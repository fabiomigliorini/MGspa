<script setup>
import { computed, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useCaixaStore } from 'src/stores/caixaStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgInputData from '@components/MgInputData.vue'

const store = useCaixaStore()

const debounced = useDebounceFn(() => store.atualizar(), 800)
watch(() => store.filtros, debounced, { deep: true })

const ativos = computed(
  () => ['codfilial', 'transacao_de', 'transacao_ate'].filter((k) => store.filtros[k]).length,
)
</script>

<template>
  <FilterDrawerShell :active-count="ativos" @clear="store.limpar()">
    <FilterGroup title="Filial" first>
      <MgSelectFilial
        v-model="store.filtros.codfilial"
        outlined
        clearable
        :bottom-slots="false"
        label="Filial"
      />
    </FilterGroup>
    <FilterGroup title="Período">
      <MgInputData
        v-model="store.filtros.transacao_de"
        label="De"
        :bottom-slots="false"
        class="q-mb-sm"
      />
      <MgInputData
        v-model="store.filtros.transacao_ate"
        label="Até"
        default-time="end"
        :bottom-slots="false"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
