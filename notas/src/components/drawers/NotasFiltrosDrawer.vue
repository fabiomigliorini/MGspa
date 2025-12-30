<script setup>
import { reactive, onMounted } from 'vue'
import { useNotaFiscalStore } from '../../stores/notaFiscalStore'

const notaFiscalStore = useNotaFiscalStore()

const filters = reactive({
  search: '',
  modelo: null,
  situacao: null,
  dataInicial: null,
  dataFinal: null
})

const modeloOptions = [
  { label: 'NF-e (55)', value: '55' },
  { label: 'NFC-e (65)', value: '65' }
]

const situacaoOptions = [
  { label: 'Digitação', value: 'Digitacao' },
  { label: 'Autorizada', value: 'Autorizada' },
  { label: 'Cancelada', value: 'Cancelada' },
  { label: 'Inutilizada', value: 'Inutilizada' },
  { label: 'Denegada', value: 'Denegada' }
]

const handleFilter = () => {
  notaFiscalStore.setFilters(filters)
  notaFiscalStore.pagination.page = 1
  notaFiscalStore.fetchNotas(filters)
}

const handleClearFilters = () => {
  filters.search = ''
  filters.modelo = null
  filters.situacao = null
  filters.dataInicial = null
  filters.dataFinal = null
  notaFiscalStore.clearFilters()
  notaFiscalStore.pagination.page = 1
  notaFiscalStore.fetchNotas()
}

// Carrega filtros salvos da store ao montar
onMounted(() => {
  filters.search = notaFiscalStore.filters.search || ''
  filters.modelo = notaFiscalStore.filters.modelo || null
  filters.situacao = notaFiscalStore.filters.situacao || null
  filters.dataInicial = notaFiscalStore.filters.dataInicial || null
  filters.dataFinal = notaFiscalStore.filters.dataFinal || null
})
</script>

<template>
  <div class="q-pa-md">
    <div class="text-h6 q-mb-md">Filtros</div>

    <!-- Busca -->
    <q-input
      v-model="filters.search"
      label="Buscar (Número, Chave, Cliente)"
      outlined
      dense
      class="q-mb-md"
      clearable
    >
      <template v-slot:prepend>
        <q-icon name="search" />
      </template>
    </q-input>

    <!-- Modelo -->
    <q-select
      v-model="filters.modelo"
      :options="modeloOptions"
      label="Modelo"
      outlined
      dense
      class="q-mb-md"
      clearable
      emit-value
      map-options
    />

    <!-- Situação -->
    <q-select
      v-model="filters.situacao"
      :options="situacaoOptions"
      label="Situação"
      outlined
      dense
      class="q-mb-md"
      clearable
      emit-value
      map-options
    />

    <!-- Data Inicial -->
    <q-input
      v-model="filters.dataInicial"
      label="Data Inicial"
      outlined
      dense
      class="q-mb-md"
      clearable
    >
      <template v-slot:prepend>
        <q-icon name="event" class="cursor-pointer">
          <q-popup-proxy cover transition-show="scale" transition-hide="scale">
            <q-date v-model="filters.dataInicial" mask="YYYY-MM-DD">
              <div class="row items-center justify-end">
                <q-btn v-close-popup label="OK" color="primary" flat />
              </div>
            </q-date>
          </q-popup-proxy>
        </q-icon>
      </template>
    </q-input>

    <!-- Data Final -->
    <q-input
      v-model="filters.dataFinal"
      label="Data Final"
      outlined
      dense
      class="q-mb-md"
      clearable
    >
      <template v-slot:prepend>
        <q-icon name="event" class="cursor-pointer">
          <q-popup-proxy cover transition-show="scale" transition-hide="scale">
            <q-date v-model="filters.dataFinal" mask="YYYY-MM-DD">
              <div class="row items-center justify-end">
                <q-btn v-close-popup label="OK" color="primary" flat />
              </div>
            </q-date>
          </q-popup-proxy>
        </q-icon>
      </template>
    </q-input>

    <q-separator class="q-my-md" />

    <!-- Botões -->
    <q-btn
      color="primary"
      label="Filtrar"
      icon="search"
      class="full-width q-mb-sm"
      @click="handleFilter"
    />

    <q-btn
      outline
      color="primary"
      label="Limpar Filtros"
      icon="clear"
      class="full-width"
      @click="handleClearFilters"
    />
  </div>
</template>
