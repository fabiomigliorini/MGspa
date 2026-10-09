<script setup>
import { computed } from 'vue'
import { MOTIVOS, MOTIVOS_ITEM, MOTIVOS_PAGAMENTO } from '@components/ocorrencia.js'

// Lista fixa (funciona offline no PDV). Sem valor pre-selecionado: o caixa
// escolhe o motivo de proposito (TASK-205).
const props = defineProps({
  modelValue: { type: Number, default: null },
  label: { type: String, default: 'Motivo' },
  contexto: { type: String, default: 'item' }, // item | pagamento
})
const emit = defineEmits(['update:modelValue'])

const opcoes = computed(() =>
  (props.contexto == 'pagamento' ? MOTIVOS_PAGAMENTO : MOTIVOS_ITEM).map((value) => ({
    value,
    label: MOTIVOS[value],
  })),
)
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="opcoes"
    :label="label"
    outlined
    emit-value
    map-options
    @update:model-value="(v) => emit('update:modelValue', v)"
    v-bind="$attrs"
  >
    <template v-if="$slots.prepend" #prepend><slot name="prepend" /></template>
  </q-select>
</template>
