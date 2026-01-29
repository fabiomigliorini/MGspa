<script setup>
import { reactive, onMounted, watch, ref, computed } from 'vue'
import { useCfopStore } from '../../stores/cfopStore'
import { useDebounceFn } from '@vueuse/core'

const cfopStore = useCfopStore()
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

// Funcao debounced para aplicar filtros automaticamente
const debouncedApplyFilters = useDebounceFn(() => {
  if (!isInitializing.value) {
    handleFilter()
  }
}, 800)

const filters = reactive({
  cfop: null,
  descricao: null,
})

const handleFilter = () => {
  cfopStore.setFilters({ ...filters })
  cfopStore.fetchCfops(true)
}

const handleClearFilters = () => {
  // Limpa filtros locais
  Object.keys(filters).forEach((key) => {
    filters[key] = null
  })
  // Limpa filtros da store
  cfopStore.clearFilters()
  // Recarrega dados
  cfopStore.fetchCfops(true)
}

// Carrega filtros salvos da store ao montar
onMounted(() => {
  Object.keys(filters).forEach((key) => {
    filters[key] = cfopStore.filters[key] || null
  })

  // Aguarda um pouco e entao ativa o watcher
  setTimeout(() => {
    isInitializing.value = false

    // So agora ativa o watcher para mudancas futuras
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
      <!-- Codigo CFOP -->
      <div class="text-caption text-grey-7 q-mb-md">Busque pelo CFOP:</div>
      <div class="q-mb-md">
        <q-input
          v-model="filters.cfop"
          label="Código CFOP"
          outlined
          clearable
          placeholder="Ex: 5102"
        >
          <template v-slot:prepend>
            <q-icon name="tag" />
          </template>
        </q-input>
      </div>

      <!-- Descricao -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.descricao"
          label="Descrição"
          outlined
          clearable
          placeholder="Ex: Venda"
        >
          <template v-slot:prepend>
            <q-icon name="description" />
          </template>
        </q-input>
      </div>
    </div>
  </div>
</template>
