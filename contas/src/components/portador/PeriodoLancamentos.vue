<script setup>
// Lançamentos do período (doc-4, R10) como extrato: agrupados por dia, uma linha do tempo à
// esquerda (hora e o ícone da origem) e, à direita, o valor e o saldo corrente sempre na mesma
// coluna (no celular, o saldo embaixo do valor). Transferência a confirmar em amarelo; cancelado
// riscado e fora do saldo. A linha abre o pagamento; a transferência leva ao outro portador, no
// período onde o valor caiu; as ações do lançamento (confirmar/cancelar transferência, cancelar
// avulso) ficam nele.
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import {
  formataNumero,
  formataData,
  formataDataCompleta,
  formataHora,
} from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'

const $q = useQuasar()
const store = periodoStore()
const { portador, periodo, filtroOrigem } = storeToRefs(store)

// os cancelados (riscados) só aparecem no toggle
const mostrarCancelados = ref(false)
const cancelados = computed(
  () => (periodo.value?.lancamentos ?? []).filter((l) => l.inativo).length,
)
const lista = computed(() =>
  (periodo.value?.lancamentos ?? []).filter(
    (l) =>
      (!filtroOrigem.value || l.origem === filtroOrigem.value) &&
      (mostrarCancelados.value || !l.inativo),
  ),
)
const filtro = computed(
  () => periodo.value?.resumo?.find((r) => r.origem === filtroOrigem.value)?.descricao,
)

// os lançamentos (já em ordem de transação) agrupados por dia
const dias = computed(() => {
  const ret = []
  for (const l of lista.value) {
    const data = formataData(l.transacao)
    if (ret.at(-1)?.data !== data) {
      const extenso = formataDataCompleta(l.transacao)
      ret.push({ data, titulo: extenso.charAt(0).toUpperCase() + extenso.slice(1), linhas: [] })
    }
    ret.at(-1).linhas.push(l)
  }
  return ret
})

// a origem do lançamento (V venda, T títulos, X transferência, A avulso, I item do caixa)
const ICONE = {
  V: 'shopping_cart',
  T: 'request_quote',
  X: 'swap_horiz',
  A: 'edit_note',
  I: 'inventory_2',
}

const temSaldoColuna = computed(() => $q.screen.gt.xs)

const cancelado = (l) => !!l.inativo
const pendente = (l) => !cancelado(l) && l.estado === 'P'
const corAvatar = (l) =>
  cancelado(l) ? 'grey-2' : pendente(l) ? 'amber-2' : l.valor < 0 ? 'red-1' : 'green-1'
const corIcone = (l) =>
  cancelado(l) ? 'grey-5' : pendente(l) ? 'amber-9' : l.valor < 0 ? 'red-8' : 'green-8'
const corValor = (l) =>
  cancelado(l) ? 'text-strike text-grey-5' : l.valor < 0 ? 'text-red-8' : 'text-green-8'
const valor = (l) => (l.valor > 0 ? '+' : '') + formataNumero(l.valor)

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
  if (!portador.value.ehCaixa) {
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
  <q-card v-if="periodo" flat bordered>
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
      <q-toggle
        v-if="cancelados"
        v-model="mostrarCancelados"
        :label="`Mostrar cancelados (${cancelados})`"
        color="primary"
      />
    </q-card-section>

    <q-card-section v-if="lista.length" class="q-pt-none">
      <!-- cabeçalho das colunas: o mesmo esqueleto da linha, para alinhar -->
      <div v-if="temSaldoColuna" class="row no-wrap text-caption text-grey-7">
        <div style="width: 92px" />
        <div class="col row no-wrap q-px-sm">
          <div class="col" />
          <div class="text-right q-pl-md" style="width: 112px">Valor</div>
          <div class="text-right q-pl-md" style="width: 112px">Saldo</div>
        </div>
      </div>

      <div v-for="dia in dias" :key="dia.data" class="q-mt-sm">
        <div class="text-caption text-weight-bold text-grey-8 q-py-xs">{{ dia.titulo }}</div>

        <div v-for="(l, i) in dia.linhas" :key="l.codportadormovimento" class="row no-wrap">
          <!-- hora -->
          <div
            class="row items-center justify-end text-caption text-grey-7 q-pr-sm"
            style="width: 48px; height: 56px"
          >
            {{ formataHora(l.transacao) }}
          </div>

          <!-- trilho: o ícone da origem, ligado ao anterior e ao próximo do dia -->
          <div class="column items-center no-wrap" style="width: 36px">
            <div style="width: 2px; height: 10px" :class="i ? 'bg-grey-3' : ''" />
            <q-avatar
              size="36px"
              font-size="20px"
              :color="corAvatar(l)"
              :text-color="corIcone(l)"
              :icon="ICONE[l.origem]"
            />
            <div
              class="col"
              style="width: 2px; min-height: 10px"
              :class="i < dia.linhas.length - 1 ? 'bg-grey-3' : ''"
            />
          </div>

          <!-- o lançamento -->
          <q-item
            clickable
            :to="{ name: 'pagamento-detalhe', params: { id: l.codpagamento } }"
            class="col rounded-borders q-px-sm q-ml-sm q-mb-xs"
            :class="pendente(l) ? 'bg-amber-1' : ''"
            style="min-height: 56px"
          >
            <q-item-section>
              <q-item-label :class="cancelado(l) ? 'text-strike text-grey-6' : ''">
                {{ l.texto }}
              </q-item-label>
              <q-item-label caption class="row items-center q-gutter-x-xs">
                <span>{{ l.meiodescricao }}</span>
                <q-badge v-if="pendente(l)" color="amber-8" label="a confirmar" />
                <q-badge v-if="cancelado(l)" color="grey-5" label="cancelado" />
                <q-btn
                  v-if="l.contraparte"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="open_in_new"
                  :to="{
                    name: 'portador-detalhe',
                    params: {
                      codportador: l.contraparte.codportador,
                      codportadorperiodo: l.contraparte.codportadorperiodo,
                    },
                  }"
                  @click.stop
                >
                  <q-tooltip>Ver em {{ l.contraparte.portador }}</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="l.podeConfirmar"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="done"
                  @click.stop.prevent="store.confirmarTransferencia(l.codpagamento)"
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
                  @click.stop.prevent="cancelarTransferencia(l)"
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
                  @click.stop.prevent="cancelarAvulso(l)"
                >
                  <q-tooltip>Cancelar o lançamento</q-tooltip>
                </q-btn>
              </q-item-label>
            </q-item-section>

            <q-item-section side top style="width: 112px">
              <q-item-label class="text-weight-bold" :class="corValor(l)">
                {{ valor(l) }}
              </q-item-label>
              <q-item-label v-if="!temSaldoColuna && l.saldo !== null" caption>
                saldo {{ formataNumero(l.saldo) }}
              </q-item-label>
            </q-item-section>

            <q-item-section v-if="temSaldoColuna" side top style="width: 112px">
              <q-item-label :class="l.saldo < 0 ? 'text-red-8' : 'text-grey-8'">
                {{ l.saldo !== null ? formataNumero(l.saldo) : '' }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </div>
      </div>
    </q-card-section>

    <MgEmptyState v-else plain icon="receipt_long">Nenhum lançamento.</MgEmptyState>
  </q-card>
</template>
