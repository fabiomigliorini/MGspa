<template>
  <div class="column full-height">
    <!-- Header -->
    <div class="q-pa-md bg-primary text-white">
      <div class="text-h6">
        <q-icon name="filter_list" class="q-mr-sm" />
        Filtros
      </div>
      <div class="text-caption">
        {{ store.activeFiltersCount }}
        {{ store.activeFiltersCount === 1 ? 'filtro ativo' : 'filtros ativos' }}
      </div>
    </div>

    <q-separator />

    <!-- Filtros -->
    <q-scroll-area class="col">
      <div class="q-pa-md">
        <!-- Natureza de Operação -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Natureza de Operação</div>
          <q-select
            v-model="store.filters.codnaturezaoperacao"
            outlined
            dense
            clearable
            use-input
            input-debounce="300"
            placeholder="Selecione ou busque"
            :options="naturezaOptions"
            @filter="filterNatureza"
          >
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey"> Nenhum resultado </q-item-section>
              </q-item>
            </template>
          </q-select>
        </div>

        <!-- NCM -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">NCM</div>
          <q-input
            v-model="store.filters.ncm"
            outlined
            dense
            clearable
            placeholder="Ex: 8471.30.12"
            mask="####.##.##"
          />
        </div>

        <!-- Cidade Destino -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Cidade Destino</div>
          <q-select
            v-model="store.filters.codcidadedestino"
            outlined
            dense
            clearable
            use-input
            input-debounce="300"
            placeholder="Selecione ou busque"
            :options="cidadeOptions"
            @filter="filterCidade"
          >
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey"> Nenhum resultado </q-item-section>
              </q-item>
            </template>
          </q-select>
        </div>

        <!-- Estado Destino -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Estado Destino</div>
          <q-select
            v-model="store.filters.codestadodestino"
            outlined
            dense
            clearable
            :options="estadoOptions"
            placeholder="Selecione"
          />
        </div>

        <q-separator class="q-my-md" />

        <!-- Base Percentual -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Base Percentual (%)</div>
          <q-input
            v-model.number="store.filters.basepercentual"
            outlined
            dense
            clearable
            type="number"
            min="0"
            max="100"
            step="0.01"
            placeholder="Ex: 100"
          />
        </div>

        <!-- Alíquota -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Alíquota (%)</div>
          <q-input
            v-model.number="store.filters.aliquota"
            outlined
            dense
            clearable
            type="number"
            min="0"
            max="100"
            step="0.01"
            placeholder="Ex: 8.5"
          />
        </div>

        <!-- CST -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">CST</div>
          <q-input
            v-model="store.filters.cst"
            outlined
            dense
            clearable
            placeholder="Ex: 01"
            maxlength="2"
          />
        </div>

        <!-- Classificação Tributária -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Classificação Tributária</div>
          <q-input
            v-model="store.filters.cclasstrib"
            outlined
            dense
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
          <div class="text-subtitle2 q-mb-xs">Código Benefício</div>
          <q-input
            v-model="store.filters.beneficiocodigo"
            outlined
            dense
            clearable
            placeholder="Ex: BE001"
          />
        </div>

        <!-- Vigência -->
        <div class="q-mb-md">
          <div class="text-subtitle2 q-mb-xs">Vigência</div>
          <q-select
            v-model="store.filters.vigencia"
            outlined
            dense
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
    </q-scroll-area>

    <!-- Footer com botões -->
    <q-separator />
    <div class="q-pa-md">
      <q-btn
        unelevated
        color="primary"
        label="Aplicar Filtros"
        icon="check"
        class="full-width q-mb-sm"
        @click="aplicarFiltros"
        :loading="store.isLoading"
      />
      <q-btn
        outline
        color="grey-8"
        label="Limpar Filtros"
        icon="clear"
        class="full-width"
        @click="limparFiltros"
        :disable="!store.hasActiveFilters"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useTributacaoStore } from 'stores/tributacao'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const store = useTributacaoStore()

// Estados (UF)
const estadoOptions = [
  'AC',
  'AL',
  'AP',
  'AM',
  'BA',
  'CE',
  'DF',
  'ES',
  'GO',
  'MA',
  'MT',
  'MS',
  'MG',
  'PA',
  'PB',
  'PR',
  'PE',
  'PI',
  'RJ',
  'RN',
  'RS',
  'RO',
  'RR',
  'SC',
  'SP',
  'SE',
  'TO',
]

// Options para autocomplete (serão populadas via API em implementação futura)
const naturezaOptions = ref([])
const cidadeOptions = ref([])

// Filtros de autocomplete
const filterNatureza = (val, update) => {
  update(() => {
    // TODO: Buscar da API
    naturezaOptions.value = []
  })
}

const filterCidade = (val, update) => {
  update(() => {
    // TODO: Buscar da API
    cidadeOptions.value = []
  })
}

// Ações
const aplicarFiltros = async () => {
  try {
    await store.applyFilters()
    $q.notify({
      type: 'positive',
      message: 'Filtros aplicados com sucesso',
      icon: 'check_circle',
      timeout: 1000,
    })
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Erro ao aplicar filtros',
      caption: error.message,
    })
  }
}

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
