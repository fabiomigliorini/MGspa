<script setup>
import { reactive, onMounted, watch, ref, computed } from 'vue'
import { useDfeDistribuicaoStore } from '../../stores/dfeDistribuicaoStore'
import { useDebounceFn } from '@vueuse/core'
import dfeDistribuicaoService from '../../services/dfeDistribuicaoService'

const dfeStore = useDfeDistribuicaoStore()
const isInitializing = ref(true)
const filialOptions = ref([])

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
  nfechave: null,
  codfilial: null,
  datade: null,
  dataate: null,
  nsude: null,
  nsuate: null,
})

const handleFilter = () => {
  dfeStore.setFilters({ ...filters })
  dfeStore.fetchItems(true)
}

const handleClearFilters = () => {
  Object.keys(filters).forEach((key) => {
    filters[key] = null
  })
  dfeStore.clearFilters()
  dfeStore.fetchItems(true)
}

const loadFiliais = async () => {
  try {
    const response = await dfeDistribuicaoService.filiaisHabilitadas()
    filialOptions.value = response.map((filial) => ({
      label: filial.filial,
      value: filial.codfilial,
    }))
  } catch (error) {
    console.error('Erro ao carregar filiais:', error)
  }
}

onMounted(() => {
  loadFiliais()

  Object.keys(filters).forEach((key) => {
    filters[key] = dfeStore.filters[key] || null
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
      <div class="text-caption text-grey-7 q-mb-md">Chave</div>

      <!-- Chave NFe -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.nfechave"
          label="Chave"
          outlined
          clearable
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="vpn_key" />
          </template>
        </q-input>
      </div>

      <!-- Filial -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.codfilial"
          :options="filialOptions"
          label="Filial"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="business" />
          </template>
        </q-select>
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Período</div>

      <!-- Data De -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.datade"
          label="De"
          outlined
          clearable
          type="date"
          stack-label
          :max="filters.dataate"
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="date_range" />
          </template>
        </q-input>
      </div>

      <!-- Data Até -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.dataate"
          label="Até"
          outlined
          clearable
          type="date"
          stack-label
          :min="filters.datade"
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="date_range" />
          </template>
        </q-input>
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">NSU (Número Serial Único)</div>

      <!-- NSU De -->
      <div class="q-mb-md">
        <q-input
          v-model.number="filters.nsude"
          label="De"
          outlined
          clearable
          type="number"
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="dialpad" />
          </template>
        </q-input>
      </div>

      <!-- NSU Até -->
      <div class="q-mb-md">
        <q-input
          v-model.number="filters.nsuate"
          label="Até"
          outlined
          clearable
          type="number"
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="dialpad" />
          </template>
        </q-input>
      </div>
    </div>
  </div>
</template>
