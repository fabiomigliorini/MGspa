<script setup>
import { watch, ref, computed } from 'vue'
import { useNotaFiscalStore } from '../../stores/notaFiscalStore'
import { useDebounceFn } from '@vueuse/core'
import SelectFilial from '../selects/SelectFilial.vue'
import SelectNaturezaOperacao from '../selects/SelectNaturezaOperacao.vue'
import SelectGrupoEconomico from '../selects/SelectGrupoEconomico.vue'
import SelectPessoa from '../selects/SelectPessoa.vue'
import MgInputDate from '../MgInputDate.vue'
import {
  MODELO_OPTIONS,
  STATUS_OPTIONS,
  EMITIDA_OPTIONS,
  OPERACAO_OPTIONS,
} from '../../constants/notaFiscal'

const notaFiscalStore = useNotaFiscalStore()
const updatingFromPessoa = ref(false)

const modeloOptions = MODELO_OPTIONS
const statusOptions = STATUS_OPTIONS
const emitidaOptions = EMITIDA_OPTIONS
const operacaoOptions = OPERACAO_OPTIONS

const activeFiltersCount = computed(() => notaFiscalStore.activeFiltersCount)

// Debounce para aplicar filtros automaticamente
const debouncedFetch = useDebounceFn(() => {
  notaFiscalStore.fetchNotas(true)
}, 800)

watch(
  () => notaFiscalStore.filters,
  () => {
    debouncedFetch()
  },
  { deep: true }
)

const handleClearFilters = () => {
  notaFiscalStore.clearFilters()
  notaFiscalStore.fetchNotas(true)
}

// Handler para quando uma pessoa é selecionada
const handlePessoaSelect = (pessoa) => {
  updatingFromPessoa.value = true
  notaFiscalStore.filters.codgrupoeconomico = pessoa.codgrupoeconomico || null
  setTimeout(() => {
    updatingFromPessoa.value = false
  }, 100)
}

// Observa mudanças no grupo econômico
watch(
  () => notaFiscalStore.filters.codgrupoeconomico,
  (newValue, oldValue) => {
    if (!updatingFromPessoa.value && newValue !== oldValue) {
      notaFiscalStore.filters.codpessoa = null
    }
  }
)
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
      <div class="text-caption text-grey-7 q-mb-md">Tipo e Status</div>

      <!-- Status -->
      <div class="q-mb-md">
        <q-select
          v-model="notaFiscalStore.filters.status"
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

      <!-- Modelo -->
      <div class="q-mb-md">
        <q-select
          v-model="notaFiscalStore.filters.modelo"
          :options="modeloOptions"
          label="Modelo"
          outlined
          clearable
          emit-value
          map-options
          :bottom-slots="false"
        />
      </div>

      <!-- Emitida -->
      <div class="q-mb-md">
        <q-select
          v-model="notaFiscalStore.filters.emitida"
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

      <div class="text-caption text-grey-7 q-mb-md">Busque pela Nota</div>

      <!-- Número -->
      <div class="q-mb-md">
        <q-input
          v-model="notaFiscalStore.filters.numero"
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
        <q-input v-model="notaFiscalStore.filters.serie" label="Série" outlined clearable :bottom-slots="false">
          <template v-slot:prepend>
            <q-icon name="numbers" />
          </template>
        </q-input>
      </div>

      <!-- Chave NFe -->
      <div class="q-mb-md">
        <q-input
          v-model="notaFiscalStore.filters.nfechave"
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
        <SelectFilial v-model="notaFiscalStore.filters.codfilial" label="Filial" :bottom-slots="false" />
      </div>

      <!-- Natureza de Operação -->
      <div class="q-mb-md">
        <SelectNaturezaOperacao
          v-model="notaFiscalStore.filters.codnaturezaoperacao"
          label="Natureza de Operação"
          :bottom-slots="false"
        />
      </div>

      <!-- Pessoa (Cliente/Fornecedor) -->
      <div class="q-mb-md">
        <SelectPessoa
          v-model="notaFiscalStore.filters.codpessoa"
          label="Pessoa (Cliente/Fornecedor)"
          :bottom-slots="false"
          @select="handlePessoaSelect"
        />
      </div>

      <!-- Grupo Econômico -->
      <div class="q-mb-md">
        <SelectGrupoEconomico
          v-model="notaFiscalStore.filters.codgrupoeconomico"
          label="Grupo Econômico"
          :bottom-slots="false"
        />
      </div>

      <!-- Operação -->
      <div class="q-mb-md">
        <q-select
          v-model="notaFiscalStore.filters.codoperacao"
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

      <div class="text-caption text-grey-7 q-mb-md">Período de Emissão</div>

      <div class="q-mb-md">
        <MgInputDate v-model="notaFiscalStore.filters.emissao_inicio" label="Emissão - De" />
      </div>

      <div class="q-mb-md">
        <MgInputDate v-model="notaFiscalStore.filters.emissao_fim" label="Emissão - Até" />
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Período de Saída</div>

      <div class="q-mb-md">
        <MgInputDate v-model="notaFiscalStore.filters.saida_inicio" label="Saída - De" />
      </div>

      <div class="q-mb-md">
        <MgInputDate v-model="notaFiscalStore.filters.saida_fim" label="Saída - Até" />
      </div>

      <q-separator class="q-my-md" />

      <div class="text-caption text-grey-7 q-mb-md">Valor Total</div>

      <!-- Valor Total - De -->
      <div class="q-mb-md">
        <q-input
          v-model.number="notaFiscalStore.filters.valortotal_inicio"
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
          v-model.number="notaFiscalStore.filters.valortotal_fim"
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
