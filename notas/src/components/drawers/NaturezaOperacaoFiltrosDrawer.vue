<script setup>
import { reactive, onMounted, watch, ref, computed } from 'vue'
import {
  useNaturezaOperacaoStore,
  FINNFE_OPTIONS,
  OPERACAO_OPTIONS,
} from '../../stores/naturezaOperacaoStore'
import { useDebounceFn } from '@vueuse/core'

const naturezaOperacaoStore = useNaturezaOperacaoStore()
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
  naturezaoperacao: null,
  finnfe: null,
  codoperacao: null,
  emitida: null,
})

const handleFilter = () => {
  naturezaOperacaoStore.setFilters({ ...filters })
  naturezaOperacaoStore.fetchNaturezaOperacoes(true)
}

const handleClearFilters = () => {
  // Limpa filtros locais
  Object.keys(filters).forEach((key) => {
    filters[key] = null
  })
  // Limpa filtros da store
  naturezaOperacaoStore.clearFilters()
  // Recarrega dados
  naturezaOperacaoStore.fetchNaturezaOperacoes(true)
}

// Carrega filtros salvos da store ao montar
onMounted(() => {
  Object.keys(filters).forEach((key) => {
    filters[key] = naturezaOperacaoStore.filters[key] || null
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
      <!-- Nome da Natureza de Operação -->
      <div class="text-grey-7 text-body2 q-mb-sm">Busque pela Natureza:</div>
      <div class="q-mb-md">
        <q-input
          v-model="filters.naturezaoperacao"
          label="Natureza de Operação"
          outlined
          clearable
          placeholder="Ex: Venda"
        >
          <template v-slot:prepend>
            <q-icon name="swap_horiz" />
          </template>
        </q-input>
      </div>

      <!-- Filtro por Operação -->
      <div class="text-grey-7 text-body2 q-mb-sm">Tipo de Operação:</div>
      <div class="q-mb-md">
        <q-select
          v-model="filters.codoperacao"
          :options="OPERACAO_OPTIONS"
          option-value="value"
          option-label="label"
          emit-value
          map-options
          outlined
          clearable
          label="Operação"
        >
          <template v-slot:prepend>
            <q-icon name="compare_arrows" />
          </template>
        </q-select>
      </div>

      <!-- Filtro por Finalidade NFe -->
      <div class="text-grey-7 text-body2 q-mb-sm">Finalidade NFe:</div>
      <div class="q-mb-md">
        <q-select
          v-model="filters.finnfe"
          :options="FINNFE_OPTIONS"
          option-value="value"
          option-label="label"
          emit-value
          map-options
          outlined
          clearable
          label="Finalidade"
        >
          <template v-slot:prepend>
            <q-icon name="description" />
          </template>
        </q-select>
      </div>
    </div>
  </div>
</template>
