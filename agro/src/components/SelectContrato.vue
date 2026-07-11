<script setup>
// Select de contrato. Lê a store carga — funciona offline. O rótulo (contrato +
// pessoa) vem do helper da store. `operacao` só compõe o label (compra/venda).
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'

const props = defineProps({
  modelValue: { type: Number, default: null },
  operacao: { type: String, default: 'compra' },
  label: { type: String, default: null },
})
defineEmits(['update:modelValue'])

const store = useCargaStore()
const { contratosAtivos } = storeToRefs(store)

const lbl = computed(() => props.label || `Contrato (${props.operacao})`)
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="contratosAtivos"
    :label="lbl"
    option-value="codcontrato"
    :option-label="(c) => store.rotuloContrato(c.codcontrato)"
    emit-value
    map-options
    outlined
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
