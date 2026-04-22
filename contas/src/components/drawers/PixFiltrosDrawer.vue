<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { usePixStore } from 'src/stores/pixStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

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
          <q-input
            v-model.number="store.filters.valorinicial"
            outlined
            clearable
            :bottom-slots="false"
            type="number"
            step="0.01"
            label="De R$"
            input-class="text-right"
          />
        </div>
        <div class="col-6">
          <q-input
            v-model.number="store.filters.valorfinal"
            outlined
            clearable
            :bottom-slots="false"
            type="number"
            step="0.01"
            label="Até R$"
            input-class="text-right"
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Data">
      <q-input
        v-model="store.filters.horarioinicial"
        outlined
        clearable
        :bottom-slots="false"
        mask="##/##/#### ##:##"
        input-class="text-center"
        label="De"
        class="q-mb-sm"
      >
        <template #prepend>
          <q-icon name="event" class="cursor-pointer">
            <q-popup-proxy transition-show="scale" transition-hide="scale">
              <q-date v-model="store.filters.horarioinicial" mask="DD/MM/YYYY HH:mm" />
            </q-popup-proxy>
          </q-icon>
        </template>
        <template #append>
          <q-icon name="access_time" class="cursor-pointer">
            <q-popup-proxy transition-show="scale" transition-hide="scale">
              <q-time v-model="store.filters.horarioinicial" mask="DD/MM/YYYY HH:mm" format24h />
            </q-popup-proxy>
          </q-icon>
        </template>
      </q-input>

      <q-input
        v-model="store.filters.horariofinal"
        outlined
        clearable
        :bottom-slots="false"
        mask="##/##/#### ##:##"
        input-class="text-center"
        label="Até"
      >
        <template #prepend>
          <q-icon name="event" class="cursor-pointer">
            <q-popup-proxy transition-show="scale" transition-hide="scale">
              <q-date v-model="store.filters.horariofinal" mask="DD/MM/YYYY HH:mm" />
            </q-popup-proxy>
          </q-icon>
        </template>
        <template #append>
          <q-icon name="access_time" class="cursor-pointer">
            <q-popup-proxy transition-show="scale" transition-hide="scale">
              <q-time v-model="store.filters.horariofinal" mask="DD/MM/YYYY HH:mm" format24h />
            </q-popup-proxy>
          </q-icon>
        </template>
      </q-input>
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
