<script setup>
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useTipoTituloStore } from 'src/stores/tipoTituloStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useTipoTituloStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

const boolOptions = [
  { label: 'Sim', value: true },
  { label: 'Não', value: false },
  { label: 'Todos', value: null },
]

const naturezaOptions = [
  { label: 'A Receber', value: 'R' },
  { label: 'A Pagar', value: 'P' },
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
      <MgInputValor
        v-model="store.filters.codtipotitulo"
        :decimals="0"
        :grouping="false"
        clearable
        :bottom-slots="false"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </MgInputValor>

      <MgInput
        v-model="store.filters.tipotitulo"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
      >
        <template #prepend><q-icon name="description" /></template>
      </MgInput>
    </FilterGroup>

    <FilterGroup title="Movimentação">
      <q-select
        v-model="store.filters.natureza"
        :options="naturezaOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Natureza"
        class="q-mb-sm"
      />
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
        v-model="store.filters.movimentaportador"
        :options="boolOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Movimenta portador"
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
