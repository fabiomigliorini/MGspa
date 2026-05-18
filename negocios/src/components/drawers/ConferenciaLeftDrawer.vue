<script setup>
import { onMounted } from 'vue'
import { conferenciaStore } from 'src/stores/conferencia'
import SelectPdv from 'components/selects/SelectPdv.vue'
import MgInputData from '@components/MgInputData.vue'
const sConferencia = conferenciaStore()

onMounted(() => {
  sConferencia.inicializaFiltro()
})
</script>
<template>
  <q-list>
    <q-item-label header>Filtro </q-item-label>

    <!-- PDV -->
    <q-item>
      <q-item-section>
        <select-pdv outlined v-model="sConferencia.filtro.codpdv" label="PDV" clearable />
      </q-item-section>
    </q-item>

    <!-- DIA -->
    <q-item>
      <q-item-section>
        <MgInputData type="date" v-model="sConferencia.filtro.dia" label="Dia" />
      </q-item-section>
    </q-item>
  </q-list>
</template>
