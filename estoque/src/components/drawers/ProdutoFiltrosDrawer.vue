<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useProdutoStore } from 'src/stores/produtoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgAutocomplete from 'src/components/MgAutocomplete.vue'

const store = useProdutoStore()
const f = store.filters

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}

// Cascata: ao mudar um nível, limpa os abaixo
watch(
  () => f.codsecaoproduto,
  () => {
    f.codfamiliaproduto = null
    f.codgrupoproduto = null
    f.codsubgrupoproduto = null
  },
)
watch(
  () => f.codfamiliaproduto,
  () => {
    f.codgrupoproduto = null
    f.codsubgrupoproduto = null
  },
)
watch(
  () => f.codgrupoproduto,
  () => {
    f.codsubgrupoproduto = null
  },
)

const statusOptions = [
  { label: 'Ativos', value: 1 },
  { label: 'Inativos', value: 2 },
  { label: 'Todos', value: null },
]
const siteOptions = [
  { label: 'Indiferente', value: null },
  { label: 'No site', value: true },
  { label: 'Fora do site', value: false },
]
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-input
        v-model.number="f.codproduto"
        outlined
        clearable
        :bottom-slots="false"
        type="number"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </q-input>
      <q-input
        v-model="f.produto"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>
      <q-input
        v-model="f.barras"
        outlined
        clearable
        :bottom-slots="false"
        label="Código de barras"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="qr_code_2" /></template>
      </q-input>
      <q-input
        v-model="f.referencia"
        outlined
        clearable
        :bottom-slots="false"
        label="Referência"
      >
        <template #prepend><q-icon name="tag" /></template>
      </q-input>
    </FilterGroup>

    <FilterGroup title="Preço">
      <div class="row q-col-gutter-sm">
        <div class="col-6">
          <q-input
            v-model.number="f.preco_de"
            outlined
            clearable
            :bottom-slots="false"
            type="number"
            label="De"
          />
        </div>
        <div class="col-6">
          <q-input
            v-model.number="f.preco_ate"
            outlined
            clearable
            :bottom-slots="false"
            type="number"
            label="Até"
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Classificação">
      <MgAutocomplete
        v-model="f.codsecaoproduto"
        endpoint="v1/secao-produto/autocompletar"
        search-param="secaoproduto"
        label="Seção"
        class="q-mb-sm"
      />
      <MgAutocomplete
        v-model="f.codfamiliaproduto"
        endpoint="v1/familia-produto/autocompletar"
        search-param="familiaproduto"
        label="Família"
        :extra-params="{ codsecaoproduto: f.codsecaoproduto }"
        class="q-mb-sm"
      />
      <MgAutocomplete
        v-model="f.codgrupoproduto"
        endpoint="v1/grupo-produto/autocompletar"
        search-param="grupoproduto"
        label="Grupo"
        :extra-params="{ codfamiliaproduto: f.codfamiliaproduto }"
        class="q-mb-sm"
      />
      <MgAutocomplete
        v-model="f.codsubgrupoproduto"
        endpoint="v1/subgrupo-produto/autocompletar"
        search-param="subgrupoproduto"
        label="Subgrupo"
        :extra-params="{ codgrupoproduto: f.codgrupoproduto }"
        class="q-mb-sm"
      />
      <MgAutocomplete
        v-model="f.codmarca"
        endpoint="v1/marca/autocompletar"
        search-param="marca"
        label="Marca"
        class="q-mb-sm"
      />
      <MgAutocomplete
        v-model="f.codncm"
        endpoint="v1/ncm/autocompletar"
        search-param="busca"
        label="NCM"
      />
    </FilterGroup>

    <FilterGroup title="Status">
      <q-select
        v-model="f.site"
        :options="siteOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Site"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="language" /></template>
      </q-select>
      <q-select
        v-model="f.inativo"
        :options="statusOptions"
        emit-value
        map-options
        outlined
        :bottom-slots="false"
        label="Situação"
      >
        <template #prepend><q-icon name="toggle_on" /></template>
      </q-select>
    </FilterGroup>
  </FilterDrawerShell>
</template>
