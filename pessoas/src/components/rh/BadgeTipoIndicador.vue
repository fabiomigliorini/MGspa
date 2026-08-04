<script setup>
import { tipoIndicadorLabel, tipoIndicadorColor } from 'src/utils/rhFormatters'

// Badge do tipo de indicador: U=Unidade, S=Setor, V=Vendedor, C=Caixa.
defineProps({
  tipo: { type: String, default: null },
})
</script>

<template>
  <q-badge v-if="tipo" :color="tipoIndicadorColor(tipo)" :label="tipoIndicadorLabel(tipo)" />
</template>
