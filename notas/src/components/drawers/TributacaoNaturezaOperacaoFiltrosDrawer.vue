<script setup>
import { reactive, onMounted, watch, ref, computed } from 'vue'
import {
  useTributacaoNaturezaOperacaoStore,
  TIPO_PRODUTO_OPTIONS,
} from '../../stores/tributacaoNaturezaOperacaoStore'
import { useDebounceFn } from '@vueuse/core'
import SelectEstado from '../selects/SelectEstado.vue'
import SelectTributacao from '../selects/SelectTributacao.vue'
import SelectCfop from '../selects/SelectCfop.vue'

const tributacaoStore = useTributacaoNaturezaOperacaoStore()
const isInitializing = ref(true)

const activeFiltersCount = computed(() => {
  let count = 0
  Object.keys(filters).forEach((key) => {
    const value = filters[key]
    if (value !== null && value !== '' && value !== undefined) {
      count++
    }
  })
  return count
})

const debouncedApplyFilters = useDebounceFn(() => {
  if (!isInitializing.value) {
    handleFilter()
  }
}, 800)

const filters = reactive({
  codtributacaonaturezaoperacao: null,
  codestado: null,
  codtributacao: null,
  codtipoproduto: null,
  ncm: null,
  codcfop: null,
  bit: null,
})

const BIT_OPTIONS = [
  { value: true, label: 'Sim' },
  { value: false, label: 'Não' },
]

const handleFilter = () => {
  tributacaoStore.setFilters({ ...filters })
  tributacaoStore.fetchTributacoes(true)
}

const handleClearFilters = () => {
  Object.keys(filters).forEach((key) => {
    filters[key] = null
  })
  tributacaoStore.clearFilters()
  tributacaoStore.fetchTributacoes(true)
}

onMounted(async () => {
  Object.keys(filters).forEach((key) => {
    filters[key] = tributacaoStore.filters[key] ?? null
  })

  setTimeout(() => {
    isInitializing.value = false

    watch(
      () => filters,
      () => {
        debouncedApplyFilters()
      },
      { deep: true }
    )
  }, 200)
})
</script>

<template>
  <div class="column full-height">
    <!-- Header -->
    <div class="q-pa-md bg-primary text-white">
      <div class="text-h6">
        <q-icon name="filter_list" class="q-mr-sm" />
        Filtros
      </div>
      <div class="row items-center justify-between">
        <div class="text-caption">
          {{ activeFiltersCount }}
          {{ activeFiltersCount === 1 ? 'filtro ativo' : 'filtros ativos' }}
        </div>
        <q-btn
          v-if="activeFiltersCount > 0"
          flat
          dense
          round
          icon="close"
          color="white"
          size="sm"
          @click="handleClearFilters"
        >
          <q-tooltip>Limpar Filtros</q-tooltip>
        </q-btn>
      </div>
    </div>

    <q-separator />

    <!-- Filtros -->
    <div class="q-pa-md">
      <!-- Estado -->
      <div class="text-grey-7 text-body2">Estado:</div>
      <div>
        <SelectEstado v-model="filters.codestado" label="Estado" />
      </div>

      <!-- Código -->
      <div class="text-grey-7 text-body2">Código:</div>
      <div class="q-mb-md">
        <q-input
          v-model="filters.codtributacaonaturezaoperacao"
          label="Código"
          outlined
          clearable
          placeholder="Ex: 123"
          type="number"
        >
          <template v-slot:prepend>
            <q-icon name="tag" />
          </template>
        </q-input>
      </div>

      <!-- Tributação -->
      <div class="text-grey-7 text-body2">Tributação:</div>
      <div>
        <SelectTributacao v-model="filters.codtributacao" label="Tributação" />
      </div>

      <!-- Tipo de Produto -->
      <div class="text-grey-7 text-body2">Tipo de Produto:</div>
      <div class="q-mb-md">
        <q-select
          v-model="filters.codtipoproduto"
          :options="TIPO_PRODUTO_OPTIONS"
          option-value="value"
          option-label="label"
          emit-value
          map-options
          outlined
          clearable
          label="Tipo Produto"
        >
          <template v-slot:prepend>
            <q-icon name="category" />
          </template>
        </q-select>
      </div>

      <!-- BIT -->
      <div class="text-grey-7 text-body2">BIT:</div>
      <div class="q-mb-md">
        <q-select
          v-model="filters.bit"
          :options="BIT_OPTIONS"
          option-value="value"
          option-label="label"
          emit-value
          map-options
          outlined
          clearable
          label="BIT"
        >
          <template v-slot:prepend>
            <q-icon name="flag" />
          </template>
        </q-select>
      </div>

      <!-- NCM -->
      <div class="text-grey-7 text-body2">NCM:</div>
      <div class="q-mb-md">
        <q-input v-model="filters.ncm" label="NCM" outlined clearable placeholder="Ex: 4901">
          <template v-slot:prepend>
            <q-icon name="tag" />
          </template>
        </q-input>
      </div>

      <!-- CFOP -->
      <div class="text-grey-7 text-body2">CFOP:</div>
      <div class="q-mb-md">
        <SelectCfop v-model="filters.codcfop" label="CFOP" />
      </div>
    </div>
  </div>
</template>
