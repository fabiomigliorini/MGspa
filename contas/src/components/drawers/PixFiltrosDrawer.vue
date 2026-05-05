<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { usePixStore } from 'src/stores/pixStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'

const store = usePixStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

const negocioOptions = [
  { label: 'Com', value: 'com' },
  { label: 'Sem', value: 'sem' },
  { label: 'Todos', value: 'todos' },
]

const sortOptions = [
  { label: 'Data', value: 'horario' },
  { label: 'Valor', value: 'valor' },
  { label: 'Nome', value: 'nome' },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Pessoa" first>
      <q-input
        v-model="store.filters.nome"
        outlined
        clearable
        :bottom-slots="false"
        label="Nome"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>

      <q-input
        v-model="store.filters.cpf"
        outlined
        clearable
        :bottom-slots="false"
        label="CPF/CNPJ"
      >
        <template #prepend><q-icon name="badge" /></template>
      </q-input>
    </FilterGroup>

    <FilterGroup title="Valor">
      <div class="row q-col-gutter-sm">
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.valorinicial"
            clearable
            :bottom-slots="false"
            label="De R$"
          />
        </div>
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.valorfinal"
            clearable
            :bottom-slots="false"
            label="Até R$"
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Data">
      <MgInputData
        v-model="store.filters.horarioinicial"
        type="timestamp"
        seconds
        clearable
        :bottom-slots="false"
        label="De"
        class="q-mb-sm"
      />

      <MgInputData
        v-model="store.filters.horariofinal"
        type="timestamp"
        seconds
        default-time="end"
        clearable
        :bottom-slots="false"
        label="Até"
      />
    </FilterGroup>

    <FilterGroup title="Vínculo com Negócio">
      <q-btn-toggle
        v-model="store.filters.negocio"
        spread
        no-caps
        flat
        toggle-color="primary"
        :options="negocioOptions"
      />
    </FilterGroup>

    <FilterGroup title="Ordenar Por">
      <q-btn-toggle
        v-model="store.filters.sort"
        spread
        no-caps
        flat
        toggle-color="primary"
        :options="sortOptions"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
