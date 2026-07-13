<script setup>
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useContratoDetalheStore } from 'src/stores/contratoDetalhe'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { formataNumero, formataReal, formataData } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import RecebimentoDialog from 'components/RecebimentoDialog.vue'

// Card "Parcelas de pagamento": um recebível por FIXAÇÃO. TODA a lógica (a receber
// na moeda, pode receber?, status, %) vem PRONTA do backend (ContratoFixacaoResource);
// aqui só pinta. Em US$ a parcela aparece com o dólar a receber; recebe-se em R$
// quando o câmbio está travado. Dá baixa em N recebimentos e pode QUITAR.
const $q = useQuasar()
const store = useContratoDetalheStore()
const { fixacoes } = storeToRefs(store)

function n(v) {
  return Number(v) || 0
}
const fmt = (v, d = 0) => formataNumero(v, d)
const rs = formataReal
const fmtData = formataData
const simbolo = store.simboloMoeda

function recebimentosDe(f) {
  return (f.ContratoPagamentoS || []).filter((p) => !p.inativo)
}
// Só a apresentação dos status que vêm prontos do backend (statusrecebimento).
const STATUS = {
  RECEBIDA: { label: 'Recebida', color: 'green-6' },
  PARCIAL: { label: 'Parcial', color: 'orange-7' },
  ABERTO: { label: 'A receber', color: 'blue-grey-5' },
  AGUARDANDO_CAMBIO: { label: 'Aguardando câmbio', color: 'teal-6' },
}
function status(f) {
  return STATUS[f.statusrecebimento] || STATUS.ABERTO
}

// ---- Recebimento ----
const dialog = ref(false)
const dialogFixacao = ref(null)
const dialogRecebimento = ref(null)
function registrar(f) {
  dialogFixacao.value = f
  dialogRecebimento.value = null
  dialog.value = true
}
function editar(f, r) {
  dialogFixacao.value = f
  dialogRecebimento.value = r
  dialog.value = true
}
function excluir(f, r) {
  $q.dialog({
    title: 'Excluir',
    message: 'Excluir este recebimento?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await store.excluirRecebimento(f.codcontratofixacao, r)
      notifySuccess('Excluído!')
    } catch (e) {
      notifyError(e)
    }
  })
}
async function quitar(f, v) {
  try {
    await store.quitarFixacao(f.codcontratofixacao, v)
    notifySuccess(v ? 'Fixação quitada!' : 'Fixação reaberta!')
  } catch (e) {
    notifyError(e)
  }
}
</script>

<template>
  <q-card flat bordered class="q-mb-md">
    <q-item>
      <q-item-section>
        <q-item-label class="text-subtitle1">Parcelas de pagamento</q-item-label>
      </q-item-section>
    </q-item>
    <q-separator />

    <q-card-section>
      <MgEmptyState v-if="!fixacoes.length" plain icon="payments">
        Nenhuma fixação lançada — sem parcelas a receber.
      </MgEmptyState>

      <div v-else class="row q-col-gutter-md">
        <div v-for="f in fixacoes" :key="f.codcontratofixacao" class="col-12 col-sm-6 col-md-4">
          <q-card
            flat
            bordered
            class="full-height"
            :style="`border-left: 3px solid ${f.estrangeira ? '#0d9488' : '#16a34a'}`"
          >
            <!-- Cabeçalho: fixação + status -->
            <q-card-section class="q-pb-none">
              <div class="row items-start justify-between no-wrap">
                <div class="col">
                  <div class="text-subtitle1 text-weight-bold">
                    {{ fmt(f.quantidade) }}
                    <span class="text-caption text-grey-6 text-weight-regular">
                      sc · {{ simbolo(f.moeda) }} {{ fmt(f.preco, 2) }}/sc</span
                    >
                  </div>
                  <div v-if="f.datavencimento" class="text-caption text-grey-7">
                    vence {{ fmtData(f.datavencimento) }}
                  </div>
                </div>
                <q-chip
                  :color="status(f).color"
                  text-color="white"
                  :label="status(f).label"
                  square
                  class="q-my-none"
                />
              </div>
            </q-card-section>

            <q-card-section class="q-pt-sm">
              <!-- A receber (na moeda da fixação) -->
              <div class="text-body1 text-weight-bold">
                A receber {{ simbolo(f.moeda) }} {{ fmt(f.arecebermoeda, 2) }}
              </div>

              <!-- US$: contexto do câmbio -->
              <template v-if="f.estrangeira">
                <div v-if="!f.podereceber" class="text-caption text-orange-8 q-mt-xs">
                  Câmbio não travado — trave o câmbio pra receber em R$.
                </div>
                <div v-else class="text-caption text-grey-7 q-mt-xs">
                  Travado {{ rs(f.liquidobrl) }} líquido<span v-if="f.saldomoeda > 0.005">
                    · falta travar US$ {{ fmt(f.saldomoeda, 2) }}</span
                  >
                </div>
              </template>

              <!-- Recebimento em R$ (só quando há R$ a receber) -->
              <template v-if="f.podereceber">
                <div class="row items-baseline justify-between text-body2 q-mt-sm">
                  <span class="text-grey-7">
                    Recebido <b class="text-green-8">{{ rs(f.recebido) }}</b>
                  </span>
                  <span v-if="!f.quitado" class="text-grey-7">Saldo {{ rs(f.saldoreceber) }}</span>
                </div>
                <q-linear-progress
                  :value="n(f.percentualrecebido) / 100"
                  color="green-6"
                  track-color="grey-3"
                  size="6px"
                  rounded
                  class="q-my-sm"
                />
                <div
                  v-if="f.quitado"
                  class="text-caption"
                  :class="n(f.diferenca) >= 0 ? 'text-green-8' : 'text-orange-8'"
                >
                  Diferença {{ n(f.diferenca) >= 0 ? '+' : '−' }}{{ rs(Math.abs(n(f.diferenca))) }}
                  (impostos)
                </div>

                <!-- Recebimentos -->
                <div
                  v-for="r in recebimentosDe(f)"
                  :key="r.codcontratopagamento"
                  class="row items-center no-wrap bg-grey-2 rounded-borders q-px-sm q-py-xs q-mt-sm"
                >
                  <q-icon name="check" size="14px" class="text-green-7 q-mr-xs" />
                  <span class="col text-caption text-grey-8">
                    {{ fmtData(r.data) }} · <b>{{ rs(r.valor) }}</b>
                    <span v-if="r.Portador"> · {{ r.Portador.portador }}</span>
                  </span>
                  <q-btn flat round size="xs" color="grey-6" icon="edit" @click="editar(f, r)" />
                  <q-btn flat round size="xs" color="grey-6" icon="delete" @click="excluir(f, r)" />
                </div>

                <!-- Ações -->
                <div class="row items-center q-gutter-sm q-mt-sm">
                  <q-btn
                    v-if="!f.quitado"
                    flat
                    no-caps
                    size="sm"
                    color="primary"
                    icon="add"
                    label="Registrar recebimento"
                    @click="registrar(f)"
                  />
                  <q-btn
                    v-if="!f.quitado && f.recebido > 0"
                    flat
                    no-caps
                    size="sm"
                    color="green-7"
                    icon="done_all"
                    label="Quitar"
                    @click="quitar(f, true)"
                  />
                  <q-btn
                    v-if="f.quitado"
                    flat
                    no-caps
                    size="sm"
                    color="grey-7"
                    icon="lock_open"
                    label="Reabrir"
                    @click="quitar(f, false)"
                  />
                </div>
              </template>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </q-card-section>

    <RecebimentoDialog
      v-model="dialog"
      :fixacao="dialogFixacao || {}"
      :recebimento="dialogRecebimento"
    />
  </q-card>
</template>
