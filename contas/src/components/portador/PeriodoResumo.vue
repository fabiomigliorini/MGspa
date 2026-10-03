<script setup>
// Resumo do período (doc-4, R9): o formulário "Movimento do Caixa" de papel. Saldo inicial,
// entradas e saídas por origem e saldo final, todos gravados pelo servidor; na gaveta, também o
// contado × sistema da abertura e do fechamento. Clicar numa origem filtra a lista.
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { formataNumero } from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'

const store = periodoStore()
const { periodo, filtroOrigem } = storeToRefs(store)

const caixa = computed(() => periodo.value?.caixa)
const cor = (v) => (v < 0 ? 'text-red-8' : v > 0 ? 'text-green-8' : 'text-grey-7')
const filtrar = (origem) => (filtroOrigem.value = filtroOrigem.value === origem ? null : origem)
</script>

<template>
  <q-card v-if="periodo" flat bordered class="q-mb-md">
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
          <td>Saldo inicial</td>
          <td colspan="2" class="text-right">{{ formataNumero(periodo.saldoinicial) }}</td>
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
          <td>Saldo final</td>
          <td colspan="2" class="text-right" :class="periodo.saldofinal < 0 ? 'text-red-8' : ''">
            {{ formataNumero(periodo.saldofinal) }}
          </td>
        </tr>
        <template v-if="caixa">
          <tr>
            <td>Contado na abertura</td>
            <td colspan="2" class="text-right">
              {{ formataNumero(caixa.contadoabertura) }}
              <span
                v-if="caixa.ajusteabertura"
                class="text-caption"
                :class="cor(caixa.ajusteabertura)"
              >
                (ajuste {{ formataNumero(caixa.ajusteabertura) }})
              </span>
            </td>
          </tr>
          <tr v-if="caixa.contadofechamento !== null">
            <td>Contado no fechamento (sistema {{ formataNumero(caixa.sistemafechamento) }})</td>
            <td colspan="2" class="text-right">
              {{ formataNumero(caixa.contadofechamento) }}
              <span
                v-if="caixa.ajustefechamento"
                class="text-caption"
                :class="cor(caixa.ajustefechamento)"
              >
                (ajuste {{ formataNumero(caixa.ajustefechamento) }})
              </span>
            </td>
          </tr>
        </template>
      </tbody>
    </q-markup-table>
  </q-card>
</template>
