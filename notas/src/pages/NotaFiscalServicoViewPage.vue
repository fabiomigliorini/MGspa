<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useQuasar, copyToClipboard } from 'quasar'
import { useNotaFiscalServicoStore } from '../stores/notaFiscalServicoStore'
import {
  getNfseStatusLabel,
  getNfseStatusColor,
  getNfseStatusIcon,
  formataCodigoTributacaoNacional,
  formataNbs,
} from '../constants/notaFiscalServico'
import {
  formataCnpjCpf,
  formataTimestamp,
  formataData,
  formataNumero,
  formataNumeroNota,
  formataChave,
} from '@components/formatters'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgNotaFiscalServicoAcoes from '@components/MgNotaFiscalServicoAcoes.vue'
import api from '../services/api'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()
const store = useNotaFiscalServicoStore()

const nota = computed(() => store.nota)
const editavel = computed(() => nota.value && ['DIG', 'ERR'].includes(nota.value.status))
const acoesRef = ref(null)

async function carregar(force = false) {
  try {
    await store.fetchNota(route.params.codnotafiscalservico, force)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      icon: 'error',
      message: 'Erro ao carregar a nota de serviço',
      caption: error?.response?.data?.message || error?.message,
    })
  }
}

// As acoes devolvem a nota atualizada: usa o payload em vez de buscar de novo
function onAcaoConcluida(acao, notaAtualizada) {
  if (acao === 'excluir') {
    store.removerDaLista(notaAtualizada?.codnotafiscalservico)
    router.push({ name: 'notas-servico' })
    return
  }
  if (!notaAtualizada) return carregar(true)
  store.setNota(notaAtualizada)
  if (acao === 'duplicar') {
    router.push({
      name: 'nota-servico-view',
      params: { codnotafiscalservico: notaAtualizada.codnotafiscalservico },
    })
  }
  // A substituta nasce para ser corrigida: abre direto no formulario
  if (acao === 'substituir') {
    router.push({
      name: 'nota-servico-edit',
      params: { codnotafiscalservico: notaAtualizada.codnotafiscalservico },
    })
  }
}

// Substituicao (TASK-279 #5): "NFS-e 123", "DPS 000.025 - Série 1" ou em digitacao
function descricaoNota(n) {
  if (n.nfsenumero) return `NFS-e ${n.nfsenumero}`
  if (n.numero) return `DPS ${formataNumeroNota(n.numero)} - Série ${n.serie}`
  return 'Nota em digitação'
}

function copiarChave() {
  copyToClipboard(nota.value.nfsechave).then(() =>
    $q.notify({ color: 'green-5', icon: 'done', message: 'Chave copiada' }),
  )
}

// F9 envia, como na nota fiscal
function onKeyDown(event) {
  if (event.key === 'F9' && acoesRef.value?.podeEnviar) {
    event.preventDefault()
    acoesRef.value.enviar()
  }
}

onMounted(() => {
  carregar()
  window.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeyDown)
})

watch(
  () => route.params.codnotafiscalservico,
  (novo, antigo) => {
    if (novo && novo !== antigo) carregar()
  },
)
</script>

<template>
  <q-page padding>
    <div style="max-width: 1086px; margin: auto">
      <div v-if="store.loadingNota && !nota" class="row justify-center q-py-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <template v-else-if="nota">
        <!-- Cabeçalho -->
        <div class="row items-center q-mb-md no-wrap">
          <q-btn flat round icon="arrow_back" to="/nota-servico" class="q-mr-sm" />
          <div class="text-h5 ellipsis col">
            <template v-if="nota.nfsenumero">NFS-e {{ nota.nfsenumero }}</template>
            <template v-else-if="nota.numero">
              DPS {{ formataNumeroNota(nota.numero) }} - Série {{ nota.serie }}
            </template>
            <template v-else>Nota de Serviço em Digitação</template>
          </div>

          <MgInfoCriacao :registro="nota" class="q-mr-sm" />

          <q-btn
            v-if="editavel"
            flat
            round
            color="grey-7"
            icon="edit"
            :to="{
              name: 'nota-servico-edit',
              params: { codnotafiscalservico: nota.codnotafiscalservico },
            }"
          >
            <q-tooltip>Editar</q-tooltip>
          </q-btn>

          <MgNotaFiscalServicoAcoes
            ref="acoesRef"
            :nota="nota"
            :api="api"
            show-extras
            mostrar-excluir
            @action-completed="onAcaoConcluida"
          />
        </div>

        <!-- Rejeição / erro do Sistema Nacional -->
        <q-banner
          v-if="nota.status === 'ERR' && nota.mensagem"
          class="bg-red-1 text-red-9 q-mb-md"
          rounded
        >
          <template v-slot:avatar>
            <q-icon name="error" color="negative" />
          </template>
          <div class="text-weight-bold">Não autorizada pelo Sistema Nacional</div>
          <div style="white-space: pre-line">{{ nota.mensagem }}</div>
        </q-banner>

        <div class="row q-col-gutter-md">
          <!-- NFS-e -->
          <div class="col-12 col-md-6">
            <q-card flat bordered class="full-height">
              <q-card-section class="bg-primary text-white text-body2">
                <q-icon name="receipt" size="1.5em" class="q-mr-sm" />
                NFS-e
              </q-card-section>
              <q-card-section>
                <div class="text-caption text-grey-7">Status</div>
                <div class="q-mb-sm">
                  <q-badge :color="getNfseStatusColor(nota.status)" class="text-subtitle2">
                    <q-icon :name="getNfseStatusIcon(nota.status)" size="sm" class="q-mr-xs" />
                    {{ getNfseStatusLabel(nota.status) }}
                  </q-badge>
                </div>

                <div class="text-caption text-grey-7">Filial</div>
                <div class="text-body2 q-mb-sm">{{ nota.filial?.filial || '-' }}</div>

                <div class="text-caption text-grey-7">DPS</div>
                <div class="text-body2 q-mb-sm" style="font-family: monospace">
                  <template v-if="nota.numero">
                    {{ formataNumeroNota(nota.numero) }} - Série {{ nota.serie }}
                  </template>
                  <template v-else>Sem número (recebe no envio)</template>
                </div>

                <template v-if="nota.nfsechave">
                  <div class="text-caption text-grey-7">Chave de Acesso</div>
                  <div class="text-caption row items-center no-wrap q-mb-sm">
                    <span style="font-family: monospace">{{ formataChave(nota.nfsechave) }}</span>
                    <q-btn
                      flat
                      round
                      size="sm"
                      icon="content_copy"
                      color="grey-7"
                      class="q-ml-xs"
                      @click="copiarChave"
                    >
                      <q-tooltip>Copiar chave</q-tooltip>
                    </q-btn>
                  </div>
                </template>

                <div class="row q-col-gutter-sm">
                  <div class="col-6">
                    <div class="text-caption text-grey-7">Emissão</div>
                    <div class="text-body2">
                      {{ nota.emissao ? formataTimestamp(nota.emissao, 4, true) : '-' }}
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-caption text-grey-7">Competência</div>
                    <div class="text-body2">{{ formataData(nota.competencia) }}</div>
                  </div>
                  <div class="col-6" v-if="nota.nfseautorizacao">
                    <div class="text-caption text-grey-7">Autorização</div>
                    <div class="text-body2">
                      {{ formataTimestamp(nota.nfseautorizacao, 4, true) }}
                    </div>
                  </div>
                  <div class="col-6" v-if="nota.nfsecancelamento">
                    <div class="text-caption text-grey-7">Cancelamento</div>
                    <div class="text-body2">
                      {{ formataTimestamp(nota.nfsecancelamento, 4, true) }}
                    </div>
                  </div>
                  <div class="col-12" v-if="nota.justificativa">
                    <div class="text-caption text-grey-7">Justificativa</div>
                    <div class="text-body2">{{ nota.justificativa }}</div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Tomador -->
          <div class="col-12 col-md-6">
            <q-card flat bordered class="full-height">
              <q-card-section class="bg-primary text-white text-body2">
                <q-icon
                  :name="nota.pessoa?.fisica ? 'person' : 'business'"
                  size="1.5em"
                  class="q-mr-sm"
                />
                Tomador
              </q-card-section>
              <q-card-section>
                <template v-if="nota.pessoa">
                  <div class="text-caption text-grey-7">Nome</div>
                  <div class="text-subtitle1 text-weight-bold q-mb-sm">
                    {{ nota.pessoa.fantasia }}
                  </div>
                  <div class="text-caption text-grey-7">Razão Social</div>
                  <div class="text-body2 q-mb-sm">{{ nota.pessoa.pessoa }}</div>
                  <div class="text-caption text-grey-7">
                    {{ nota.pessoa.fisica ? 'CPF' : 'CNPJ' }}
                  </div>
                  <div class="text-body2 q-mb-sm">
                    {{ formataCnpjCpf(nota.pessoa.cnpj, nota.pessoa.fisica) }}
                  </div>
                  <template v-if="nota.pessoa.emailnfe">
                    <div class="text-caption text-grey-7">E-mail de NF-e</div>
                    <div class="text-body2">{{ nota.pessoa.emailnfe }}</div>
                  </template>
                </template>
                <div v-else class="text-body2 text-grey-7">Tomador não identificado</div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Substituição (TASK-279 #5): nas duas pontas do vínculo -->
          <div class="col-12" v-if="nota.substituida || nota.substituta">
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2">
                <q-icon name="find_replace" size="1.5em" class="q-mr-sm" />
                Substituição
              </q-card-section>
              <q-card-section>
                <div class="row q-col-gutter-md">
                  <div class="col-12 col-md-6" v-if="nota.substituida">
                    <div class="text-caption text-grey-7">Substitui a</div>
                    <div class="text-body2 q-mb-sm">
                      <router-link
                        :to="{
                          name: 'nota-servico-view',
                          params: { codnotafiscalservico: nota.substituida.codnotafiscalservico },
                        }"
                        class="text-primary"
                      >
                        {{ descricaoNota(nota.substituida) }}
                      </router-link>
                      <q-badge :color="getNfseStatusColor(nota.substituida.status)" class="q-ml-sm">
                        {{ getNfseStatusLabel(nota.substituida.status) }}
                      </q-badge>
                    </div>
                    <div class="text-caption text-grey-7">Motivo</div>
                    <div class="text-body2 q-mb-sm">{{ nota.substituicaomotivodescricao }}</div>
                    <template v-if="nota.substituicaojustificativa">
                      <div class="text-caption text-grey-7">Descrição do motivo</div>
                      <div class="text-body2">{{ nota.substituicaojustificativa }}</div>
                    </template>
                  </div>
                  <div class="col-12 col-md-6" v-if="nota.substituta">
                    <div class="text-caption text-grey-7">Substituída pela</div>
                    <div class="text-body2 q-mb-sm">
                      <router-link
                        :to="{
                          name: 'nota-servico-view',
                          params: { codnotafiscalservico: nota.substituta.codnotafiscalservico },
                        }"
                        class="text-primary"
                      >
                        {{ descricaoNota(nota.substituta) }}
                      </router-link>
                      <q-badge :color="getNfseStatusColor(nota.substituta.status)" class="q-ml-sm">
                        {{ getNfseStatusLabel(nota.substituta.status) }}
                      </q-badge>
                    </div>
                    <div class="text-caption text-grey-7">Motivo</div>
                    <div class="text-body2 q-mb-sm">
                      {{ nota.substituta.substituicaomotivodescricao }}
                    </div>
                    <template v-if="nota.substituta.substituicaojustificativa">
                      <div class="text-caption text-grey-7">Descrição do motivo</div>
                      <div class="text-body2">{{ nota.substituta.substituicaojustificativa }}</div>
                    </template>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Serviço -->
          <div class="col-12">
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2">
                <q-icon name="design_services" size="1.5em" class="q-mr-sm" />
                Serviço
              </q-card-section>
              <q-card-section>
                <div class="row q-col-gutter-md">
                  <div class="col-12 col-md-6">
                    <div class="text-caption text-grey-7">Código de Tributação Nacional</div>
                    <div class="text-body2">
                      <span class="text-weight-bold">
                        {{ formataCodigoTributacaoNacional(nota.tributacaonacional?.codigo) }}
                      </span>
                      {{ nota.tributacaonacional?.descricao }}
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="text-caption text-grey-7">NBS</div>
                    <div class="text-body2">
                      <span class="text-weight-bold">{{ formataNbs(nota.nbs?.codigo) }}</span>
                      {{ nota.nbs?.descricao }}
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="text-caption text-grey-7">Descrição do Serviço</div>
                    <div class="text-body2" style="white-space: pre-line">{{ nota.descricao }}</div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="text-caption text-grey-7">Local da Prestação</div>
                    <div class="text-body2">
                      {{ nota.cidadeprestacao?.cidade }}
                      <template v-if="nota.cidadeprestacao?.uf">
                        / {{ nota.cidadeprestacao.uf }}
                      </template>
                    </div>
                  </div>
                  <div class="col-12" v-if="nota.observacoes">
                    <div class="text-caption text-grey-7">Observações</div>
                    <div class="text-body2" style="white-space: pre-line">
                      {{ nota.observacoes }}
                    </div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Valores -->
          <div class="col-12">
            <q-card flat bordered>
              <q-card-section class="bg-primary text-white text-body2">
                <q-icon name="attach_money" size="1.5em" class="q-mr-sm" />
                Valores
              </q-card-section>
              <q-card-section>
                <div class="row q-col-gutter-md">
                  <div class="col-6 col-md-3">
                    <div class="text-caption text-grey-7">Valor do Serviço</div>
                    <div class="text-body1">R$ {{ formataNumero(nota.valorservico) }}</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-caption text-grey-7">Desconto Incondicionado</div>
                    <div class="text-body1">
                      R$ {{ formataNumero(nota.valordescontoincondicionado || 0) }}
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-caption text-grey-7">Desconto Condicionado</div>
                    <div class="text-body1">
                      R$ {{ formataNumero(nota.valordescontocondicionado || 0) }}
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-caption text-grey-7">Base de Cálculo</div>
                    <div class="text-body1">R$ {{ formataNumero(nota.basecalculo) }}</div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-caption text-grey-7">Alíquota do ISS</div>
                    <div class="text-body1">
                      {{ nota.issaliquota != null ? formataNumero(nota.issaliquota) + '%' : '-' }}
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-caption text-grey-7">ISS Retido</div>
                    <div class="text-body1">
                      {{ nota.issvalor != null ? 'R$ ' + formataNumero(nota.issvalor) : '-' }}
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-caption text-grey-7">Tributos Aprox. (Simples)</div>
                    <div class="text-body1">
                      {{
                        nota.tributospercentual != null
                          ? formataNumero(nota.tributospercentual) + '%'
                          : '-'
                      }}
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-caption text-grey-7">Retenção do ISS</div>
                    <div class="text-body1">
                      {{ nota.issretido ? 'Retido pelo tomador' : 'Não retido' }}
                    </div>
                  </div>
                  <div class="col-6 col-md-3">
                    <div class="text-caption text-grey-7">Valor Líquido</div>
                    <div class="text-h6 text-weight-bold text-primary">
                      R$ {{ formataNumero(nota.valorliquido) }}
                    </div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </template>

      <q-card v-else flat bordered class="q-pa-xl text-center">
        <q-icon name="error" size="4em" color="negative" />
        <div class="text-h6 text-grey-7 q-mt-md">Nota de serviço não encontrada</div>
      </q-card>
    </div>
  </q-page>
</template>
