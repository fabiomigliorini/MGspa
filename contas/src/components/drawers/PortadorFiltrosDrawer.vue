<script setup>
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { usePortadorStore } from 'src/stores/portadorStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelectBanco from '@components/MgSelectBanco.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import { PORTADOR_TIPO_OPTIONS } from 'src/constants/portadorTipo'

const store = usePortadorStore()

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
        v-model="store.filters.codportador"
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
        v-model="store.filters.portador"
        outlined
        clearable
        :bottom-slots="false"
        label="Portador"
      >
        <template #prepend><q-icon name="description" /></template>
      </MgInput>
    </FilterGroup>

    <FilterGroup title="Vínculos">
      <q-select
        v-model="store.filters.tipo"
        :options="PORTADOR_TIPO_OPTIONS"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Tipo"
        class="q-mb-sm"
      />

      <MgSelectBanco
        v-model="store.filters.codbanco"
        outlined
        clearable
        :bottom-slots="false"
        label="Banco"
        class="q-mb-sm"
      />

      <MgSelectFilial
        v-model="store.filters.codfilial"
        outlined
        clearable
        :bottom-slots="false"
        label="Filial"
      />
    </FilterGroup>

    <FilterGroup title="Boleto e Status">
      <q-select
        v-model="store.filters.emiteboleto"
        :options="boolOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Emite Boleto"
        class="q-mb-sm"
      />
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
