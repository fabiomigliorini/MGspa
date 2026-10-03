<script setup>
// Resumo do período (doc-4, R9), dentro do card do PeriodoCabecalho: o formulário "Movimento do
// Caixa" de papel. Saldo inicial, entradas e saídas por origem e saldo final, todos gravados pelo
// servidor. No caixa (espécie), o botão ao lado do saldo inicial e do final abre a contagem (fechado,
// só para ver), e
// embaixo do saldo aparece o contado e a diferença (o ajuste é um lançamento; fechar só batendo).
// Clicar numa origem filtra a lista.
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { formataNumero } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'

const emit = defineEmits(['contar'])

const store = periodoStore()
const { periodo, filtroOrigem } = storeToRefs(store)

// a contagem existe no caixa (espécie); fechado ou sem permissão, o dialog só mostra
const podeContar = computed(() => !!periodo.value?.contagem)
// a contagem do momento, se houve
const contado = (momento) => {
  const c = periodo.value?.contagem?.[momento]
  return c?.contado != null ? c : null
}
const conferencia = (c) =>
  `contado ${formataNumero(c.contado)} · ` +
  (c.diferenca ? `diferença ${c.diferenca > 0 ? '+' : ''}${formataNumero(c.diferenca)}` : 'confere')
const cor = (v) => (v < 0 ? 'text-red-8' : v > 0 ? 'text-green-8' : 'text-grey-7')
const filtrar = (origem) => (filtroOrigem.value = filtroOrigem.value === origem ? null : origem)
</script>

<template>
  <template v-if="periodo">
    <q-separator />
    <q-markup-table flat separator="horizontal" wrap-cells>
      <thead>
        <tr>
          <th class="text-left">Resumo</th>
          <th class="text-right">Entradas</th>
          <th class="text-right">Saídas</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            Saldo inicial
            <q-btn
              v-if="podeContar"
              flat
              round
              size="sm"
              color="grey-7"
              icon="calculate"
              class="q-ml-xs"
              @click="emit('contar', 'inicial')"
            >
              <q-tooltip>Contagem inicial</q-tooltip>
            </q-btn>
            <div
              v-if="contado('inicial')"
              class="text-caption"
              :class="contado('inicial').diferenca ? 'text-red-8' : 'text-green-8'"
            >
              {{ conferencia(contado('inicial')) }}
            </div>
          </td>
          <!-- positivo é entrada, negativo é saída -->
          <td class="text-right">
            {{ periodo.saldoinicial >= 0 ? formataNumero(periodo.saldoinicial) : '' }}
          </td>
          <td class="text-right text-red-8">
            {{ periodo.saldoinicial < 0 ? formataNumero(-periodo.saldoinicial) : '' }}
          </td>
        </tr>
        <tr
          v-for="r in periodo.resumo"
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
        <tr class="text-weight-bold">
          <td>
            Saldo final
            <q-btn
              v-if="podeContar"
              flat
              round
              size="sm"
              color="grey-7"
              icon="calculate"
              class="q-ml-xs"
              @click="emit('contar', 'final')"
            >
              <q-tooltip>Contagem final</q-tooltip>
            </q-btn>
            <div
              v-if="contado('final')"
              class="text-caption text-weight-regular"
              :class="contado('final').diferenca ? 'text-red-8' : 'text-green-8'"
            >
              {{ conferencia(contado('final')) }}
            </div>
          </td>
          <td colspan="2" class="text-right" :class="periodo.saldofinal < 0 ? 'text-red-8' : ''">
            {{ formataNumero(periodo.saldofinal) }}
          </td>
        </tr>
      </tbody>
    </q-markup-table>
  </template>
</template>
