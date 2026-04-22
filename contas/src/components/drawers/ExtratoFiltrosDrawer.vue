<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useExtratoStore } from 'src/stores/extratoStore'
import FilterDrawerShell from 'src/components/FilterDrawerShell.vue'
import FilterGroup from 'src/components/FilterGroup.vue'

const store = useExtratoStore()
const router = useRouter()
const route = useRoute()

function navegar(ano, mes) {
  router.push({
    name: 'extrato',
    params: { codportador: route.params.codportador, ano, mes },
  })
}

function onAnoChange(ano) {
  const meses = mesesDoAno(ano)
  const mes = meses.includes(store.mes) ? store.mes : meses[0]
  if (mes) navegar(ano, mes)
}

function mesesDoAno(ano) {
  if (!store.intervalo) return []
  const inicio = new Date(store.intervalo.primeira_data)
  const fim = new Date(store.intervalo.ultima_data)
  const out = []
  for (let m = 1; m <= 12; m++) {
    const ref = new Date(Number(ano), m - 1, 1)
    if (ref >= new Date(inicio.getFullYear(), inicio.getMonth(), 1) && ref <= fim) {
      out.push(String(m).padStart(2, '0'))
    }
  }
  return out
}

function onMesChange(mes) {
  navegar(store.ano, mes)
}

const mesAnteriorHabilitado = computed(() => {
  const idx = store.mesesDisponiveis.findIndex((m) => m.value === store.mes)
  if (idx > 0) return true
  const anoIdx = store.anosDisponiveis.indexOf(store.ano)
  return anoIdx > 0
})

const mesSeguinteHabilitado = computed(() => {
  const idx = store.mesesDisponiveis.findIndex((m) => m.value === store.mes)
  if (idx >= 0 && idx < store.mesesDisponiveis.length - 1) return true
  const anoIdx = store.anosDisponiveis.indexOf(store.ano)
  return anoIdx >= 0 && anoIdx < store.anosDisponiveis.length - 1
})

function mesAnterior() {
  const idx = store.mesesDisponiveis.findIndex((m) => m.value === store.mes)
  if (idx > 0) {
    navegar(store.ano, store.mesesDisponiveis[idx - 1].value)
    return
  }
  const anoIdx = store.anosDisponiveis.indexOf(store.ano)
  if (anoIdx > 0) {
    const novoAno = store.anosDisponiveis[anoIdx - 1]
    const meses = mesesDoAno(novoAno)
    navegar(novoAno, meses[meses.length - 1])
  }
}

function mesSeguinte() {
  const idx = store.mesesDisponiveis.findIndex((m) => m.value === store.mes)
  if (idx >= 0 && idx < store.mesesDisponiveis.length - 1) {
    navegar(store.ano, store.mesesDisponiveis[idx + 1].value)
    return
  }
  const anoIdx = store.anosDisponiveis.indexOf(store.ano)
  if (anoIdx >= 0 && anoIdx < store.anosDisponiveis.length - 1) {
    const novoAno = store.anosDisponiveis[anoIdx + 1]
    const meses = mesesDoAno(novoAno)
    navegar(novoAno, meses[0])
  }
}

const diaAnteriorHabilitado = computed(() => {
  const idx = store.diasDoMes.indexOf(store.diaSelecionado)
  return idx > 0
})

const diaSeguinteHabilitado = computed(() => {
  const idx = store.diasDoMes.indexOf(store.diaSelecionado)
  return idx >= 0 && idx < store.diasDoMes.length - 1
})

function diaAnterior() {
  const idx = store.diasDoMes.indexOf(store.diaSelecionado)
  if (idx > 0) store.diaSelecionado = store.diasDoMes[idx - 1]
}

function diaSeguinte() {
  const idx = store.diasDoMes.indexOf(store.diaSelecionado)
  if (idx >= 0 && idx < store.diasDoMes.length - 1) {
    store.diaSelecionado = store.diasDoMes[idx + 1]
  }
}
</script>

<template>
  <FilterDrawerShell title="Navegação" :active-count="0">
    <FilterGroup title="Período" first>
      <q-select
        :model-value="store.ano"
        @update:model-value="onAnoChange"
        :options="store.anosDisponiveis"
        label="Ano"
        outlined
        :bottom-slots="false"
        class="q-mb-md"
      />

      <div class="row items-center no-wrap">
        <q-btn
          flat
          round
          dense
          icon="chevron_left"
          color="grey-7"
          :disable="!mesAnteriorHabilitado"
          @click="mesAnterior"
        />
        <q-select
          :model-value="store.mes"
          @update:model-value="onMesChange"
          :options="store.mesesDisponiveis"
          emit-value
          map-options
          label="Mês"
          outlined
          :bottom-slots="false"
          class="col q-mx-sm"
        />
        <q-btn
          flat
          round
          dense
          icon="chevron_right"
          color="grey-7"
          :disable="!mesSeguinteHabilitado"
          @click="mesSeguinte"
        />
      </div>
    </FilterGroup>

    <FilterGroup title="Dia">
      <div class="row items-center no-wrap">
        <q-btn
          flat
          round
          dense
          icon="chevron_left"
          color="grey-7"
          :disable="!diaAnteriorHabilitado"
          @click="diaAnterior"
        />
        <q-select
          v-model="store.diaSelecionado"
          :options="store.diasDoMes"
          label="Dia"
          outlined
          :bottom-slots="false"
          :disable="!store.diasDoMes.length"
          class="col q-mx-sm"
        />
        <q-btn
          flat
          round
          dense
          icon="chevron_right"
          color="grey-7"
          :disable="!diaSeguinteHabilitado"
          @click="diaSeguinte"
        />
      </div>
    </FilterGroup>
  </FilterDrawerShell>
</template>
