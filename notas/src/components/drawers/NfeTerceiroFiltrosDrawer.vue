<script setup>
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import { reactive, onMounted, watch, ref, computed } from 'vue'
import { useNfeTerceiroStore } from '../../stores/nfeTerceiroStore'
import { useDebounceFn } from '@vueuse/core'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectNaturezaOperacao from '@components/MgSelectNaturezaOperacao.vue'
import MgSelectGrupoEconomico from '@components/MgSelectGrupoEconomico.vue'
import SelectPessoa from '@components/MgSelectPessoa.vue'

const nfeTerceiroStore = useNfeTerceiroStore()
const updatingFromPessoa = ref(false)
const isInitializing = ref(true)

const situacaoOptions = [
  { label: 'Autorizada', value: 1 },
  { label: 'Denegada', value: 2 },
  { label: 'Cancelada', value: 3 },
]

const manifestacaoOptions = [
  { label: 'Ciência da Operação', value: 210210, color: 'orange' },
  { label: 'Operação Realizada', value: 210200, color: 'green' },
  { label: 'Desconhecida', value: 210220, color: 'red' },
  { label: 'Não Realizada', value: 210240, color: 'red' },
]

const booleanOptions = [
  { label: 'Sim', value: true },
  { label: 'Não', value: false },
]

const importacaoOptions = [
  { label: 'Pendentes', value: 'pendentes' },
  { label: 'Importadas', value: 'importadas' },
  { label: 'Ignoradas', value: 'ignoradas' },
]

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
  codpessoa: null,
  codgrupoeconomico: null,
  codnaturezaoperacao: null,
  emissao_inicio: null,
  emissao_fim: null,
  indsituacao: null,
  indmanifestacao: null,
  ignorada: null,
  revisao: null,
  conferencia: null,
  importacao: null,
})

const handleFilter = () => {
  nfeTerceiroStore.setFilters({ ...filters })
  nfeTerceiroStore.fetchItems(true)
}

const handleClearFilters = () => {
  Object.keys(filters).forEach((key) => {
    filters[key] = null
  })
  nfeTerceiroStore.clearFilters()
  nfeTerceiroStore.fetchItems(true)
}

const handlePessoaSelect = (pessoa) => {
  updatingFromPessoa.value = true
  if (pessoa.codgrupoeconomico) {
    filters.codgrupoeconomico = pessoa.codgrupoeconomico
  } else {
    filters.codgrupoeconomico = null
  }
  setTimeout(() => {
    updatingFromPessoa.value = false
  }, 100)
}

watch(
  () => filters.codgrupoeconomico,
  (newValue, oldValue) => {
    if (!updatingFromPessoa.value && newValue !== oldValue) {
      filters.codpessoa = null
    }
  },
)

onMounted(() => {
  Object.keys(filters).forEach((key) => {
    filters[key] = nfeTerceiroStore.filters[key] || null
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
      <div class="text-caption text-grey-7 q-mb-md">Busca</div>

      <!-- Chave NFe -->
      <div class="q-mb-md">
        <MgInput
          v-model="filters.nfechave"
          label="Chave NFe"
          outlined
          clearable
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="vpn_key" />
          </template>
        </MgInput>
      </div>

      <!-- Importacao -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.importacao"
          :options="importacaoOptions"
          label="Importacao"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        />
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Relacionamentos</div>

      <!-- Filial -->
      <div class="q-mb-md">
        <MgSelectFilial
          v-model="filters.codfilial"
          label="Filial"
          clearable
          :bottom-slots="false"
        />
      </div>

      <!-- Pessoa (Fornecedor) -->
      <div class="q-mb-md">
        <SelectPessoa
          v-model="filters.codpessoa"
          clearable
          label="Fornecedor"
          :bottom-slots="false"
          @select="handlePessoaSelect"
        />
      </div>

      <!-- Grupo Economico -->
      <div class="q-mb-md">
        <MgSelectGrupoEconomico
          v-model="filters.codgrupoeconomico"
          label="Grupo Economico"
          clearable
          :bottom-slots="false"
        />
      </div>

      <!-- Natureza de Operacao -->
      <div class="q-mb-md">
        <MgSelectNaturezaOperacao
          v-model="filters.codnaturezaoperacao"
          label="Natureza de Operacao"
          clearable
          :bottom-slots="false"
        />
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Status</div>

      <!-- Situacao -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.indsituacao"
          :options="situacaoOptions"
          label="Situacao"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        />
      </div>

      <!-- Manifestacao -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.indmanifestacao"
          :options="manifestacaoOptions"
          label="Manifestacao"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        >
          <template v-slot:option="scope">
            <q-item v-bind="scope.itemProps">
              <q-item-section avatar>
                <q-badge :color="scope.opt.color" rounded />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ scope.opt.label }}</q-item-label>
              </q-item-section>
            </q-item>
          </template>
        </q-select>
      </div>

      <!-- Ignorada -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.ignorada"
          :options="booleanOptions"
          label="Ignorada"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        />
      </div>

      <!-- Revisao -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.revisao"
          :options="booleanOptions"
          label="Revisada"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        />
      </div>

      <!-- Conferencia -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.conferencia"
          :options="booleanOptions"
          label="Conferida"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        />
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Periodo de Emissao</div>

      <!-- Emissao De -->
      <div class="q-mb-md">
        <MgInputData v-model="filters.emissao_inicio" label="Emissao - De" :bottom-slots="false" />
      </div>

      <!-- Emissao Ate -->
      <div class="q-mb-md">
        <MgInputData v-model="filters.emissao_fim" label="Emissao - Ate" :bottom-slots="false" />
      </div>
    </div>
  </div>
</template>
