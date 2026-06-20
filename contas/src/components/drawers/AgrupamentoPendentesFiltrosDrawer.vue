<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useAgrupamentoPendenteStore } from 'src/stores/agrupamentoPendenteStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import SelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectGrupoEconomico from '@components/MgSelectGrupoEconomico.vue'
import SelectGrupoCliente from 'src/components/select/SelectGrupoCliente.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'
import MgSelectTipoTitulo from '@components/MgSelectTipoTitulo.vue'
import MgSelectFormaPagamento from '@components/MgSelectFormaPagamento.vue'
import MgInputData from '@components/MgInputData.vue'
import MgInputValor from '@components/MgInputValor.vue'

const store = useAgrupamentoPendenteStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

function clear() {
  store.clearFilters()
  store.fetchItems()
}
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Cliente / Portador" first>
      <SelectGrupoCliente
        v-model="store.filters.codgrupocliente"
        outlined
        :bottom-slots="false"
        label="Grupos de Cliente"
        class="q-mb-md"
      />
      <MgSelectPortador
        v-model="store.filters.codportador"
        multiple
        outlined
        clearable
        :bottom-slots="false"
        label="Portadores"
      />
    </FilterGroup>

    <FilterGroup title="Pessoa">
      <MgSelectGrupoEconomico
        v-model="store.filters.codgrupoeconomico"
        outlined
        clearable
        :bottom-slots="false"
        label="Grupo Econômico"
        class="q-mb-md"
      />
      <SelectPessoa
        v-model="store.filters.codpessoa"
        outlined
        clearable
        :bottom-slots="false"
        label="Pessoa"
      />
    </FilterGroup>

    <FilterGroup title="Tipo / Forma">
      <MgSelectTipoTitulo
        v-model="store.filters.codtipotitulo"
        outlined
        clearable
        :bottom-slots="false"
        label="Tipo de Título"
        class="q-mb-md"
      />
      <MgSelectFormaPagamento
        v-model="store.filters.codformapagamento"
        outlined
        clearable
        :bottom-slots="false"
        label="Forma de Pagamento"
      />
    </FilterGroup>

    <FilterGroup title="Vencimento / Valor">
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-6">
          <MgInputData
            v-model="store.filters.vencimento_de"
            :bottom-slots="false"
            type="date"
            label="Vencimento"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputData
            v-model="store.filters.vencimento_ate"
            :bottom-slots="false"
            type="date"
            label="Até"
            stack-label
          />
        </div>
      </div>
      <div class="row q-col-gutter-md">
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.valor_de"
            :bottom-slots="false"
            label="Valor"
            stack-label
          />
        </div>
        <div class="col-6">
          <MgInputValor
            v-model="store.filters.valor_ate"
            :bottom-slots="false"
            label="Até"
            stack-label
          />
        </div>
      </div>
    </FilterGroup>
  </FilterDrawerShell>
</template>
