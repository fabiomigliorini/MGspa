<script setup>
import MgInput from '@components/MgInput.vue'
import MgSelect from '@components/MgSelect.vue'
import MgInputValor from '@components/MgInputValor.vue'
import { reactive, onMounted, watch, ref, computed } from 'vue'
import { useDfeDistribuicaoStore } from '../../stores/dfeDistribuicaoStore'
import { useDebounceFn } from '@vueuse/core'
import dfeDistribuicaoService from '../../services/dfeDistribuicaoService'
import MgInputData from '@components/MgInputData.vue'

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
      { deep: true },
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
        <MgInput v-model="filters.nfechave" label="Chave" outlined clearable :bottom-slots="false">
          <template v-slot:prepend>
            <q-icon name="vpn_key" />
          </template>
        </MgInput>
      </div>

      <!-- Filial -->
      <div class="q-mb-md">
        <MgSelect
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
        </MgSelect>
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Período</div>

      <!-- Data De -->
      <div class="q-mb-md">
        <MgInputData
          v-model="filters.datade"
          label="De"
          type="date"
          clearable
          stack-label
          :max="filters.dataate"
        />
      </div>

      <!-- Data Até -->
      <div class="q-mb-md">
        <MgInputData
          v-model="filters.dataate"
          label="Até"
          type="date"
          clearable
          stack-label
          :min="filters.datade"
        />
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">NSU (Número Serial Único)</div>

      <!-- NSU De -->
      <div class="q-mb-md">
        <MgInputValor
          v-model="filters.nsude"
          :decimals="0"
          :grouping="false"
          label="De"
          clearable
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="dialpad" />
          </template>
        </MgInputValor>
      </div>

      <!-- NSU Até -->
      <div class="q-mb-md">
        <MgInputValor
          v-model="filters.nsuate"
          :decimals="0"
          :grouping="false"
          label="Até"
          clearable
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="dialpad" />
          </template>
        </MgInputValor>
      </div>
    </div>
  </div>
</template>
