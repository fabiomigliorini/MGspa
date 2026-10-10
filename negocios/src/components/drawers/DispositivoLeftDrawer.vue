<script setup>
import { watch, onUnmounted } from 'vue'
import { dispositivoStore } from 'stores/dispositivo'
import FilterDrawerShell from 'components/FilterDrawerShell.vue'
import FilterGroup from 'components/FilterGroup.vue'
import MgInput from '@components/MgInput.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectSetor from '@components/MgSelectSetor.vue'

const sDispositivo = dispositivoStore()

// uma espera so' para o filtro inteiro: sem isso seria uma requisicao por tecla
let timer = null
watch(
  () => sDispositivo.filtros,
  () => {
    clearTimeout(timer)
    timer = setTimeout(() => sDispositivo.carregar(), 500)
  },
  { deep: true },
)
onUnmounted(() => clearTimeout(timer))

// o watch acima recarrega; chamar aqui tambem faria duas requisicoes
const limpar = () => sDispositivo.limparFiltros()

// ativo = autorizado; o dispositivo novo nasce inativo
const statusOptions = [
  { label: 'Ativos', value: '' },
  { label: 'Inativos', value: 'inativo' },
  { label: 'Todos', value: 'todos' },
]
</script>

<template>
  <FilterDrawerShell :active-count="sDispositivo.filtrosAtivos" @clear="limpar">
    <FilterGroup title="Situação" first>
      <q-option-group v-model="sDispositivo.filtros.status" :options="statusOptions" type="radio" />
    </FilterGroup>

    <FilterGroup title="Apelido">
      <MgInput
        v-model="sDispositivo.filtros.apelido"
        clearable
        :bottom-slots="false"
        label="Apelido"
      >
        <template #prepend><q-icon name="search" /></template>
      </MgInput>
    </FilterGroup>

    <FilterGroup title="Onde">
      <div class="row q-col-gutter-sm">
        <div class="col-12">
          <MgSelectFilial
            v-model="sDispositivo.filtros.codfilial"
            clearable
            :bottom-slots="false"
          />
        </div>
        <div class="col-12">
          <MgSelectSetor v-model="sDispositivo.filtros.codsetor" clearable :bottom-slots="false" />
        </div>
      </div>
    </FilterGroup>

    <FilterGroup title="Rede">
      <div class="row q-col-gutter-sm">
        <div class="col-12">
          <MgInput v-model="sDispositivo.filtros.ip" clearable :bottom-slots="false" label="IP" />
        </div>
        <div class="col-12">
          <MgInput
            v-model="sDispositivo.filtros.uuid"
            clearable
            :bottom-slots="false"
            label="UUID"
          />
        </div>
      </div>
    </FilterGroup>
  </FilterDrawerShell>
</template>
