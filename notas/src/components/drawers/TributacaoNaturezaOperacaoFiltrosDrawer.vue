<script setup>
import { reactive, onMounted, watch, ref, computed } from 'vue'
import {
  useTributacaoNaturezaOperacaoStore,
  TIPO_PRODUTO_OPTIONS,
} from '../../stores/tributacaoNaturezaOperacaoStore'
import { useDebounceFn } from '@vueuse/core'
import api from '../../services/api'

const tributacaoStore = useTributacaoNaturezaOperacaoStore()
const isInitializing = ref(true)

// Options para selects
const tributacaoOptions = ref([])
const estadoOptions = ref([])
const cfopOptions = ref([])

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
})

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

// Carrega opções dos selects
const loadOptions = async () => {
  try {
    const [tributacoesRes, estadosRes, cfopsRes] = await Promise.all([
      api.get('/v1/select/tributacao'),
      api.get('/v1/select/estado'),
      api.get('/v1/cfop', { params: { per_page: 1000 } }),
    ])
    tributacaoOptions.value = tributacoesRes.data || []
    estadoOptions.value = estadosRes.data || []
    cfopOptions.value = (cfopsRes.data?.data || []).map((cfop) => ({
      label: `${cfop.codcfop} - ${cfop.descricao}`,
      value: cfop.codcfop,
    }))
  } catch (error) {
    console.error('Erro ao carregar opções:', error)
  }
}

onMounted(async () => {
  await loadOptions()

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

      <!-- Estado -->
      <div class="text-grey-7 text-body2">Estado:</div>
      <div class="q-mb-md">
        <q-select
          v-model="filters.codestado"
          :options="estadoOptions"
          option-value="value"
          option-label="sigla"
          emit-value
          map-options
          outlined
          clearable
          label="Estado"
        >
          <template v-slot:prepend>
            <q-icon name="map" />
          </template>
        </q-select>
      </div>

      <!-- Tributação -->
      <div class="text-caption text-grey-7">Tributação:</div>
      <div class="q-mb-md">
        <q-select
          v-model="filters.codtributacao"
          :options="tributacaoOptions"
          option-value="codtributacao"
          option-label="tributacao"
          emit-value
          map-options
          outlined
          clearable
          label="Tributação"
        >
          <template v-slot:prepend>
            <q-icon name="receipt" />
          </template>
        </q-select>
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
        <q-select
          v-model="filters.codcfop"
          :options="cfopOptions"
          option-value="value"
          option-label="label"
          emit-value
          map-options
          outlined
          clearable
          label="CFOP"
          use-input
          input-debounce="0"
          @filter="(val, update) => update()"
        >
          <template v-slot:prepend>
            <q-icon name="numbers" />
          </template>
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">Nenhum resultado</q-item-section>
            </q-item>
          </template>
        </q-select>
      </div>
    </div>
  </div>
</template>
