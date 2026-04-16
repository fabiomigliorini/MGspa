<script setup>
import { watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useTipoTituloStore } from 'src/stores/tipoTituloStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useTipoTituloStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

onMounted(() => store.fetchTiposMovimento())

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

const boolOptions = [
  { label: 'Sim', value: true },
  { label: 'Não', value: false },
  { label: 'Todos', value: null },
]

const statusOptions = [
  { label: 'Ativos', value: false },
  { label: 'Inativos', value: true },
  { label: 'Todos', value: null },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-input
        v-model.number="store.filters.codtipotitulo"
        outlined
        clearable
        :bottom-slots="false"
        type="number"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </q-input>

      <q-input
        v-model="store.filters.tipotitulo"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="description" /></template>
      </q-input>

      <q-select
        v-model="store.filters.codtipomovimentotitulo"
        :options="store.tiposMovimento"
        option-value="codtipomovimentotitulo"
        option-label="tipomovimentotitulo"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Tipo Movimento"
      >
        <template #prepend><q-icon name="sync_alt" /></template>
      </q-select>
    </FilterGroup>

    <FilterGroup title="Movimentação">
      <q-select
        v-model="store.filters.pagar"
        :options="boolOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Pagar"
        class="q-mb-sm"
      />
      <q-select
        v-model="store.filters.receber"
        :options="boolOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Receber"
        class="q-mb-sm"
      />
      <q-select
        v-model="store.filters.debito"
        :options="boolOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Débito"
        class="q-mb-sm"
      />
      <q-select
        v-model="store.filters.credito"
        :options="boolOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Crédito"
      />
    </FilterGroup>

    <FilterGroup title="Status">
      <q-select
        v-model="store.filters.inativo"
        :options="statusOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Situação"
      >
        <template #prepend><q-icon name="toggle_on" /></template>
      </q-select>
    </FilterGroup>
  </FilterDrawerShell>
</template>
