<script setup>
import { ref, computed, onMounted, watch, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import {
  useMdfeStore,
  statusColor,
  labelDe,
  TIPO_EMITENTE_OPTIONS,
  TIPO_TRANSPORTADOR_OPTIONS,
  TIPO_EMISSAO_OPTIONS,
  MODAL_OPTIONS,
} from '../stores/mdfeStore'
import mdfeService from '../services/mdfeService'
import { formataChave, formataCodigo, formataNumero, tempoRelativo } from '@components/formatters'
import { notificarSucesso, notificarErro } from '../utils/notify'

const route = useRoute()
const $q = useQuasar()
const store = useMdfeStore()

const codmdfe = computed(() => route.params.codmdfe)
const mdfe = computed(() => store.currentMdfe)
const carregando = ref(false)

const loading = reactive({
  criarXml: false,
  enviar: false,
  consultarEnvio: false,
  consultar: false,
  damdfe: false,
  cancelar: false,
  encerrar: false,
})

const status = computed(() => mdfe.value?.codmdfestatus)
const podeAlterar = computed(() => [1, 2, 4].includes(status.value))

const mgsisUrl = process.env.MGSIS_URL
const linkNotaFiscal = (codnotafiscal) =>
  `${mgsisUrl}/index.php?r=notaFiscal/view&id=${codnotafiscal}`

const loadData = async () => {
  carregando.value = true
  try {
    await store.fetchMdfe(codmdfe.value)
  } catch (error) {
    notificarErro(error, 'Falha ao carregar MDFe')
  } finally {
    carregando.value = false
  }
}

watch(codmdfe, loadData)
onMounted(loadData)

// Notifica resultado de uma ação SEFAZ (cStat/xMotivo) e recarrega
const notificarRetornoSefaz = (data, okStat, sucessoMsg) => {
  if (data?.cStat) {
    const ok = String(data.cStat) === String(okStat)
    $q.notify({
      type: ok ? 'positive' : 'warning',
      message: `${data.cStat} - ${data.xMotivo}`,
      icon: ok ? 'done' : 'warning',
    })
  } else {
    notificarSucesso(sucessoMsg)
  }
}

const executar = async (chave, fn, { okStat, sucesso, erro } = {}) => {
  loading[chave] = true
  try {
    const data = await fn()
    await loadData()
    notificarRetornoSefaz(data, okStat, sucesso)
  } catch (error) {
    notificarErro(error, erro)
  } finally {
    loading[chave] = false
  }
}

const criarXml = () =>
  executar('criarXml', () => mdfeService.criarXml(codmdfe.value), {
    sucesso: 'Arquivo XML criado!',
    erro: 'Falha ao criar XML',
  })

const enviar = () =>
  executar('enviar', () => mdfeService.enviar(codmdfe.value), {
    sucesso: 'MDFe transmitido!',
    erro: 'Falha ao transmitir MDFe',
  })

const consultarEnvio = (codmdfeenviosefaz = null) =>
  executar('consultarEnvio', () => mdfeService.consultarEnvio(codmdfe.value, codmdfeenviosefaz), {
    okStat: 100,
    sucesso: 'Transmissão consultada!',
    erro: 'Falha ao consultar transmissão',
  })

const consultar = () =>
  executar('consultar', () => mdfeService.consultar(codmdfe.value), {
    okStat: 100,
    sucesso: 'MDFe consultado!',
    erro: 'Falha ao consultar MDFe',
  })

const encerrar = () =>
  executar('encerrar', () => mdfeService.encerrar(codmdfe.value), {
    okStat: 135,
    sucesso: 'MDFe encerrado!',
    erro: 'Falha ao encerrar MDFe',
  })

const cancelar = () => {
  $q.dialog({
    title: 'Cancelar MDFe',
    message: 'Informe o motivo do cancelamento (mínimo 15 caracteres):',
    prompt: { model: '', type: 'textarea', isValid: (v) => v && v.length >= 15 },
    cancel: { label: 'Voltar', flat: true },
    ok: { label: 'Cancelar MDFe', color: 'negative' },
  }).onOk((justificativa) =>
    executar('cancelar', () => mdfeService.cancelar(codmdfe.value, justificativa), {
      okStat: 135,
      sucesso: 'MDFe cancelado!',
      erro: 'Falha ao cancelar MDFe',
    }),
  )
}

const damdfe = async () => {
  loading.damdfe = true
  try {
    const url = await mdfeService.damdfe(codmdfe.value)
    window.open(url)
    notificarSucesso('DAMDFe gerado!')
  } catch (error) {
    notificarErro(error, 'Falha ao gerar DAMDFe')
  } finally {
    loading.damdfe = false
  }
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 900px; margin: auto">
      <div v-if="carregando && !mdfe" class="row justify-center q-mt-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <template v-else-if="mdfe">
        <!-- Cabeçalho -->
        <div class="row items-center q-mb-md">
          <q-btn flat round icon="arrow_back" :to="{ name: 'mdfe' }" />
          <div class="text-h5 q-ml-sm">MDFe {{ formataCodigo(mdfe.codmdfe) }}</div>
          <q-space />
          <q-chip :color="statusColor(status)" text-color="white" class="text-weight-medium">
            {{ mdfe.mdfestatussigla }} · {{ mdfe.mdfestatus }}
          </q-chip>
        </div>

        <!-- Ações de workflow -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="row q-gutter-sm items-center">
            <q-btn
              v-if="[1, 2].includes(status)"
              outline
              color="primary"
              icon="code"
              label="Criar XML"
              :loading="loading.criarXml"
              @click="criarXml"
            />
            <q-btn
              v-if="mdfe.chmdfe && [1, 2].includes(status)"
              outline
              color="primary"
              icon="upload"
              label="Transmitir"
              :loading="loading.enviar"
              @click="enviar"
            />
            <q-btn
              v-if="mdfe.MdfeEnvioSefazS.length > 0 && [1, 2].includes(status)"
              outline
              color="primary"
              icon="download"
              label="Consultar Transmissão"
              :loading="loading.consultarEnvio"
              @click="consultarEnvio()"
            />
            <q-btn
              v-if="mdfe.chmdfe"
              outline
              color="primary"
              icon="sync"
              label="Consultar na SEFAZ"
              :loading="loading.consultar"
              @click="consultar"
            />
            <q-btn
              v-if="[3, 5].includes(status)"
              outline
              color="primary"
              icon="picture_as_pdf"
              label="DAMDFe"
              :loading="loading.damdfe"
              @click="damdfe"
            />
            <q-btn
              v-if="status === 3"
              outline
              color="positive"
              icon="check"
              label="Encerrar"
              :loading="loading.encerrar"
              @click="encerrar"
            />
            <q-btn
              v-if="status === 3"
              outline
              color="negative"
              icon="block"
              label="Cancelar"
              :loading="loading.cancelar"
              @click="cancelar"
            />
          </q-card-section>
        </q-card>

        <div class="row q-col-gutter-md">
          <!-- Emitente / Número -->
          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
                Emitente
              </q-card-section>
              <q-list separator>
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Filial</q-item-label>
                    <q-item-label>{{ mdfe.filial }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Tipo de Emitente</q-item-label>
                    <q-item-label>{{ labelDe(TIPO_EMITENTE_OPTIONS, mdfe.tipoemitente) }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Tipo de Transportador</q-item-label>
                    <q-item-label>
                      {{ labelDe(TIPO_TRANSPORTADOR_OPTIONS, mdfe.tipotransportador) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- Número e Status -->
          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
                Número e Status
              </q-card-section>
              <q-list separator>
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Identificação</q-item-label>
                    <q-item-label>
                      Modelo {{ mdfe.modelo }} · Série {{ mdfe.serie }}
                      <span v-if="mdfe.numero"> · Número {{ mdfe.numero }}</span>
                    </q-item-label>
                    <q-item-label v-if="mdfe.chmdfe" class="text-caption">
                      {{ formataChave(mdfe.chmdfe) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Emissão</q-item-label>
                    <q-item-label>
                      {{ labelDe(TIPO_EMISSAO_OPTIONS, mdfe.tipoemissao) }}
                      <span v-if="mdfe.emissao" class="text-grey-7">
                        · {{ tempoRelativo(mdfe.emissao) }}
                      </span>
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="mdfe.protocoloautorizacao">
                  <q-item-section>
                    <q-item-label caption>Autorização</q-item-label>
                    <q-item-label>Protocolo {{ mdfe.protocoloautorizacao }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="mdfe.encerramento">
                  <q-item-section>
                    <q-item-label caption>Encerramento</q-item-label>
                    <q-item-label>{{ tempoRelativo(mdfe.encerramento) }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="mdfe.inativo">
                  <q-item-section>
                    <q-item-label caption>Cancelamento</q-item-label>
                    <q-item-label>
                      <span v-if="mdfe.protocolocancelamento">
                        Protocolo {{ mdfe.protocolocancelamento }} ·
                      </span>
                      {{ tempoRelativo(mdfe.inativo) }}
                    </q-item-label>
                    <q-item-label v-if="mdfe.justificativa" class="text-caption">
                      {{ mdfe.justificativa }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- Viagem -->
          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
                Viagem
              </q-card-section>
              <q-list separator>
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Início</q-item-label>
                    <q-item-label>
                      {{ mdfe.inicioviagem ? tempoRelativo(mdfe.inicioviagem) : '—' }} ·
                      Modal {{ labelDe(MODAL_OPTIONS, mdfe.modal) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label caption>Trajeto</q-item-label>
                    <q-item-label>
                      {{ mdfe.cidadecarregamento }} → {{ mdfe.estadofim }}
                    </q-item-label>
                    <q-item-label
                      v-if="mdfe.MdfeEstadoS && mdfe.MdfeEstadoS.length"
                      class="text-caption"
                    >
                      Passando por
                      {{ mdfe.MdfeEstadoS.map((e) => e.estado).join(', ') }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- Veículos -->
          <div class="col-12 col-md-6">
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
                Veículos
              </q-card-section>
              <q-list separator>
                <q-item v-for="v in mdfe.MdfeVeiculoS" :key="v.codveiculo">
                  <q-item-section avatar>
                    <q-avatar icon="local_shipping" color="primary" text-color="white" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ v.placa }}</q-item-label>
                    <q-item-label caption>
                      <span v-if="v.proprietario">{{ v.proprietario }}</span>
                      <span v-if="v.condutor"> · Condutor: {{ v.condutor }}</span>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- Notas Fiscais -->
          <div class="col-12">
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
                Notas Fiscais
              </q-card-section>
              <q-list separator>
                <q-item v-for="nfe in mdfe.MdfeNfeS" :key="nfe.nfechave">
                  <q-item-section>
                    <q-item-label>
                      <a
                        v-if="nfe.codnotafiscal"
                        :href="linkNotaFiscal(nfe.codnotafiscal)"
                        target="_blank"
                      >
                        {{ formataChave(nfe.nfechave) }}
                      </a>
                      <span v-else>{{ formataChave(nfe.nfechave) }}</span>
                    </q-item-label>
                    <q-item-label caption>
                      <span v-if="nfe.valor">R$ {{ formataNumero(nfe.valor) }}</span>
                      <span v-if="nfe.peso"> · {{ formataNumero(nfe.peso, 0) }} Kg</span>
                      <span v-if="nfe.cidadedescarga"> · {{ nfe.cidadedescarga }}</span>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- Informações -->
          <div
            v-if="mdfe.informacoesadicionais || mdfe.informacoescomplementares"
            class="col-12"
          >
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
                Informações
              </q-card-section>
              <q-list separator>
                <q-item v-if="mdfe.informacoesadicionais">
                  <q-item-section>
                    <q-item-label caption>Adicionais</q-item-label>
                    <q-item-label>{{ mdfe.informacoesadicionais }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item v-if="mdfe.informacoescomplementares">
                  <q-item-section>
                    <q-item-label caption>Complementares</q-item-label>
                    <q-item-label>{{ mdfe.informacoescomplementares }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- Histórico de envios -->
          <div v-if="mdfe.MdfeEnvioSefazS.length" class="col-12">
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2 text-weight-bold">
                Histórico de Transmissões
              </q-card-section>
              <q-list separator>
                <q-item v-for="envio in mdfe.MdfeEnvioSefazS" :key="envio.codmdfeenviosefaz">
                  <q-item-section>
                    <q-item-label>
                      Envio {{ envio.criacao ? tempoRelativo(envio.criacao) : '' }}
                    </q-item-label>
                    <q-item-label v-if="envio.cstatretorno" caption>
                      {{ envio.cstatretorno }} - {{ envio.xmotivo }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-btn
                      flat
                      round
                      size="sm"
                      color="grey-7"
                      icon="sync"
                      :loading="loading.consultarEnvio"
                      @click="consultarEnvio(envio.codmdfeenviosefaz)"
                    >
                      <q-tooltip>Consultar este envio</q-tooltip>
                    </q-btn>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
        </div>

        <!-- Autoria -->
        <div class="text-caption text-grey q-mt-md">
          Criado por {{ mdfe.usuariocriacao }}
          <span v-if="mdfe.criacao">{{ tempoRelativo(mdfe.criacao) }}</span>
          <span v-if="mdfe.usuarioalteracao && mdfe.alteracao !== mdfe.criacao">
            · Alterado por {{ mdfe.usuarioalteracao }} {{ tempoRelativo(mdfe.alteracao) }}
          </span>
        </div>
      </template>
    </div>

    <q-page-sticky v-if="mdfe && podeAlterar" position="bottom-right" :offset="[18, 18]">
      <q-btn
        fab
        icon="edit"
        color="primary"
        :to="{ name: 'mdfe-edit', params: { codmdfe: mdfe.codmdfe } }"
      >
        <q-tooltip>Editar</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-page>
</template>
