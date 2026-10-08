<script setup>
// Resumo do período (doc-4), dentro do card do PeriodoCabecalho. Na espécie, no jeito do
// formulário "Movimento do Caixa" de papel: Moedas, Cédulas e cada item do caixa numa linha, a
// entrada é o contado no começo (a contagem que deu o saldo inicial) e a saída o contado no fim
// (o item com a movimentação dele embaixo); depois as origens (vendas, títulos e vales,
// transferências, ajustes) e no pé o Total e a Diferença, verde dentro da tolerância do portador.
// A calculadora ao lado do valor abre a contagem só daquele bloco (fechado, só para ver); a
// inicial só confere: azul bate, roxo diverge. Fora da espécie: saldo
// inicial, origens e saldo final. Tudo gravado pelo servidor. Clicar numa origem filtra a lista.
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { formataNumero } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'

const emit = defineEmits(['contar'])

const store = periodoStore()
const { periodo, filtroOrigem } = storeToRefs(store)

const podeContar = computed(() => !!periodo.value?.contagem)
const contado = (momento) => {
  const c = periodo.value?.contagem?.[momento]
  return c?.contado != null ? c : null
}
const sinal = (v) => (v > 0 ? '+' : '') + formataNumero(v)
const naTolerancia = (d) => Math.abs(d ?? 0) <= (periodo.value?.tolerancia ?? 0)
const cor = (v) => (v < 0 ? 'text-red-8' : v > 0 ? 'text-green-8' : 'text-grey-7')
const filtrar = (origem) => (filtroOrigem.value = filtroOrigem.value === origem ? null : origem)
const valor = (v) => (v == null ? '' : formataNumero(v))

// os totais da contagem que deu o saldo inicial; sem ela, a linha "Saldo inicial"
const abertura = computed(() => periodo.value?.contagem?.abertura ?? null)

// o quadro da espécie, montado pelo servidor (o mesmo do borderô), na ordem do papel: Moedas e
// Cédulas; por item do caixa, o título, "Abertura e fechamento" e a "Movimentação"; as origens, com
// as maquinetas de parceiros sob "Parceiros", uma linha cada. `bloco`: o ícone ao lado do valor
// abre a contagem só daquele bloco; `confere`: a contagem inicial do bloco bate com a abertura
// (azul) ou não (roxo, "Divergente"); null sem contagem inicial
const quadro = computed(() => periodo.value?.quadro ?? null)
const linhasEspecie = computed(() => quadro.value?.linhas ?? [])
const corConfere = (c) => (c === true ? 'text-blue-8' : c === false ? 'text-purple-8' : '')

// as origens, fora da espécie (na espécie já estão no quadro)
const resumo = computed(() => (quadro.value ? [] : (periodo.value.resumo ?? [])))

const saldoInicialEntrada = computed(() =>
  abertura.value ? null : Math.max(periodo.value.saldoinicial, 0),
)
const saldoInicialSaida = computed(() =>
  abertura.value ? null : Math.max(-periodo.value.saldoinicial, 0),
)
</script>

<template>
  <template v-if="periodo">
    <q-separator />
    <q-markup-table flat separator="horizontal" wrap-cells>
      <thead>
        <tr>
          <th class="text-left">Resumo</th>
          <th class="text-right">{{ podeContar ? 'Entrada' : 'Entradas' }}</th>
          <th class="text-right">{{ podeContar ? 'Saída' : 'Saídas' }}</th>
        </tr>
      </thead>
      <tbody>
        <!-- positivo é entrada, negativo é saída -->
        <tr v-if="!abertura">
          <td>Saldo inicial</td>
          <td class="text-right">
            {{ periodo.saldoinicial >= 0 ? valor(saldoInicialEntrada) : '' }}
          </td>
          <td class="text-right text-red-8">
            {{ periodo.saldoinicial < 0 ? valor(saldoInicialSaida) : '' }}
          </td>
        </tr>
        <template v-for="l in linhasEspecie" :key="l.chave">
          <tr v-if="l.tipo === 'titulo'">
            <td colspan="3" class="text-weight-medium">{{ l.descricao }}</td>
          </tr>
          <tr v-else-if="l.tipo === 'contagem'">
            <td>
              <div :class="l.recuo ? 'q-pl-md' : ''">{{ l.descricao }}</div>
            </td>
            <td class="text-right">
              <q-btn
                v-if="l.bloco"
                flat
                round
                size="sm"
                color="grey-7"
                icon="calculate"
                class="q-mr-xs"
                @click="emit('contar', 'inicial', l.bloco)"
              >
                <q-tooltip>Contagem inicial de {{ l.nome }} (só confere)</q-tooltip>
              </q-btn>
              <span :class="corConfere(l.confere)">{{ valor(l.entrada) }}</span>
              <div v-if="l.confere === false" class="text-caption text-grey-7">Divergente</div>
            </td>
            <td class="text-right">
              <q-btn
                v-if="l.bloco"
                flat
                round
                size="sm"
                color="grey-7"
                icon="calculate"
                class="q-mr-xs"
                @click="emit('contar', 'final', l.bloco)"
              >
                <q-tooltip>Contagem final de {{ l.nome }}</q-tooltip>
              </q-btn>
              {{ valor(l.saida) }}
            </td>
          </tr>
          <tr
            v-else
            class="cursor-pointer"
            :class="filtroOrigem === l.filtro ? 'bg-blue-1' : ''"
            @click="filtrar(l.filtro)"
          >
            <td>
              <div :class="l.recuo ? 'q-pl-md' : ''">
                {{ l.descricao }} <span class="text-grey-7">({{ l.quantidade }})</span>
                <q-icon
                  v-if="filtroOrigem === l.filtro"
                  name="filter_alt"
                  color="primary"
                  class="q-ml-xs"
                />
              </div>
            </td>
            <td class="text-right" :class="cor(l.entrada)">{{ formataNumero(l.entrada) }}</td>
            <td class="text-right" :class="cor(-l.saida)">{{ formataNumero(l.saida) }}</td>
          </tr>
        </template>
        <tr
          v-for="r in resumo"
          :key="r.origem"
          class="cursor-pointer"
          :class="filtroOrigem === r.origem ? 'bg-blue-1' : ''"
          @click="filtrar(r.origem)"
        >
          <td>
            {{ r.descricao }} <span class="text-grey-7">({{ r.quantidade }})</span>
            <q-icon
              v-if="filtroOrigem === r.origem"
              name="filter_alt"
              color="primary"
              class="q-ml-xs"
            />
          </td>
          <td class="text-right" :class="cor(r.entrada)">{{ formataNumero(r.entrada) }}</td>
          <td class="text-right" :class="cor(-r.saida)">{{ formataNumero(r.saida) }}</td>
        </tr>
        <template v-if="quadro">
          <tr class="text-weight-bold">
            <td>Total</td>
            <td class="text-right">{{ formataNumero(quadro.totalentrada) }}</td>
            <td class="text-right">{{ formataNumero(quadro.totalsaida) }}</td>
          </tr>
          <tr v-if="contado('final')">
            <td>Diferença</td>
            <td
              colspan="2"
              class="text-right text-weight-bold"
              :class="naTolerancia(contado('final').diferenca) ? 'text-green-8' : 'text-amber-10'"
            >
              {{ sinal(contado('final').diferenca) }}
              <div
                v-if="!naTolerancia(contado('final').diferenca)"
                class="text-caption text-weight-regular text-grey-7"
              >
                Acima Tolerância {{ formataNumero(periodo.tolerancia) }}
              </div>
            </td>
          </tr>
          <tr v-else>
            <td>
              Saldo final
              <div class="text-caption text-grey-7">a contar</div>
            </td>
            <td colspan="2" class="text-right" :class="periodo.saldofinal < 0 ? 'text-red-8' : ''">
              {{ formataNumero(periodo.saldofinal) }}
            </td>
          </tr>
        </template>
        <tr v-else class="text-weight-bold">
          <td>Saldo final</td>
          <td colspan="2" class="text-right" :class="periodo.saldofinal < 0 ? 'text-red-8' : ''">
            {{ formataNumero(periodo.saldofinal) }}
          </td>
        </tr>
      </tbody>
    </q-markup-table>
  </template>
</template>
