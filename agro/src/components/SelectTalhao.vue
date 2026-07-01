<script setup>
// Select de talhão (plantio da safra ativa). Lê a store carga — funciona offline.
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'

defineProps({
  modelValue: { type: Number, default: null },
  label: { type: String, default: 'Talhão' },
})
defineEmits(['update:modelValue'])

const { plantiosDaSafra } = storeToRefs(useCargaStore())
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="plantiosDaSafra"
    :label="label"
    option-value="codplantio"
    option-label="rotulo"
    emit-value
    map-options
    outlined
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
