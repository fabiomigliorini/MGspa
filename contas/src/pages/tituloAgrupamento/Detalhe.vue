<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import {
  formataNumero,
  formataData,
  formataTelefone,
  formataNumeroNota,
  formataCodNegocio,
} from '@components/formatters'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useAuthStore } from 'src/stores/auth'
import { PERMISSOES } from 'src/constants/permissoes'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInputData from '@components/MgInputData.vue'
import MgNotaFiscalAcoes from '@components/MgNotaFiscalAcoes.vue'
import SelectPessoa from 'src/components/select/SelectPessoa.vue'
import {
  getNotaFiscalStatusColor,
  getNotaFiscalStatusLabel,
  isNotaFiscalCanceladaInutilizada,
} from '@components/notaFiscalStatus'
import { abrirPdf } from 'src/utils/abrirPdf'

const route = useRoute()
const $q = useQuasar()
const auth = useAuthStore()

const podeMutar = computed(() =>
  auth.hasAnyPermission([PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA]),
)

const ag = ref(null)
const loading = ref(false)

const id = computed(() => (route.params.id ? Number(route.params.id) : null))

const estornado = computed(() => !!ag.value?.cancelamento)
const podeEstornar = computed(() => podeMutar.value && ag.value && !estornado.value)

async function carregar() {
  if (!id.value) return
  loading.value = true
  try {
    const { data } = await api.get(`v1/titulo-agrupamento/${id.value}`)
    ag.value = data.data
  } catch (e) {
    notifyError(e, 'Erro ao carregar agrupamento')
    ag.value = null
  } finally {
    loading.value = false
  }
}

function abrirRelatorio() {
  abrirPdf(
    `v1/titulo-agrupamento/${id.value}/relatorio`,
    {},
    {
      title: 'Agrupamento',
    },
  )
}

const dialogEditar = ref(false)
const editar = ref({ codpessoa: null, emissao: null, observacao: '' })
const salvandoEdicao = ref(false)

function abrirDialogEditar() {
  editar.value = {
    codpessoa: ag.value?.codpessoa ?? null,
    emissao: ag.value?.emissao ? String(ag.value.emissao).slice(0, 10) : null,
    observacao: ag.value?.observacao ?? '',
  }
  dialogEditar.value = true
}

async function salvarEdicao() {
  salvandoEdicao.value = true
  try {
    const { data } = await api.put(`v1/titulo-agrupamento/${id.value}`, {
      codpessoa: editar.value.codpessoa,
      emissao: editar.value.emissao,
      observacao: editar.value.observacao || null,
    })
    ag.value = data.data
    notifySuccess('Agrupamento atualizado')
    dialogEditar.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar agrupamento')
  } finally {
    salvandoEdicao.value = false
  }
}

function estornar() {
  $q.dialog({
    title: 'Estornar',
    message: 'Confirma estornar este agrupamento?',
    cancel: true,
  }).onOk(async () => {
    try {
      const { data } = await api.post(`v1/titulo-agrupamento/${id.value}/estornar`)
      ag.value = data.data
      notifySuccess('Agrupamento estornado')
    } catch (e) {
      notifyError(e, 'Erro ao estornar')
    }
  })
}

const dialogEmail = ref(false)
const emailsSelecionados = ref([])
const emailsExtras = ref([''])

const mensagemWhatsapp = encodeURIComponent(
  'Olá! Aqui é do departamento de Cobrança da MG Papelaria! ' +
    'Acabamos de enviar os documentos do fechamento de(s) sua(s) compra(s). ' +
    'Por favor, poderia confirmar se você recebeu?',
)
const urlWhatsapp = (t) => `https://wa.me/${t.pais}${t.ddd}${t.telefone}?text=${mensagemWhatsapp}`

const dialogModelo = ref(false)
const gerandoNota = ref(false)
const gerarTodos = ref(false)

const NOTAS_URL = process.env.NOTAS_URL
const urlNotaFiscal = (cod) => (cod ? `${NOTAS_URL}/nota/${cod}` : null)
function abrirDialogModelo() {
  gerarTodos.value = false
  dialogModelo.value = true
}

async function gerarNota(modelo) {
  dialogModelo.value = false
  gerandoNota.value = true
  try {
    await api.post(`v1/titulo-agrupamento/${id.value}/gerar-nota-fiscal`, {
      modelo,
      todos: gerarTodos.value,
    })
    notifySuccess('Nota Fiscal gerada')
    await carregar()
  } catch (e) {
    notifyError(e, 'Erro ao gerar Nota Fiscal')
  } finally {
    gerandoNota.value = false
  }
}

function abrirDialogEmail() {
  emailsSelecionados.value = (ag.value?.pessoa_emails || [])
    .filter((e) => e.cobranca)
    .map((e) => e.email)
  emailsExtras.value = ['']
  dialogEmail.value = true
}

async function enviarEmail() {
  const extras = emailsExtras.value.map((s) => s.trim()).filter(Boolean)
  const todos = [...emailsSelecionados.value, ...extras]
  if (!todos.length) {
    notifyError('Selecione ou informe ao menos um e-mail')
    return
  }
  try {
    const { data } = await api.post(`v1/titulo/agrupamento/${id.value}/mail`, {
      destinatario: todos.join(','),
    })
    if (data.sucesso === false) {
      notifyError(data.mensagem || 'Erro ao enviar e-mail')
    } else {
      notifySuccess('E-mail enviado!')
      dialogEmail.value = false
    }
  } catch (e) {
    notifyError(e, 'Erro ao enviar e-mail')
  }
}

const urlPessoa = (cod) => (cod ? `${process.env.PESSOAS_URL}/pessoa/${cod}` : null)

onMounted(carregar)
watch(() => route.fullPath, carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1100px; margin: auto">
      <template v-if="ag">
        <q-item class="q-pb-md q-px-none">
          <q-item-section avatar>
            <q-btn
              flat
              dense
              round
              icon="arrow_back"
              :to="{ name: 'agrupamento' }"
              aria-label="Voltar"
            />
          </q-item-section>
          <q-item-section>
            <div class="text-h4 text-grey-9">
              Agrupamento {{ formataCodNegocio(ag.codtituloagrupamento) }}
            </div>
            <div v-if="estornado" class="text-negative">
              Estornado em {{ formataData(ag.cancelamento) }}
            </div>
          </q-item-section>
        </q-item>

        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-xs-6 col-sm-4">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Pessoa</div>
              <div class="text-h6 ellipsis">
                <q-btn
                  flat
                  dense
                  no-caps
                  padding="0"
                  color="primary"
                  :label="ag.fantasia"
                  :href="urlPessoa(ag.codpessoa)"
                  target="_blank"
                />
              </div>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-4">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Emissão</div>
              <div class="text-h6 text-grey-7">{{ formataData(ag.emissao) }}</div>
            </q-card>
          </div>
          <div class="col-xs-12 col-sm-4">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Total</div>
              <div class="text-h6" :class="ag.operacao === 'CR' ? 'text-orange' : 'text-green'">
                {{ formataNumero(ag.valor) }} {{ ag.operacao }}
              </div>
            </q-card>
          </div>
        </div>

        <!-- Linha 2 -->
        <div class="row q-col-gutter-md">
          <!-- DETALHES -->
          <div class="col-xs-12 col-sm-8">
            <q-card bordered flat>
              <q-card-section class="text-grey-9 text-overline row items-center">
                DETALHES DO AGRUPAMENTO
                <q-space />
                <q-btn
                  v-if="podeMutar"
                  flat
                  round
                  size="sm"
                  icon="edit"
                  color="grey-7"
                  @click="abrirDialogEditar"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  dense
                  icon="email"
                  size="sm"
                  color="grey-7"
                  @click="abrirDialogEmail"
                  v-if="!estornado"
                >
                  <q-tooltip>Enviar por e-mail</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  dense
                  icon="print"
                  size="sm"
                  color="grey-7"
                  @click="abrirRelatorio"
                  v-if="!estornado"
                >
                  <q-tooltip>Relatório</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="podeEstornar"
                  flat
                  round
                  dense
                  icon="undo"
                  size="sm"
                  color="grey-7"
                  @click="estornar"
                >
                  <q-tooltip>Estornar</q-tooltip>
                </q-btn>
                <MgInfoCriacao
                  :usuariocriacao="ag.usuariocriacao"
                  :criacao="ag.criacao"
                  :usuarioalteracao="ag.usuarioalteracao"
                  :alteracao="ag.alteracao"
                />
              </q-card-section>
              <q-card-section class="q-pt-none">
                <div
                  class="text-body2 bg-grey-2 rounded-borders q-pa-md"
                  style="white-space: pre-line"
                >
                  <span v-if="ag.observacao">
                    {{ ag.observacao }}
                  </span>
                  <span v-else class="text-italic text-grey-7"> Sem Observações </span>
                </div>
              </q-card-section>
            </q-card>
          </div>

          <!-- WHATSAPP -->
          <div class="col-xs-12 col-sm-4">
            <!-- WhatsApp -->
            <q-card bordered flat>
              <q-card-section class="text-grey-9 text-overline">
                WHATSAPP ({{ ag.pessoa_telefones.length }})
              </q-card-section>
              <q-list separator>
                <q-item
                  v-for="t in ag.pessoa_telefones"
                  :key="t.codpessoatelefone"
                  clickable
                  tag="a"
                  :href="urlWhatsapp(t)"
                  target="_blank"
                >
                  <q-item-section avatar>
                    <q-icon name="chat" color="green-6" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ formataTelefone(t) }}</q-item-label>
                    <q-item-label caption v-if="t.apelido">{{ t.apelido }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
              <q-card-section class="text-caption text-grey-7 q-pt-none">
                Clique em um número de telefone acima para enviar a mensagem de confirmação de
                recebimento dos documentos por email.
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Linha 3 -->
        <div class="row q-col-gutter-md">
          <!-- NOTAS -->
          <div class="col-xs-12 col-sm-4">
            <!-- Notas Fiscais -->
            <q-card bordered flat class="q-mt-md">
              <q-card-section class="text-grey-9 text-overline row">
                NOTAS FISCAIS ({{ ag.notas_fiscais.length }})
                <q-space />
                <q-btn
                  v-if="podeMutar && !estornado"
                  flat
                  round
                  dense
                  icon="receipt_long"
                  size="sm"
                  color="grey-7"
                  @click="abrirDialogModelo"
                >
                  <q-tooltip>Gerar Nota Fiscal</q-tooltip>
                </q-btn>
              </q-card-section>
              <q-list separator>
                <q-item
                  v-for="n in ag.notas_fiscais"
                  :key="n.codnotafiscal"
                  :class="isNotaFiscalCanceladaInutilizada(n.status) ? 'bg-red-1' : ''"
                  :href="urlNotaFiscal(n.codnotafiscal)"
                >
                  <q-item-section>
                    <q-item-label class="text-weight-medium text-primary">
                      {{ formataNumeroNota(n.numero, n.modelo, n.serie, n.emitida) }}
                    </q-item-label>
                    <q-item-label caption :class="`text-${getNotaFiscalStatusColor(n.status)} `">
                      {{ n.filial }}
                      &#8226;
                      <span> {{ getNotaFiscalStatusLabel(n.status) }}</span>
                    </q-item-label>
                    <q-item-label caption>
                      <MgNotaFiscalAcoes
                        compact
                        show-extras
                        :nota="n"
                        :api="api"
                        @action-completed="carregar"
                      />
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label class="text-weight-bold text-primary ellipsis">
                      {{ formataNumero(n.valortotal) }}
                    </q-item-label>
                    <q-item-label caption class="ellipsis">{{ n.naturezaoperacao }}</q-item-label>
                    <q-item-label caption class="ellipsis">
                      {{ formataData(n.emissao) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- GERADOS -->
          <div class="col-xs-12 col-sm-4">
            <!-- Títulos Gerados -->
            <q-card bordered flat class="q-mt-md">
              <q-card-section class="text-grey-9 text-overline">
                TÍTULOS GERADOS ({{ ag.titulos_gerados?.length || 0 }})
              </q-card-section>
              <q-list separator v-if="ag.titulos_gerados?.length">
                <q-item
                  v-for="t in ag.titulos_gerados"
                  :key="t.codtitulo"
                  :to="{ name: 'titulo-detalhe', params: { codtitulo: t.codtitulo } }"
                >
                  <q-item-section>
                    <q-item-label class="text-weight-medium text-primary">
                      {{ t.numero }}
                    </q-item-label>
                    <q-item-label caption :class="t.gerencial ? 'text-orange' : 'text-green'">
                      {{ t.filial }}
                    </q-item-label>
                    <q-item-label caption v-if="t.boleto">
                      Boleto {{ t.nossonumero }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label
                      class="text-weight-bold text-right"
                      :class="t.operacao === 'CR' ? 'text-orange' : 'text-green'"
                    >
                      {{ formataNumero(t.valor) }} {{ t.operacao }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ formataData(t.vencimento) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

          <!-- BAIXADOS -->
          <div class="col-xs-12 col-sm-4">
            <!-- Títulos Baixados -->
            <q-card bordered flat class="q-mt-md">
              <q-card-section class="text-grey-9 text-overline">
                MOVIMENTOS ({{ ag.titulos_baixados?.length || 0 }})
              </q-card-section>
              <q-list separator v-if="ag.titulos_baixados?.length">
                <q-item
                  v-for="m in ag.titulos_baixados"
                  :key="m.codmovimentotitulo"
                  :to="{ name: 'titulo-detalhe', params: { codtitulo: m.titulo?.codtitulo } }"
                >
                  <q-item-section>
                    <q-item-label class="text-weight-medium text-primary">
                      {{ m.titulo?.numero }}
                    </q-item-label>
                    <q-item-label caption v-if="m.titulo.codpessoa != ag.codpessoa">
                      {{ m.titulo?.fantasia }}
                    </q-item-label>

                    <q-item-label
                      caption
                      :class="m.titulo?.gerencial ? 'text-orange' : 'text-green'"
                    >
                      {{ m.titulo?.filial }}
                    </q-item-label>
                    <q-item-label caption v-if="m.titulo?.boleto">
                      Boleto {{ m.titulo?.nossonumero }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label
                      class="text-weight-bold text-right"
                      :class="m.operacao === 'CR' ? 'text-orange' : 'text-green'"
                    >
                      {{ formataNumero(m.valor) }} {{ m.operacao }}
                    </q-item-label>
                    <q-item-label caption>{{ m.tipomovimentotitulo }}</q-item-label>
                    <q-item-label caption>
                      {{ formataData(m.titulo?.vencimento) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
        </div>
      </template>
    </div>

    <q-inner-loading :showing="loading || gerandoNota || salvandoEdicao" color="primary" />

    <!-- Dialog Editar -->
    <q-dialog v-model="dialogEditar">
      <q-card bordered flat style="width: 500px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">EDITAR AGRUPAMENTO</q-card-section>
        <q-form @submit.prevent="salvarEdicao">
          <q-separator inset />
          <q-card-section class="">
            <div class="row q-col-gutter-md">
              <div class="col-9">
                <SelectPessoa
                  v-model="editar.codpessoa"
                  outlined
                  label="Pessoa"
                  autofocus
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
              <div class="col-3">
                <MgInputData
                  v-model="editar.emissao"
                  type="date"
                  label="Emissão"
                  year-digits="2"
                  stack-label
                  :rules="[(v) => !!v || 'Obrigatório']"
                />
              </div>
            </div>
            <q-input
              v-model="editar.observacao"
              outlined
              type="textarea"
              label="Observações"
              autogrow
              maxlength="200"
            />
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Salvar" type="submit" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Dialog Modelo Nota Fiscal -->
    <q-dialog v-model="dialogModelo">
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          GERAR QUAL MODELO DE NOTA FISCAL?
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <q-checkbox v-model="gerarTodos" label="Incluir itens que já possuem nota fiscal" />
          <div class="text-caption text-grey-7 q-mt-xs">
            Por padrão só entram itens sem nota fiscal ativa.
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="NFC-e (Cupom)" color="primary" @click="gerarNota(65)" />
          <q-btn flat label="NF-e (Nota)" color="primary" @click="gerarNota(55)" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog Email -->
    <q-dialog v-model="dialogEmail">
      <q-card bordered flat style="width: 500px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">ENVIAR POR E-MAIL</q-card-section>
        <q-form @submit.prevent="enviarEmail">
          <q-separator inset />
          <q-card-section v-if="ag?.pessoa_emails?.length">
            <div class="text-caption text-grey-7 q-mb-xs">E-mails cadastrados</div>
            <div v-for="e in ag.pessoa_emails" :key="e.codpessoaemail">
              <q-checkbox
                v-model="emailsSelecionados"
                :val="e.email"
                :label="e.apelido ? `${e.email} (${e.apelido})` : e.email"
              />
            </div>
          </q-card-section>
          <q-separator inset v-if="ag?.pessoa_emails?.length" />
          <q-card-section>
            <div class="row items-center q-mb-xs">
              <div class="text-caption text-grey-7">E-mails adicionais</div>
              <q-space />
              <q-btn
                flat
                dense
                round
                size="sm"
                icon="add"
                color="primary"
                @click="emailsExtras.push('')"
                tabindex="-1"
              >
                <q-tooltip>Adicionar e-mail</q-tooltip>
              </q-btn>
            </div>
            <q-input
              v-for="(_, i) in emailsExtras"
              :key="i"
              v-model="emailsExtras[i]"
              outlined
              type="email"
              placeholder="E-mail adicional"
              class="q-mb-sm"
              lazy-rules
              :rules="[(v) => !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'E-mail inválido']"
              :autofocus="i === 0 && !ag?.pessoa_emails?.length"
            />
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Enviar" type="submit" color="primary" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
