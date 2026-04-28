<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { formatMoney } from 'src/utils/formatters.js'
import { useBoletoStore } from 'src/stores/boletoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'

const route = useRoute()
const store = useBoletoStore()

const codportadorAtual = computed(() =>
  route.params.codportador ? Number(route.params.codportador) : null,
)

function linkPortador(codportador) {
  return {
    name: 'boleto-liquidados',
    params: {
      ano: route.params.ano,
      mes: route.params.mes,
      dia: route.params.dia,
      codportador,
    },
  }
}
</script>

<template>
  <FilterDrawerShell title="Portadores do Dia" :active-count="0" no-padding>
    <q-list separator>
      <q-item v-if="!store.liqPortadores.length" class="text-grey-6">
        <q-item-section>
          <q-item-label caption>Selecione ano, mês e dia</q-item-label>
        </q-item-section>
      </q-item>
      <q-item
        v-for="p in store.liqPortadores"
        :key="p.codportador"
        clickable
        :to="linkPortador(p.codportador)"
        :active="p.codportador === codportadorAtual"
        active-class="bg-blue-1 text-primary"
      >
        <q-item-section>
          <q-item-label class="text-weight-bold text-right">
            {{ formatMoney(p.total) }}
          </q-item-label>
          <q-item-label caption class="text-right">
            {{ p.conta }} · {{ p.portador }}
            <span class="text-grey-6">({{ p.quantidade }})</span>
          </q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </FilterDrawerShell>
</template>
