<script setup>
// Tipo de romaneio (Recebimento / Expedição / Transferência) como toggle — o
// operador decide na hora de digitar, não num filtro de tela.
import { SENTIDOS } from 'src/utils/carga'

defineProps({
  modelValue: { type: String, default: null },
  disable: { type: Boolean, default: false },
})
defineEmits(['update:modelValue'])

const opcoes = SENTIDOS.map((s) => ({ label: s.label, value: s.value, icon: s.icon }))
</script>

<template>
  <q-btn-toggle
    :model-value="modelValue"
    :options="opcoes"
    :disable="disable"
    spread
    no-caps
    unelevated
    color="grey-3"
    text-color="grey-9"
    toggle-color="primary"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
