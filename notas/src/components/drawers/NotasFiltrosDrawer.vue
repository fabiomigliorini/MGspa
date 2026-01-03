<script setup>
import { reactive, onMounted, watch, ref, computed } from 'vue'
import { useNotaFiscalStore } from '../../stores/notaFiscalStore'
import { useDebounceFn } from '@vueuse/core'
import SelectFilial from '../selects/SelectFilial.vue'
import SelectNaturezaOperacao from '../selects/SelectNaturezaOperacao.vue'
import SelectGrupoEconomico from '../selects/SelectGrupoEconomico.vue'
import SelectPessoa from '../selects/SelectPessoa.vue'
import {
  MODELO_OPTIONS,
  STATUS_OPTIONS,
  EMITIDA_OPTIONS,
  OPERACAO_OPTIONS
} from '../../constants/notaFiscal'

const notaFiscalStore = useNotaFiscalStore()
const updatingFromPessoa = ref(false)
const isInitializing = ref(true)

const modeloOptions = MODELO_OPTIONS
const statusOptions = STATUS_OPTIONS
const emitidaOptions = EMITIDA_OPTIONS
const operacaoOptions = OPERACAO_OPTIONS

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

// Função debounced para aplicar filtros automaticamente
const debouncedApplyFilters = useDebounceFn(() => {
  if (!isInitializing.value) {
    handleFilter()
  }
}, 800) // 800ms de debounce

const filters = reactive({
  // Filtros de busca
  numero: null,
  serie: null,
  nfechave: null,

  // Filtros de relacionamento
  codfilial: null,
  codpessoa: null,
  codgrupoeconomico: null,
  codnaturezaoperacao: null,
  codoperacao: null,

  // Filtros de tipo
  modelo: null,
  emitida: null,
  status: null,

  // Filtros de data
  emissao_inicio: null,
  emissao_fim: null,
  saida_inicio: null,
  saida_fim: null,

  // Filtros de valor
  valortotal_inicio: null,
  valortotal_fim: null,
})

// Converte DD/MM/YYYY para YYYY-MM-DD
const convertToISODate = (ddmmyyyy) => {
  if (!ddmmyyyy || ddmmyyyy.length !== 10) return null
  const [day, month, year] = ddmmyyyy.split('/')
  return `${year}-${month}-${day}`
}

// Converte YYYY-MM-DD para DD/MM/YYYY
const convertFromISODate = (yyyymmdd) => {
  if (!yyyymmdd) return null
  const [year, month, day] = yyyymmdd.split('-')
  return `${day}/${month}/${year}`
}

const handleFilter = () => {
  // Converte datas para formato ISO antes de enviar
  const filtersToSend = {
    ...filters,
    emissao_inicio: convertToISODate(filters.emissao_inicio),
    emissao_fim: convertToISODate(filters.emissao_fim),
    saida_inicio: convertToISODate(filters.saida_inicio),
    saida_fim: convertToISODate(filters.saida_fim),
  }

  notaFiscalStore.setFilters(filtersToSend)
  notaFiscalStore.fetchNotas(true)
}

const handleClearFilters = () => {
  // Limpa filtros locais
  Object.keys(filters).forEach((key) => {
    filters[key] = null
  })
  // Limpa filtros da store
  notaFiscalStore.clearFilters()
  // Recarrega dados
  notaFiscalStore.fetchNotas(true)
}

// Handler para quando uma pessoa é selecionada
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

// Observa mudanças no grupo econômico
watch(
  () => filters.codgrupoeconomico,
  (newValue, oldValue) => {
    // Se a mudança não veio da seleção de pessoa e houve uma alteração real
    if (!updatingFromPessoa.value && newValue !== oldValue) {
      // Limpa a pessoa selecionada pois pode ser de outro grupo econômico
      filters.codpessoa = null
    }
  },
)

// Carrega filtros salvos da store ao montar
onMounted(() => {
  Object.keys(filters).forEach((key) => {
    // Converte datas de YYYY-MM-DD para DD/MM/YYYY para exibição
    if (
      key === 'emissao_inicio' ||
      key === 'emissao_fim' ||
      key === 'saida_inicio' ||
      key === 'saida_fim'
    ) {
      filters[key] = convertFromISODate(notaFiscalStore.filters[key])
    } else {
      filters[key] = notaFiscalStore.filters[key] || null
    }
  })

  // Aguarda um pouco e então ativa o watcher
  // Isso evita que o watcher dispare durante o carregamento inicial
  setTimeout(() => {
    isInitializing.value = false

    // Só agora ativa o watcher para mudanças futuras
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
      <div class="text-caption text-grey-7 q-mb-md">Identificação</div>

      <!-- Número -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.numero"
          label="Número"
          outlined
          clearable
          type="number"
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="tag" />
          </template>
        </q-input>
      </div>

      <!-- Série -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.serie"
          label="Série"
          outlined
          clearable
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="numbers" />
          </template>
        </q-input>
      </div>

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

      <!-- Natureza de Operação -->
      <div class="q-mb-md">
        <SelectNaturezaOperacao
          v-model="filters.codnaturezaoperacao"
          label="Natureza de Operação"
          :bottom-slots="false"
        />
      </div>

      <!-- Pessoa (Cliente/Fornecedor) -->
      <div class="q-mb-md">
        <SelectPessoa
          v-model="filters.codpessoa"
          label="Pessoa (Cliente/Fornecedor)"
          :bottom-slots="false"
          @select="handlePessoaSelect"
        />
      </div>

      <!-- Grupo Econômico -->
      <div class="q-mb-md">
        <SelectGrupoEconomico
          v-model="filters.codgrupoeconomico"
          label="Grupo Econômico"
          :bottom-slots="false"
        />
      </div>

      <!-- Operação -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.codoperacao"
          :options="operacaoOptions"
          label="Operação"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        />
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Tipo e Status</div>

      <!-- Modelo -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.modelo"
          :options="modeloOptions"
          label="Modelo"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        />
      </div>

      <!-- Status -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.status"
          :options="statusOptions"
          label="Status"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        >
          <template v-slot:option="scope">
            <q-item v-bind="scope.itemProps">
              <q-item-section avatar>
                <q-icon :name="scope.opt.icon" :color="scope.opt.color" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ scope.opt.label }}</q-item-label>
              </q-item-section>
            </q-item>
          </template>
          <template v-slot:selected-item="scope">
            <q-icon :name="scope.opt.icon" :color="scope.opt.color" size="xs" class="q-mr-xs" />
            {{ scope.opt.label }}
          </template>
        </q-select>
      </div>

      <!-- Emitida -->
      <div class="q-mb-md">
        <q-select
          v-model="filters.emitida"
          :options="emitidaOptions"
          label="Emissão"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        />
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Período de Emissão</div>

      <!-- Data Emissão Inicial -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.emissao_inicio"
          label="Emissão - De"
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

      <!-- Data Emissão Final -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.emissao_fim"
          label="Emissão - Até"
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

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Período de Saída</div>

      <!-- Data Saída Inicial -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.saida_inicio"
          label="Saída - De"
          outlined
          clearable
          mask="##/##/####"
          placeholder="DD/MM/AAAA"
          :bottom-slots="false"
        >
          <template v-slot:append>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="filters.saida_inicio" mask="DD/MM/YYYY">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Fechar" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </div>

      <!-- Data Saída Final -->
      <div class="q-mb-md">
        <q-input
          v-model="filters.saida_fim"
          label="Saída - Até"
          outlined
          clearable
          mask="##/##/####"
          placeholder="DD/MM/AAAA"
          :bottom-slots="false"
        >
          <template v-slot:append>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="filters.saida_fim" mask="DD/MM/YYYY">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup label="Fechar" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Valor Total</div>

      <!-- Valor Total - De -->
      <div class="q-mb-md">
        <q-input
          v-model.number="filters.valortotal_inicio"
          label="Valor Total - De"
          outlined
          clearable
          type="number"
          step="0.01"
          min="0"
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="attach_money" />
          </template>
        </q-input>
      </div>

      <!-- Valor Total - Até -->
      <div class="q-mb-md">
        <q-input
          v-model.number="filters.valortotal_fim"
          label="Valor Total - Até"
          outlined
          clearable
          type="number"
          step="0.01"
          min="0"
          :bottom-slots="false"
        >
          <template v-slot:prepend>
            <q-icon name="attach_money" />
          </template>
        </q-input>
      </div>

    </div>
  </div>
</template>
