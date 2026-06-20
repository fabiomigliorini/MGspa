<script setup>
import { watch } from 'vue'
import { useTributacaoStore } from 'stores/tributacao'
import { useQuasar } from 'quasar'
import { useDebounceFn } from '@vueuse/core'
import MgSelectEstado from '@components/MgSelectEstado.vue'
import MgSelectCidade from '@components/MgSelectCidade.vue'
import MgSelectNaturezaOperacao from '@components/MgSelectNaturezaOperacao.vue'
import MgSelectTipoProduto from '@components/MgSelectTipoProduto.vue'
import SelectTipoCliente from '@components/MgSelectTipoCliente.vue'
import MgInputValor from '@components/MgInputValor.vue'

const $q = useQuasar()
const store = useTributacaoStore()

// Função debounced para aplicar filtros automaticamente
const debouncedApplyFilters = useDebounceFn(async () => {
  try {
    await store.applyFilters()
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao aplicar filtros',
      caption: error.message,
    })
  }
}, 800) // 800ms de debounce

// Watch nos filtros para aplicar automaticamente
watch(
  () => store.filters,
  () => {
    debouncedApplyFilters()
  },
  { deep: true },
)

const limparFiltros = async () => {
  try {
    await store.clearFilters()
    $q.notify({
      type: 'info',
      message: 'Filtros limpos',
      icon: 'info',
      timeout: 1000,
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao limpar filtros',
      caption: error.message,
    })
  }
}
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
          {{ store.activeFiltersCount }}
          {{ store.activeFiltersCount === 1 ? 'filtro ativo' : 'filtros ativos' }}
        </div>
        <q-btn
          v-if="store.hasActiveFilters"
          flat
          dense
          round
          icon="close"
          color="white"
          size="sm"
          @click="limparFiltros"
        >
          <q-tooltip>Limpar Filtros</q-tooltip>
        </q-btn>
      </div>
    </div>

    <q-separator />

    <!-- Filtros -->
    <div class="q-pa-md">
      <div class="text-caption text-grey-7 q-mb-md">Critérios de Incidência (ordem do motor)</div>

      <!-- 1. Natureza de Operação -->
      <div class="q-mb-md">
        <MgSelectNaturezaOperacao
          v-model="store.filters.codnaturezaoperacao"
          label="1. Natureza de Operação"
          clearable
          :bottom-slots="false"
        />
      </div>

      <!-- 2. Estado Destino -->
      <div class="q-mb-md">
        <MgSelectEstado
          v-model="store.filters.codestadodestino"
          label="2. Estado Destino"
          clearable
          :bottom-slots="false"
        />
      </div>

      <!-- 3. Cidade Destino -->
      <div class="q-mb-md">
        <MgSelectCidade
          v-model="store.filters.codcidadedestino"
          label="3. Cidade Destino"
          clearable
          :bottom-slots="false"
        />
      </div>

      <!-- 4. Tipo de Produto -->
      <div class="q-mb-md">
        <MgSelectTipoProduto
          v-model="store.filters.codtipoproduto"
          label="4. Tipo de Produto"
          clearable
          :bottom-slots="false"
        />
      </div>

      <!-- 5. Tipo de Cliente -->
      <div class="q-mb-md">
        <SelectTipoCliente
          v-model="store.filters.tipocliente"
          label="5. Tipo de Cliente"
          clearable
          :bottom-slots="false"
        />
      </div>

      <!-- 6. NCM -->
      <div class="q-mb-md">
        <q-input
          v-model="store.filters.ncm"
          label="6. NCM"
          outlined
          clearable
          placeholder="Ex: 84713012"
          mask="########"
        />
      </div>

      <q-separator class="q-my-md" />

      <!-- Base Percentual -->
      <div class="q-mb-md">
        <MgInputValor
          v-model="store.filters.basepercentual"
          label="Base Percentual (%)"
          clearable
          :min="0"
          :max="100"
        />
      </div>

      <!-- Alíquota -->
      <div class="q-mb-md">
        <MgInputValor
          v-model="store.filters.aliquota"
          label="Alíquota (%)"
          clearable
          :min="0"
          :max="100"
        />
      </div>

      <!-- CST -->
      <div class="q-mb-md">
        <q-input
          v-model="store.filters.cst"
          label="CST"
          outlined
          clearable
          placeholder="Ex: 01"
          maxlength="2"
        />
      </div>

      <!-- Classificação Tributária -->
      <div class="q-mb-md">
        <q-input
          v-model="store.filters.cclasstrib"
          label="Classificação Tributária"
          outlined
          clearable
          placeholder="Digite o código"
        />
      </div>

      <q-separator class="q-my-md" />

      <!-- Gera Crédito -->
      <div class="q-mb-md">
        <div class="text-subtitle2 q-mb-xs">Gera Crédito</div>
        <q-option-group
          v-model="store.filters.geracredito"
          :options="[
            { label: 'Todos', value: null },
            { label: 'Sim', value: true },
            { label: 'Não', value: false },
          ]"
          type="radio"
          dense
        />
      </div>

      <!-- Benefício -->
      <div class="q-mb-md">
        <q-input
          v-model="store.filters.beneficiocodigo"
          label="Código Benefício"
          outlined
          clearable
          placeholder="Ex: BE001"
        />
      </div>

      <!-- Vigência -->
      <div class="q-mb-md">
        <q-select
          v-model="store.filters.vigencia"
          label="Vigência"
          outlined
          clearable
          :options="[
            { label: 'Todas', value: null },
            { label: 'Vigentes', value: 'vigente' },
            { label: 'Futuras', value: 'futuro' },
            { label: 'Expiradas', value: 'expirado' },
          ]"
          option-label="label"
          option-value="value"
          emit-value
          map-options
        />
      </div>
    </div>
  </div>
</template>
