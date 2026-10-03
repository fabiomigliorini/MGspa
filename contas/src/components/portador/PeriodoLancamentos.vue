<script setup>
// Lançamentos do período (doc-4, R10) numa linha do tempo: entradas à esquerda, saídas à direita
// (no celular, uma coluna só). Data e hora do fato, a origem em texto, o meio, o valor e o saldo
// corrente. Transferência a confirmar em amarelo; cancelado riscado e fora do saldo. O lançamento
// abre o pagamento; a transferência leva ao outro portador, no período onde o valor caiu; as
// ações do lançamento (confirmar/cancelar transferência, cancelar avulso) ficam nele.
import { computed } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import { formataNumero, formataTimestamp } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'

const $q = useQuasar()
const store = periodoStore()
const { portador, periodo, filtroOrigem } = storeToRefs(store)

const lista = computed(() =>
  (periodo.value?.lancamentos ?? []).filter(
    (l) => !filtroOrigem.value || l.origem === filtroOrigem.value,
  ),
)
const filtro = computed(
  () => periodo.value?.resumo?.find((r) => r.origem === filtroOrigem.value)?.descricao,
)

// a origem do lançamento (V venda, T títulos, X transferência, A avulso, I item do caixa)
const ICONE = {
  V: 'shopping_cart',
  T: 'request_quote',
  X: 'swap_horiz',
  A: 'edit_note',
  I: 'inventory_2',
}

const layout = computed(() => ($q.screen.lt.sm ? 'dense' : 'loose'))

const riscado = (l) => !!l.inativo
const corEntrada = (l) =>
  riscado(l) ? 'grey-5' : l.estado === 'P' ? 'amber-8' : l.valor < 0 ? 'red-7' : 'green-7'
const subtitulo = (l) =>
  formataTimestamp(l.transacao, 0) +
  (riscado(l) ? ' · cancelado' : l.estado === 'P' ? ' · a confirmar' : '')
const cor = (l) =>
  riscado(l) ? 'text-strike text-grey-6' : l.valor < 0 ? 'text-red-8' : 'text-green-8'

const justificar = (title, ok, fn) =>
  $q
    .dialog({
      title,
      message: 'Motivo:',
      prompt: {
        model: '',
        type: 'text',
        outlined: true,
        isValid: (v) => (v || '').trim().length >= 5,
      },
      cancel: { label: 'Voltar', color: 'grey-8', flat: true },
      ok: { label: ok, color: 'negative', flat: true },
    })
    .onOk(fn)

function cancelarTransferencia(l) {
  justificar('Cancelar transferência', 'Cancelar transferência', (j) =>
    store.cancelarTransferencia(l.codpagamento, j),
  )
}

function cancelarAvulso(l) {
  if (!portador.value.ehGaveta) {
    justificar('Cancelar lançamento', 'Cancelar lançamento', (j) =>
      store.cancelarAvulso(l.codpagamento, j),
    )
    return
  }
  $q.dialog({
    title: 'Cancelar lançamento',
    message: `Cancelar "${l.texto}" de R$ ${formataNumero(l.valor)}?`,
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Cancelar lançamento', color: 'negative', flat: true },
  }).onOk(() => store.cancelarAvulso(l.codpagamento))
}
</script>

<template>
  <q-card v-if="periodo" flat bordered class="q-mb-md">
    <q-card-section class="row items-center q-pb-sm">
      <div class="col text-subtitle1 text-weight-medium">Lançamentos</div>
      <q-chip
        v-if="filtro"
        removable
        color="blue-1"
        text-color="primary"
        icon="filter_alt"
        :label="filtro"
        @remove="filtroOrigem = null"
      />
    </q-card-section>
    <q-card-section v-if="lista.length" class="q-pt-none">
      <div v-if="layout !== 'dense'" class="row text-caption text-grey-7 q-mb-sm">
        <div class="col text-right q-pr-xl">Entradas</div>
        <div class="col q-pl-xl">Saídas</div>
      </div>
      <q-timeline :layout="layout" color="grey-5">
        <q-timeline-entry
          v-for="l in lista"
          :key="l.codportadormovimento"
          :side="l.valor > 0 ? 'left' : 'right'"
          :color="corEntrada(l)"
          :icon="ICONE[l.origem]"
          :subtitle="subtitulo(l)"
        >
          <q-item
            clickable
            :to="{ name: 'pagamento-detalhe', params: { id: l.codpagamento } }"
            class="q-px-sm rounded-borders"
            :class="!riscado(l) && l.estado === 'P' ? 'bg-amber-1' : ''"
          >
            <q-item-section>
              <q-item-label :class="riscado(l) ? 'text-strike text-grey-6' : ''">
                {{ l.texto }}
              </q-item-label>
              <q-item-label caption>{{ l.meiodescricao }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label class="text-weight-bold" :class="cor(l)">
                {{ formataNumero(l.valor) }}
              </q-item-label>
              <q-item-label v-if="l.saldo !== null" caption>
                saldo {{ formataNumero(l.saldo) }}
              </q-item-label>
            </q-item-section>
          </q-item>
          <div
            v-if="l.contraparte || l.podeConfirmar || l.podeCancelar || l.podeCancelarAvulso"
            class="row items-center q-gutter-xs q-mt-xs"
            :class="l.valor > 0 && layout !== 'dense' ? 'justify-end' : ''"
          >
            <q-btn
              v-if="l.contraparte"
              flat
              no-caps
              size="sm"
              color="primary"
              icon="open_in_new"
              :label="`Ver em ${l.contraparte.portador}`"
              :to="{
                name: 'portador-detalhe',
                params: {
                  codportador: l.contraparte.codportador,
                  codportadorperiodo: l.contraparte.codportadorperiodo,
                },
              }"
            />
            <q-btn
              v-if="l.podeConfirmar"
              flat
              round
              size="sm"
              color="grey-7"
              icon="done"
              @click="store.confirmarTransferencia(l.codpagamento)"
            >
              <q-tooltip>Confirmar o recebimento</q-tooltip>
            </q-btn>
            <q-btn
              v-if="l.podeCancelar"
              flat
              round
              size="sm"
              color="grey-7"
              icon="block"
              @click="cancelarTransferencia(l)"
            >
              <q-tooltip>Cancelar a transferência</q-tooltip>
            </q-btn>
            <q-btn
              v-if="l.podeCancelarAvulso"
              flat
              round
              size="sm"
              color="grey-7"
              icon="delete"
              @click="cancelarAvulso(l)"
            >
              <q-tooltip>Cancelar o lançamento</q-tooltip>
            </q-btn>
          </div>
        </q-timeline-entry>
      </q-timeline>
    </q-card-section>
    <MgEmptyState v-else plain icon="receipt_long">Nenhum lançamento.</MgEmptyState>
  </q-card>
</template>
