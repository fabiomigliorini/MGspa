<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero, formataDataSemHora } from 'src/utils/formatters.js'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useAuthStore } from 'src/stores/auth'
import { PERMISSOES } from 'src/constants/permissoes'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import { abrirPdf } from 'src/utils/abrirPdf'

const route = useRoute()
const $q = useQuasar()
const auth = useAuthStore()

const podeMutar = computed(() =>
  auth.hasAnyPermission([PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.COBRANCA]),
)

const liq = ref(null)
const loading = ref(false)

const id = computed(() => (route.params.id ? Number(route.params.id) : null))

const estornado = computed(() => !!liq.value?.estornado)
const fechadaPeriodo = computed(() => !!liq.value?.codperiodo)

const podeEstornar = computed(
  () => podeMutar.value && liq.value && !estornado.value && !fechadaPeriodo.value,
)

const formatCodigo = (v) => (v ? '#' + String(v).padStart(8, '0') : '')

async function carregar() {
  if (!id.value) return
  loading.value = true
  try {
    const { data } = await api.get(`v1/liquidacao-titulo/${id.value}`)
    liq.value = data.data
  } catch (e) {
    notifyError(e, 'Erro ao carregar liquidação')
    liq.value = null
  } finally {
    loading.value = false
  }
}

function abrirRecibo(rota) {
  const titulos = {
    recibo: 'Recibo',
    'recibo-recebimento': 'Recibo de Recebimento',
    'recibo-pagamento': 'Recibo de Pagamento',
  }
  abrirPdf(`v1/liquidacao-titulo/${id.value}/${rota}`, {}, {
    title: titulos[rota] || 'Recibo',
  })
}

function estornar() {
  $q.dialog({
    title: 'Estornar',
    message: 'Confirma estornar esta liquidação?',
    cancel: true,
  }).onOk(async () => {
    try {
      const { data } = await api.post(`v1/liquidacao-titulo/${id.value}/estornar`)
      liq.value = data.data
      notifySuccess('Liquidação estornada')
    } catch (e) {
      notifyError(e, 'Erro ao estornar')
    }
  })
}

const urlPessoa = (cod) => (cod ? `${process.env.PESSOAS_URL}/pessoa/${cod}` : null)

onMounted(carregar)
watch(() => route.fullPath, carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1000px; margin: auto">
      <template v-if="liq">
        <!-- Cabeçalho -->
        <q-item class="q-pb-md q-px-none">
          <q-item-section avatar>
            <q-btn
              flat
              dense
              round
              icon="arrow_back"
              :to="{ name: 'liquidacao-titulo' }"
              aria-label="Voltar"
            />
          </q-item-section>
          <q-item-section>
            <div class="text-h4 text-grey-9">Liquidação {{ formatCodigo(liq.codliquidacaotitulo) }}</div>
            <div v-if="estornado" class="text-negative">
              Estornada em {{ formataDataSemHora(liq.estornado) }}
            </div>
            <div v-if="fechadaPeriodo" class="text-orange-8">
              Fechada em período RH #{{ liq.codperiodo }}
            </div>
          </q-item-section>
        </q-item>

        <!-- Cards resumo -->
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
                  :label="liq.fantasia"
                  :href="urlPessoa(liq.codpessoa)"
                  target="_blank"
                />
              </div>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Transação</div>
              <div class="text-h6 text-grey-7">{{ formataDataSemHora(liq.transacao) }}</div>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-2">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Portador</div>
              <div class="text-body1">{{ liq.portador }}</div>
            </q-card>
          </div>
          <div class="col-xs-6 col-sm-3">
            <q-card bordered flat class="q-py-sm text-center">
              <div class="text-caption text-grey-7">Total</div>
              <div
                class="text-h6"
                :class="liq.operacao === 'CR' ? 'text-orange' : 'text-green'"
              >
                {{ formataNumero(liq.valor) }} {{ liq.operacao }}
              </div>
            </q-card>
          </div>
        </div>

        <!-- Detalhes + ações -->
        <q-card bordered flat>
          <q-card-section class="text-grey-9 text-overline row items-center">
            DETALHES DA LIQUIDAÇÃO
            <q-space />
            <q-btn
              flat
              round
              dense
              icon="receipt"
              size="sm"
              color="grey-7"
              @click="abrirRecibo('recibo')"
              v-if="!estornado"
            >
              <q-tooltip>Recibo</q-tooltip>
            </q-btn>
            <q-btn
              v-if="!estornado && liq.credito > 0"
              flat
              round
              dense
              icon="south_west"
              size="sm"
              color="grey-7"
              @click="abrirRecibo('recibo-recebimento')"
            >
              <q-tooltip>Recibo Recebimento</q-tooltip>
            </q-btn>
            <q-btn
              v-if="!estornado && liq.debito > 0"
              flat
              round
              dense
              icon="north_east"
              size="sm"
              color="grey-7"
              @click="abrirRecibo('recibo-pagamento')"
            >
              <q-tooltip>Recibo Pagamento</q-tooltip>
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
              :usuariocriacao="liq.usuariocriacao"
              :criacao="liq.criacao"
              :usuarioalteracao="liq.usuarioalteracao"
              :alteracao="liq.alteracao"
            />
          </q-card-section>
          <template v-if="liq.observacao">
            <q-separator />
            <q-card-section>
              <div class="text-overline text-grey-7">Observação</div>
              <div class="text-body2 bg-grey-2 rounded-borders q-pa-sm" style="white-space: pre-line">
                {{ liq.observacao }}
              </div>
            </q-card-section>
          </template>
        </q-card>

        <!-- Títulos baixados -->
        <q-card bordered flat class="q-mt-md">
          <q-card-section class="text-grey-9 text-overline">
            TÍTULOS BAIXADOS ({{ liq.movimentos?.length || 0 }})
          </q-card-section>
          <q-list separator v-if="liq.movimentos?.length">
            <q-item v-for="m in liq.movimentos" :key="m.codmovimentotitulo">
              <q-item-section style="flex: 0 0 90px; min-width: 0" class="gt-xs">
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
                    :to="{ name: 'titulo-detalhe', params: { codtitulo: m.codtitulo } }"
                  />
                </q-item-label>
                <q-item-label caption>{{ m.tipomovimentotitulo }}</q-item-label>
                <q-item-label caption v-if="m.titulo?.boleto">
                  Boleto {{ m.titulo?.nossonumero }}
                </q-item-label>
              </q-item-section>
              <q-item-section style="flex: 0 0 130px" class="gt-xs">
                <q-item-label caption>
                  {{ formataDataSemHora(m.titulo?.vencimento) }}
                </q-item-label>
                <q-item-label caption>{{ m.titulo?.fantasia }}</q-item-label>
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
          <q-card-section v-else class="text-center text-grey-6 q-pa-md">
            Nenhum título baixado
          </q-card-section>
        </q-card>
      </template>
    </div>

    <q-inner-loading :showing="loading" color="primary" />
  </q-page>
</template>
