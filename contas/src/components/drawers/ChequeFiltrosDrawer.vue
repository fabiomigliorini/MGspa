<script setup>
import { watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useChequeStore } from 'src/stores/chequeStore'
import { CHEQUE_STATUS_OPTIONS } from 'src/constants/chequeStatus'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelectBanco from '@components/MgSelectBanco.vue'
import SelectPessoa from '@components/MgSelectPessoa.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'

const store = useChequeStore()

const debouncedFetch = useDebounceFn(() => store.fetchItems(true), 800)
watch(() => store.filters, debouncedFetch, { deep: true })

const clear = () => {
  store.clearFilters()
  store.fetchItems(true)
}
</script>

<template>
  <FilterDrawerShell :active-count="store.activeFiltersCount" @clear="clear">
    <FilterGroup title="Identificação" first>
      <q-input
        v-model.number="store.filters.codcheque"
        outlined
        clearable
        :bottom-slots="false"
        type="number"
        label="Código"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="numbers" /></template>
      </q-input>

      <MgSelectBanco
        v-model="store.filters.codbanco"
        outlined
        clearable
        :bottom-slots="false"
        label="Banco"
        class="q-mb-sm"
      />

      <q-input
        v-model.number="store.filters.agencia"
        outlined
        clearable
        :bottom-slots="false"
        type="number"
        label="Agência"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="account_balance" /></template>
      </q-input>

      <q-input
        v-model="store.filters.numero"
        outlined
        clearable
        :bottom-slots="false"
        label="Número"
      >
        <template #prepend><q-icon name="pin" /></template>
      </q-input>
    </FilterGroup>

    <FilterGroup title="Cliente / Emitente">
      <SelectPessoa
        v-model="store.filters.codpessoa"
        outlined
        clearable
        :bottom-slots="false"
        label="Cliente"
        class="q-mb-sm"
      />

      <q-input
        v-model="store.filters.emitente"
        outlined
        clearable
        :bottom-slots="false"
        label="Emitente"
      >
        <template #prepend><q-icon name="person" /></template>
      </q-input>
    </FilterGroup>

    <FilterGroup title="Valor">
      <MgInputValor
        v-model="store.filters.valor_de"
        clearable
        :bottom-slots="false"
        prefix="R$"
        label="Valor de"
        class="q-mb-sm"
      />
      <MgInputValor
        v-model="store.filters.valor_ate"
        clearable
        :bottom-slots="false"
        prefix="R$"
        label="Valor até"
      />
    </FilterGroup>

    <FilterGroup title="Status e Vencimento">
      <q-select
        v-model="store.filters.indstatus"
        :options="CHEQUE_STATUS_OPTIONS"
        emit-value
        map-options
        outlined
        clearable
        :bottom-slots="false"
        label="Status"
        class="q-mb-sm"
      >
        <template #prepend><q-icon name="flag" /></template>
      </q-select>

      <MgInputData v-model="store.filters.vencimento_de" label="Vencimento de" class="q-mb-sm" />
      <MgInputData v-model="store.filters.vencimento_ate" label="Vencimento até" />
    </FilterGroup>
  </FilterDrawerShell>
</template>
