<script setup>
// Receber título / Pagar vale no PDV (F11), pelo teclado (M7 do plano doc-3, no M6.1).
// Pessoa ou número de um título → títulos abertos e créditos dela (↑/↓ andam, Espaço marca) →
// Enter abre o wizard de cobrança no sentido do líquido (recebe a notinha; paga o vale). Cada
// forma lançada aparece embaixo; quando zera, grava e imprime o recibo. Pagou menos: "Finalizar
// parcial" baixa só o que foi pago, por vencimento.
import { ref, computed, watch, nextTick } from 'vue'
import { pagamentoStore } from 'stores/pagamento'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { formataData, formataNumero } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import SelectPessoa from 'components/selects/SelectPessoa.vue'

const sPagamento = pagamentoStore()
const sBaixa = baixaTitulosStore()

const cardRef = ref(null)
const itensRef = ref([])
const indice = ref(0)

const lancou = computed(() => sBaixa.pagamentos.length > 0)

// líquido do que está marcado (antes de lançar) ou da baixa em andamento
const liquido = computed(() => {
  if (lancou.value) {
    return sBaixa.liquido
  }
  return sPagamento.selecionados.reduce((s, t) => s + (t.operacao === 'DB' ? 1 : -1) * t.total, 0)
})
const verbo = computed(() => (liquido.value >= 0 ? 'Receber' : 'Pagar'))

const focar = () => nextTick(() => cardRef.value?.$el?.focus())

const buscar = async () => {
  await sPagamento.buscar()
  indice.value = 0
  focar()
}

const mover = (delta) => {
  const total = sPagamento.titulos.length
  if (!total) return
  indice.value = (indice.value + delta + total) % total
  nextTick(() => itensRef.value[indice.value]?.$el?.scrollIntoView({ block: 'nearest' }))
}

const alternar = (i) => {
  if (lancou.value) return
  const t = sPagamento.titulos[i]
  if (t) t.selecionado = !t.selecionado
}

const clicar = (i) => {
  indice.value = i
  alternar(i)
}

const receber = () => {
  if (!sPagamento.selecionados.length && !lancou.value) return
  sPagamento.receber()
}

const voltar = () => {
  if (sPagamento.etapa === 'titulos' && !lancou.value) {
    sPagamento.etapa = 'busca'
    return
  }
  sPagamento.dialog = false
}

// pagou menos que o líquido: baixa só o que foi pago
const finalizarParcial = async () => {
  sBaixa.ajustarAoPago()
  await sPagamento.finalizar()
}

// lançou tudo: grava sozinho
watch(
  () => [sBaixa.saldo, sBaixa.pagamentos.length],
  async ([saldo, lancados]) => {
    if (sPagamento.dialog && lancados > 0 && Math.abs(saldo) < 0.005) {
      await sPagamento.finalizar()
    }
  },
)

// limpa a baixa ao abrir de novo
watch(
  () => sPagamento.dialog,
  (aberto) => {
    if (aberto) {
      sBaixa.iniciar({ pessoa: null, titulos: [] })
    }
  },
)

const tecla = (e) => {
  if (sPagamento.etapa !== 'titulos') {
    if (e.key === 'Escape') {
      voltar()
      e.preventDefault()
    }
    return
  }
  let tratada = true
  switch (e.key) {
    case 'ArrowDown':
      mover(1)
      break
    case 'ArrowUp':
      mover(-1)
      break
    case ' ':
      alternar(indice.value)
      break
    case 'Enter':
      receber()
      break
    case 'Escape':
      voltar()
      break
    default:
      tratada = false
  }
  if (tratada) {
    e.preventDefault()
    e.stopPropagation()
  }
}
</script>

<template>
  <q-dialog v-model="sPagamento.dialog" no-esc-dismiss @show="focar">
    <q-card
      flat
      ref="cardRef"
      tabindex="0"
      class="no-outline column no-wrap"
      style="width: 640px; max-width: 95vw; max-height: 85vh"
      @keydown="tecla"
    >
      <q-card-section class="col-auto">
        <div class="text-h6">Receber Título / Pagar Vale</div>
        <div class="text-subtitle2 text-grey-7" v-if="sPagamento.pessoa">
          {{ sPagamento.pessoa.fantasia }}
        </div>
      </q-card-section>

      <!-- BUSCA -->
      <q-card-section v-if="sPagamento.etapa === 'busca'" class="col-auto">
        <div class="row q-col-gutter-md">
          <div class="col-12">
            <select-pessoa
              v-model="sPagamento.codpessoa"
              label="Cliente"
              autofocus
              @update:model-value="buscar"
            />
          </div>
          <div class="col-12">
            <MgInput
              v-model="sPagamento.numero"
              label="ou o número do título"
              hint="Enter busca"
              @keydown.enter.stop="buscar"
            />
          </div>
        </div>
        <q-inner-loading :showing="sPagamento.buscando" />
      </q-card-section>

      <!-- TÍTULOS -->
      <template v-else>
        <q-card-section class="col scroll q-pt-none">
          <div v-if="!sPagamento.titulos.length" class="text-grey-6 text-italic q-pa-md">
            Nenhum título aberto nem crédito para esta pessoa.
          </div>
          <q-list separator>
            <q-item
              v-for="(t, i) in sPagamento.titulos"
              :key="t.codtitulo"
              :ref="(el) => (itensRef[i] = el)"
              clickable
              :active="i === indice"
              active-class="bg-blue-1"
              @click="clicar(i)"
            >
              <q-item-section avatar>
                <q-checkbox
                  :model-value="t.selecionado"
                  :disable="lancou"
                  tabindex="-1"
                  @update:model-value="alternar(i)"
                />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ t.numero }}</q-item-label>
                <q-item-label caption>
                  vence {{ formataData(t.vencimento) }} · {{ t.filial }}
                  <template v-if="t.juros || t.multa">
                    · juros/multa {{ formataNumero(t.juros + t.multa) }}
                  </template>
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-item-label
                  class="text-weight-bold"
                  :class="t.operacao === 'DB' ? 'text-orange-9' : 'text-green-8'"
                >
                  {{ formataNumero(t.total) }}
                </q-item-label>
                <q-item-label caption>{{
                  t.operacao === 'DB' ? 'a receber' : 'crédito'
                }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-separator />

        <q-card-section class="col-auto">
          <div class="row items-center">
            <div class="col text-subtitle1">{{ verbo }}</div>
            <div class="text-h5 text-weight-bold">R$ {{ formataNumero(Math.abs(liquido)) }}</div>
          </div>
          <div
            v-for="(p, i) in sBaixa.pagamentos"
            :key="i"
            class="row items-center text-body2 text-grey-8"
          >
            <div class="col">{{ p.descricao }}</div>
            <div>R$ {{ formataNumero(p.total) }}</div>
            <q-btn
              v-if="!p.codpagamento"
              flat
              round
              size="sm"
              icon="close"
              color="grey-7"
              tabindex="-1"
              @click="sBaixa.remover(i)"
            />
          </div>
          <div v-if="lancou" class="row items-center text-subtitle1 text-orange-10">
            <div class="col">Falta</div>
            <div>R$ {{ formataNumero(sBaixa.saldo) }}</div>
          </div>
          <div class="text-caption text-grey-6">
            ↑/↓ andam · Espaço marca · Enter {{ verbo.toLowerCase() }} · Esc volta
          </div>
        </q-card-section>
      </template>

      <q-card-actions align="right" class="col-auto">
        <q-btn flat color="grey-8" label="Voltar (Esc)" tabindex="-1" @click="voltar" />
        <q-btn
          v-if="lancou && sBaixa.entrada && sBaixa.saldo > 0"
          flat
          color="primary"
          label="Finalizar parcial"
          tabindex="-1"
          :loading="sBaixa.finalizando"
          @click="finalizarParcial"
        />
        <q-btn
          v-if="sPagamento.etapa === 'titulos'"
          flat
          color="primary"
          :label="verbo + ' (Enter)'"
          tabindex="-1"
          :disable="!sPagamento.selecionados.length && !lancou"
          @click="receber"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
