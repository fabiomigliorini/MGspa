<script setup>
import { reactive, onMounted, watch, ref, computed } from 'vue'
import { useNfeTerceiroStore } from '../../stores/nfeTerceiroStore'
import { useDebounceFn } from '@vueuse/core'
import SelectFilial from '../selects/SelectFilial.vue'
import SelectNaturezaOperacao from '../selects/SelectNaturezaOperacao.vue'
import SelectGrupoEconomico from '../selects/SelectGrupoEconomico.vue'
import SelectPessoa from '../selects/SelectPessoa.vue'

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
})

const convertToISODate = (ddmmyyyy) => {
  if (!ddmmyyyy || ddmmyyyy.length !== 10) return null
  const [day, month, year] = ddmmyyyy.split('/')
  return `${year}-${month}-${day}`
}

const convertFromISODate = (yyyymmdd) => {
  if (!yyyymmdd) return null
  const [year, month, day] = yyyymmdd.split('-')
  return `${day}/${month}/${year}`
}

const handleFilter = () => {
  const filtersToSend = {
    ...filters,
    emissao_inicio: convertToISODate(filters.emissao_inicio),
    emissao_fim: convertToISODate(filters.emissao_fim),
  }

  nfeTerceiroStore.setFilters(filtersToSend)
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
    if (key === 'emissao_inicio' || key === 'emissao_fim') {
      filters[key] = convertFromISODate(nfeTerceiroStore.filters[key])
    } else {
      filters[key] = nfeTerceiroStore.filters[key] || null
    }
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
        <q-input
          v-model="filters.nfechave"
          label="Chave NFe"
          outlined
          clearable
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="vpn_key" />
          </template>
        </q-input>
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Relacionamentos</div>

      <!-- Filial -->
      <div class="q-mb-md">
        <SelectFilial v-model="filters.codfilial" label="Filial" :bottom-slots="false" />
      </div>

      <!-- Pessoa (Fornecedor) -->
      <div class="q-mb-md">
        <SelectPessoa
          v-model="filters.codpessoa"
          label="Fornecedor"
          :bottom-slots="false"
          @select="handlePessoaSelect"
        />
      </div>

      <!-- Grupo Economico -->
      <div class="q-mb-md">
        <SelectGrupoEconomico
          v-model="filters.codgrupoeconomico"
          label="Grupo Economico"
          :bottom-slots="false"
        />
      </div>

      <!-- Natureza de Operacao -->
      <div class="q-mb-md">
        <SelectNaturezaOperacao
          v-model="filters.codnaturezaoperacao"
          label="Natureza de Operacao"
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
        <q-input
          v-model="filters.emissao_inicio"
          label="Emissao - De"
          outlined
          clearable
          mask="##/##/####"
          placeholder="DD/MM/AAAA"
          :bottom-slots="false"
        >
          <template v-slot:append>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="filters.emissao_inicio" mask="DD/MM/YYYY">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Fechar" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </div>

      <!-- Emissao Ate -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.emissao_fim"
          label="Emissao - Ate"
          outlined
          clearable
          mask="##/##/####"
          placeholder="DD/MM/AAAA"
          :bottom-slots="false"
        >
          <template v-slot:append>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="filters.emissao_fim" mask="DD/MM/YYYY">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Fechar" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </div>
    </div>
  </div>
</template>
