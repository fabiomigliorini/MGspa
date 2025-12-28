<template>
  <div class="q-pa-md">
    <div class="text-h6 q-mb-md">Filtros</div>

    <q-input
      v-model="filtros.numero"
      label="Número da Nota"
      outlined
      dense
      class="q-mb-md"
      clearable
    />

    <q-input
      v-model="filtros.dataInicio"
      label="Data Início"
      type="date"
      outlined
      dense
      class="q-mb-md"
    />

    <q-input
      v-model="filtros.dataFim"
      label="Data Fim"
      type="date"
      outlined
      dense
      class="q-mb-md"
    />

    <q-select
      v-model="filtros.status"
      :options="statusOptions"
      label="Status"
      outlined
      dense
      class="q-mb-md"
      clearable
    />

    <q-separator class="q-my-md" />

    <q-btn
      color="primary"
      label="Filtrar"
      icon="search"
      class="full-width q-mb-sm"
      @click="aplicarFiltros"
    />

    <q-btn
      flat
      color="grey"
      label="Limpar"
      icon="clear"
      class="full-width"
      @click="limparFiltros"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'

const filtros = ref({
  numero: '',
  dataInicio: '',
  dataFim: '',
  status: null,
})

const statusOptions = ['Autorizada', 'Cancelada', 'Pendente', 'Rejeitada']

function aplicarFiltros() {
  console.log('Filtros aplicados:', filtros.value)
  // Emitir evento ou chamar API
}

function limparFiltros() {
  filtros.value = {
    numero: '',
    dataInicio: '',
    dataFim: '',
    status: null,
  }
}
</script>
