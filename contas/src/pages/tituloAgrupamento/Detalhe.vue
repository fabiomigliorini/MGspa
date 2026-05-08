<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero, formataDataSemHora } from 'src/utils/formatters.js'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useAuthStore } from 'src/stores/auth'
import { PERMISSOES } from 'src/constants/permissoes'
import IconeInfoCriacao from 'src/components/IconeInfoCriacao.vue'
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

const formatCodigo = (v) => (v ? '#' + String(v).padStart(8, '0') : '')

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
  abrirPdf(`v1/titulo-agrupamento/${id.value}/relatorio`, {}, {
    title: 'Agrupamento',
  })
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
const emailDest = ref('')

function abrirDialogEmail() {
  emailDest.value = ''
  dialogEmail.value = true
}

async function enviarEmail() {
  try {
    const { data } = await api.post(`v1/titulo/agrupamento/${id.value}/mail`, {
      destinatario: emailDest.value || null,
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
              Agrupamento {{ formatCodigo(ag.codtituloagrupamento) }}
            </div>
            <div v-if="estornado" class="text-negative">
              Estornado em {{ formataDataSemHora(ag.cancelamento) }}
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
              <div class="text-h6 text-grey-7">{{ formataDataSemHora(ag.emissao) }}</div>
            </q-card>
          </div>
          <div class="col-xs-12 col-sm-4">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Total</div>
              <div
                class="text-h6"
                :class="ag.operacao === 'CR' ? 'text-orange' : 'text-green'"
              >
                {{ formataNumero(ag.valor) }} {{ ag.operacao }}
              </div>
            </q-card>
          </div>
        </div>

        <q-card bordered flat>
          <q-card-section class="text-grey-9 text-overline row items-center">
            DETALHES DO AGRUPAMENTO
            <q-space />
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
            <IconeInfoCriacao
              :usuariocriacao="ag.usuariocriacao"
              :criacao="ag.criacao"
              :usuarioalteracao="ag.usuarioalteracao"
              :alteracao="ag.alteracao"
            />
          </q-card-section>
          <template v-if="ag.observacao">
            <q-separator />
            <q-card-section>
              <div class="text-overline text-grey-7">Observação</div>
              <div
                class="text-body2 bg-grey-2 rounded-borders q-pa-sm"
                style="white-space: pre-line"
              >
                {{ ag.observacao }}
              </div>
            </q-card-section>
          </template>
        </q-card>

        <!-- Títulos Gerados -->
        <q-card bordered flat class="q-mt-md">
          <q-card-section class="text-grey-9 text-overline">
            TÍTULOS GERADOS ({{ ag.titulos_gerados?.length || 0 }})
          </q-card-section>
          <q-list separator v-if="ag.titulos_gerados?.length">
            <q-item v-for="t in ag.titulos_gerados" :key="t.codtitulo">
              <q-item-section style="flex: 0 0 90px" class="gt-xs">
                <q-item-label caption :class="t.gerencial ? 'text-orange' : 'text-green'">
                  {{ t.filial }}
                </q-item-label>
              </q-item-section>
              <q-item-section style="min-width: 0">
                <q-item-label class="text-weight-medium">
                  <q-btn
                    flat
                    dense
                    no-caps
                    padding="0"
                    color="primary"
                    :label="t.numero"
                    :to="{ name: 'titulo-detalhe', params: { codtitulo: t.codtitulo } }"
                  />
                </q-item-label>
                <q-item-label caption v-if="t.boleto">
                  Boleto {{ t.nossonumero }}
                </q-item-label>
              </q-item-section>
              <q-item-section style="flex: 0 0 130px" class="gt-xs">
                <q-item-label caption>
                  Vencimento {{ formataDataSemHora(t.vencimento) }}
                </q-item-label>
                <q-item-label caption>{{ t.portador }}</q-item-label>
              </q-item-section>
              <q-item-section style="flex: 0 0 110px">
                <q-item-label
                  class="text-weight-bold text-right"
                  :class="t.operacao === 'CR' ? 'text-orange' : 'text-green'"
                >
                  {{ formataNumero(t.valor) }} {{ t.operacao }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <!-- Títulos Baixados -->
        <q-card bordered flat class="q-mt-md">
          <q-card-section class="text-grey-9 text-overline">
            TÍTULOS BAIXADOS ({{ ag.titulos_baixados?.length || 0 }})
          </q-card-section>
          <q-list separator v-if="ag.titulos_baixados?.length">
            <q-item v-for="m in ag.titulos_baixados" :key="m.codmovimentotitulo">
              <q-item-section style="flex: 0 0 90px" class="gt-xs">
                <q-item-label caption :class="m.titulo?.gerencial ? 'text-orange' : 'text-green'">
                  {{ m.titulo?.filial }}
                </q-item-label>
              </q-item-section>
              <q-item-section style="min-width: 0">
                <q-item-label class="text-weight-medium">
                  <q-btn
                    flat
                    dense
                    no-caps
                    padding="0"
                    color="primary"
                    :label="m.titulo?.numero"
                    :to="{ name: 'titulo-detalhe', params: { codtitulo: m.titulo?.codtitulo } }"
                  />
                </q-item-label>
                <q-item-label caption>{{ m.tipomovimentotitulo }}</q-item-label>
              </q-item-section>
              <q-item-section style="flex: 0 0 130px" class="gt-xs">
                <q-item-label caption>
                  Vencimento {{ formataDataSemHora(m.titulo?.vencimento) }}
                </q-item-label>
                <q-item-label caption v-if="m.titulo?.boleto">
                  Boleto {{ m.titulo?.nossonumero }}
                </q-item-label>
              </q-item-section>
              <q-item-section style="flex: 0 0 110px">
                <q-item-label
                  class="text-weight-bold text-right"
                  :class="m.operacao === 'CR' ? 'text-orange' : 'text-green'"
                >
                  {{ formataNumero(m.valor) }} {{ m.operacao }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>
    </div>

    <q-inner-loading :showing="loading" color="primary" />

    <!-- Dialog Email -->
    <q-dialog v-model="dialogEmail">
      <q-card bordered flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">ENVIAR POR E-MAIL</q-card-section>
        <q-form @submit.prevent="enviarEmail">
          <q-separator inset />
          <q-card-section>
            <q-input
              v-model="emailDest"
              outlined
              label="Destinatário (vazio usa cobrança da pessoa)"
              type="email"
              autofocus
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
