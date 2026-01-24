<script setup>
import { reactive, onMounted, watch, ref, computed } from 'vue'
import { useCidadeStore } from '../../stores/cidadeStore'
import { useDebounceFn } from '@vueuse/core'

const cidadeStore = useCidadeStore()
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
  cidade: null,
  codigooficial: null,
})

const handleFilter = () => {
  cidadeStore.setFilters({ ...filters })
  cidadeStore.fetchCidades(true)
}

const handleClearFilters = () => {
  // Limpa filtros locais
  Object.keys(filters).forEach((key) => {
    filters[key] = null
  })
  // Limpa filtros da store
  cidadeStore.clearFilters()
  // Recarrega dados
  cidadeStore.fetchCidades(true)
}

// Carrega filtros salvos da store ao montar
onMounted(() => {
  Object.keys(filters).forEach((key) => {
    filters[key] = cidadeStore.filters[key] || null
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
      <!-- Nome da Cidade -->
      <div class="text-grey-7 text-body2 q-mb-sm">Busque pela Cidade:</div>
      <div class="q-mb-md">
        <q-input v-model="filters.cidade" label="Nome da Cidade" outlined clearable>
          <template v-slot:prepend>
            <q-icon name="location_city" />
          </template>
        </q-input>
      </div>

      <!-- Codigo Oficial -->
      <div class="q-mb-md">
        <q-input v-model="filters.codigooficial" label="Codigo Oficial (IBGE)" outlined clearable>
          <template v-slot:prepend>
            <q-icon name="tag" />
          </template>
        </q-input>
      </div>
    </div>
  </div>
</template>
