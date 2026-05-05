<script setup>
import { useRouter } from 'vue-router'
import { useSaldoStore } from 'src/stores/saldoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgInputData from '@components/MgInputData.vue'

const router = useRouter()
const store = useSaldoStore()

const onDateChange = (dia) => {
  store.dataSelecionada = dia
  store.listaSaldos()
  if (!dia) return
  const newPath = router.resolve({ name: 'portador-saldos', params: { dia } }).href
  window.history.replaceState(window.history.state, '', newPath)
}
</script>

<template>
  <FilterDrawerShell title="Filtros" :active-count="0">
    <FilterGroup title="Data" first>
      <MgInputData
        :model-value="store.dataSelecionada"
        type="date"
        label="Dia"
        :bottom-slots="false"
        @update:model-value="onDateChange"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
