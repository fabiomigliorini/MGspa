<script setup>
import { reactive, onMounted, watch, ref } from 'vue'
import { useNotaFiscalStore } from '../../stores/notaFiscalStore'
import SelectFilial from '../selects/SelectFilial.vue'
import SelectNaturezaOperacao from '../selects/SelectNaturezaOperacao.vue'
import SelectGrupoEconomico from '../selects/SelectGrupoEconomico.vue'
import SelectPessoa from '../selects/SelectPessoa.vue'

const notaFiscalStore = useNotaFiscalStore()
const updatingFromPessoa = ref(false)

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

const modeloOptions = [
  { label: 'NF-e (55)', value: '55' },
  { label: 'NFC-e (65)', value: '65' },
]

const statusOptions = [
  { label: 'Pendente', value: 'Pendente' },
  { label: 'Autorizada', value: 'Autorizada' },
  { label: 'Cancelada', value: 'Cancelada' },
  { label: 'Inutilizada', value: 'Inutilizada' },
]

const emitidaOptions = [
  { label: 'Nossa Emissão', value: true },
  { label: 'Emitida por Terceiro', value: false },
]

const operacaoOptions = [
  { label: 'Entrada', value: 0 },
  { label: 'Saída', value: 1 },
]

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
})
</script>

<template>
  <div class="q-pa-md">
    <div class="text-h6 q-mb-md">Filtros</div>

    <!-- Seção: Identificação -->
    <div class="text-subtitle2 text-grey-7 q-mb-sm">Identificação</div>

    <!-- Número -->
    <q-input
      v-model="filters.numero"
      label="Número"
      outlined
      dense
      class="q-mb-md"
      clearable
      type="number"
    >
      <template v-slot:prepend>
        <q-icon name="tag" />
      </template>
    </q-input>

    <!-- Série -->
    <q-input v-model="filters.serie" label="Série" outlined dense class="q-mb-md" clearable>
      <template v-slot:prepend>
        <q-icon name="numbers" />
      </template>
    </q-input>

    <!-- Chave NFe -->
    <q-input v-model="filters.nfechave" label="Chave NFe" outlined dense class="q-mb-md" clearable>
      <template v-slot:prepend>
        <q-icon name="vpn_key" />
      </template>
    </q-input>

    <q-separator class="q-my-md" />

    <!-- Seção: Relacionamentos -->
    <div class="text-subtitle2 text-grey-7 q-mb-sm">Relacionamentos</div>

    <!-- Filial -->
    <SelectFilial v-model="filters.codfilial" label="Filial" dense class="q-mb-md" />

    <!-- Natureza de Operação -->
    <SelectNaturezaOperacao
      v-model="filters.codnaturezaoperacao"
      label="Natureza de Operação"
      dense
      class="q-mb-md"
    />

    <!-- Pessoa (Cliente/Fornecedor) -->
    <SelectPessoa
      v-model="filters.codpessoa"
      label="Pessoa (Cliente/Fornecedor)"
      dense
      class="q-mb-md"
      @select="handlePessoaSelect"
    />

    <!-- Grupo Econômico -->
    <SelectGrupoEconomico
      v-model="filters.codgrupoeconomico"
      label="Grupo Econômico"
      dense
      class="q-mb-md"
    />

    <!-- Operação -->
    <q-select
      v-model="filters.codoperacao"
      :options="operacaoOptions"
      label="Operação"
      outlined
      dense
      class="q-mb-md"
      clearable
      emit-value
      map-options
    />

    <q-separator class="q-my-md" />

    <!-- Seção: Tipo e Status -->
    <div class="text-subtitle2 text-grey-7 q-mb-sm">Tipo e Status</div>

    <!-- Modelo -->
    <q-select
      v-model="filters.modelo"
      :options="modeloOptions"
      label="Modelo"
      outlined
      dense
      class="q-mb-md"
      clearable
      emit-value
      map-options
    />

    <!-- Status -->
    <q-select
      v-model="filters.status"
      :options="statusOptions"
      label="Status"
      outlined
      dense
      class="q-mb-md"
      clearable
      emit-value
      map-options
    />

    <!-- Emitida -->
    <q-select
      v-model="filters.emitida"
      :options="emitidaOptions"
      label="Emissão"
      outlined
      dense
      class="q-mb-md"
      clearable
      emit-value
      map-options
    />

    <q-separator class="q-my-md" />

    <!-- Seção: Período de Emissão -->
    <div class="text-subtitle2 text-grey-7 q-mb-sm">Período de Emissão</div>

    <!-- Data Emissão Inicial -->
    <q-input
      v-model="filters.emissao_inicio"
      label="Emissão - De"
      outlined
      dense
      class="q-mb-md"
      clearable
      mask="##/##/####"
      placeholder="DD/MM/AAAA"
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

    <!-- Data Emissão Final -->
    <q-input
      v-model="filters.emissao_fim"
      label="Emissão - Até"
      outlined
      dense
      class="q-mb-md"
      clearable
      mask="##/##/####"
      placeholder="DD/MM/AAAA"
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

    <q-separator class="q-my-md" />

    <!-- Seção: Período de Saída -->
    <div class="text-subtitle2 text-grey-7 q-mb-sm">Período de Saída</div>

    <!-- Data Saída Inicial -->
    <q-input
      v-model="filters.saida_inicio"
      label="Saída - De"
      outlined
      dense
      class="q-mb-md"
      clearable
      mask="##/##/####"
      placeholder="DD/MM/AAAA"
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

    <!-- Data Saída Final -->
    <q-input
      v-model="filters.saida_fim"
      label="Saída - Até"
      outlined
      dense
      class="q-mb-md"
      clearable
      mask="##/##/####"
      placeholder="DD/MM/AAAA"
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

    <q-separator class="q-my-md" />

    <!-- Seção: Valor Total -->
    <div class="text-subtitle2 text-grey-7 q-mb-sm">Valor Total</div>

    <!-- Valor Total - De -->
    <q-input
      v-model.number="filters.valortotal_inicio"
      label="Valor Total - De"
      outlined
      dense
      class="q-mb-md"
      clearable
      type="number"
      step="0.01"
      min="0"
    >
      <template v-slot:prepend>
        <q-icon name="attach_money" />
      </template>
    </q-input>

    <!-- Valor Total - Até -->
    <q-input
      v-model.number="filters.valortotal_fim"
      label="Valor Total - Até"
      outlined
      dense
      class="q-mb-md"
      clearable
      type="number"
      step="0.01"
      min="0"
    >
      <template v-slot:prepend>
        <q-icon name="attach_money" />
      </template>
    </q-input>

    <q-separator class="q-my-md" />

    <!-- Botões -->
    <q-btn
      color="primary"
      label="Filtrar"
      icon="search"
      class="full-width q-mb-sm"
      @click="handleFilter"
    />

    <q-btn
      outline
      color="primary"
      label="Limpar Filtros"
      icon="clear"
      class="full-width"
      @click="handleClearFilters"
    />
  </div>
</template>
