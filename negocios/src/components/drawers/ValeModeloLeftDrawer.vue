<script setup>
import { watch, onUnmounted } from 'vue'
import { valeModeloStore } from 'stores/valeModelo'
import FilterDrawerShell from 'components/FilterDrawerShell.vue'
import FilterGroup from 'components/FilterGroup.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'

const sVale = valeModeloStore()

// Uma espera só para o filtro inteiro: o MgInputValor emite a cada tecla
// digitada, e sem isso seria uma requisição por dígito.
let timer = null
watch(
  () => sVale.filtros,
  () => {
    clearTimeout(timer)
    timer = setTimeout(() => sVale.carregar(1), 500)
  },
  { deep: true },
)
onUnmounted(() => clearTimeout(timer))

// O watch acima recarrega; chamar aqui tambem faria duas requisicoes.
const limpar = () => sVale.limparFiltros()

const situacaoOptions = [
  { label: 'Ativos', value: 1 },
  { label: 'Inativos', value: 2 },
  { label: 'Todos', value: 9 },
]
</script>

<template>
  <FilterDrawerShell :active-count="sVale.filtrosAtivos" @clear="limpar">
    <FilterGroup title="Favorecido" first>
      <MgSelectPessoa
        v-model="sVale.filtros.codpessoafavorecido"
        label="Escola"
        clearable
        :bottom-slots="false"
      />
    </FilterGroup>

    <FilterGroup title="Descrição">
      <q-input
        v-model="sVale.filtros.modelo"
        outlined
        clearable
        :bottom-slots="false"
        label="Descrição"
      >
        <template #prepend><q-icon name="search" /></template>
      </q-input>
    </FilterGroup>

    <FilterGroup title="Valor">
      <div class="row q-col-gutter-sm">
        <div class="col-6">
          <MgInputValor
            v-model="sVale.filtros.valorde"
            clearable
            :bottom-slots="false"
            label="De R$"
          />
        </div>
        <div class="col-6">
          <MgInputValor
            v-model="sVale.filtros.valorate"
            clearable
            :bottom-slots="false"
            label="Até R$"
          />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Situação">
      <q-btn-toggle
        v-model="sVale.filtros.inativo"
        spread
        no-caps
        flat
        toggle-color="primary"
        :options="situacaoOptions"
      />
    </FilterGroup>
  </FilterDrawerShell>
</template>
