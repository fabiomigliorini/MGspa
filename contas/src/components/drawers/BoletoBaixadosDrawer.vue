<script setup>
import { computed } from 'vue'
import { useBoletoStore } from 'src/stores/boletoStore'
import { TIPO_BAIXA } from 'src/constants/tituloBoleto'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'

const store = useBoletoStore()

const tiposBaixa = Object.entries(TIPO_BAIXA).map(([value, label]) => ({
  value: Number(value),
  label,
}))

const activeCount = computed(() => {
  const f = store.baixadosFiltros
  let count = 0
  if (f.codportador) count++
  if (f.tipobaixa) count++
  return count
})

function limpar() {
  store.baixadosFiltros.codportador = null
  store.baixadosFiltros.tipobaixa = null
}
</script>

<template>
  <FilterDrawerShell title="Filtros" :active-count="activeCount" @clear="limpar">
    <FilterGroup title="Portador" first>
      <MgSelectPortador
        v-model="store.baixadosFiltros.codportador"
        label="Portador"
        outlined
        clearable
        :bottom-slots="false"
      />
    </FilterGroup>

    <FilterGroup title="Tipo de Baixa">
      <q-select
        v-model="store.baixadosFiltros.tipobaixa"
        :options="tiposBaixa"
        label="Tipo de Baixa"
        outlined
        clearable
        emit-value
        map-options
        :bottom-slots="false"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
