<script setup>
// Tipo de romaneio (Recebimento / Expedição / Transferência) — select do modal
// de Operação. Ícone/cor vêm de SENTIDOS (utils/carga), o mesmo par do card.
// Sem ícone no campo: num modal de 560px com 3 campos por linha ele cortaria
// "Recebimento" com reticências.
import { SENTIDOS } from 'src/utils/carga'

defineProps({
  modelValue: { type: String, default: null },
  label: { type: String, default: 'Operação' },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="SENTIDOS"
    :label="label"
    option-value="value"
    option-label="label"
    emit-value
    map-options
    outlined
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #option="{ opt, itemProps }">
      <q-item v-bind="itemProps">
        <q-item-section avatar>
          <q-icon :name="opt.icon" :color="opt.color" />
        </q-item-section>
        <q-item-section>{{ opt.label }}</q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
