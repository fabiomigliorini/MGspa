<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'src/services/api'
import { useEstoqueSaldoStore } from 'src/stores/estoqueSaldoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgAutocomplete from 'src/components/MgAutocomplete.vue'

const store = useEstoqueSaldoStore()
const f = store.filters

const depositos = ref([])

const aplicar = () => store.fetchItems()
const clear = () => store.clearFilters()

const saldoOptions = [
  { label: 'Indiferente', value: null },
  { label: 'Negativo', value: -1 },
  { label: 'Positivo', value: 1 },
]
const minimoOptions = [
  { label: 'Indiferente', value: null },
  { label: 'Abaixo do mínimo', value: -1 },
  { label: 'Acima do mínimo', value: 1 },
]
const maximoOptions = [
  { label: 'Indiferente', value: null },
  { label: 'Abaixo do máximo', value: -1 },
  { label: 'Acima do máximo', value: 1 },
]

onMounted(async () => {
  const { data } = await api.get('v1/select/estoque-local')
  depositos.value = data
})
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Local e Marca" first>
      <q-select
        v-model="f.codestoquelocal"
        :options="depositos"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Depósito"
        class="q-mb-sm"
        @update:model-value="aplicar"
      >
        <template #prepend><q-icon name="warehouse" /></template>
      </q-select>
      <MgAutocomplete
        v-model="f.codmarca"
        endpoint="v1/marca/autocompletar"
        search-param="marca"
        label="Marca"
        @update:model-value="aplicar"
      />
    </FilterGroup>

    <FilterGroup title="Saldo">
      <q-select
        v-model="f.saldo"
        :options="saldoOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Saldo"
        class="q-mb-sm"
        @update:model-value="aplicar"
      />
      <q-select
        v-model="f.minimo"
        :options="minimoOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Estoque mínimo"
        class="q-mb-sm"
        @update:model-value="aplicar"
      />
      <q-select
        v-model="f.maximo"
        :options="maximoOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Estoque máximo"
        @update:model-value="aplicar"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
