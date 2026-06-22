<script setup>
// ===== Select ESTÁTICO (4 opções fixas, sem backend) =====
// Tipo de cliente fiscal: PFC/PFN/PJC/PJN.
defineProps({
  modelValue: { type: String, default: null },
  label: { type: String, default: 'Tipo de Cliente' },
  clearable: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select'])

const opcoes = [
  { value: 'PFC', label: 'PFC', descricao: 'Pessoa Física Contribuinte', cor: 'blue' },
  { value: 'PFN', label: 'PFN', descricao: 'Pessoa Física Não Contribuinte', cor: 'cyan' },
  { value: 'PJC', label: 'PJC', descricao: 'Pessoa Jurídica Contribuinte', cor: 'green' },
  { value: 'PJN', label: 'PJN', descricao: 'Pessoa Jurídica Não Contribuinte', cor: 'teal' },
]

function onUpdate(v) {
  emit('update:modelValue', v)
  emit('select', opcoes.find((o) => o.value === v) || null)
}
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="opcoes"
    :label="label"
    outlined
    :clearable="clearable"
    emit-value
    map-options
    @update:model-value="onUpdate"
    v-bind="$attrs"
  >
    <template #option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon name="person" :color="scope.opt.cor" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.label }}</q-item-label>
          <q-item-label caption class="text-grey-6">{{ scope.opt.descricao }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template v-if="$slots.prepend" #prepend><slot name="prepend" /></template>
    <template v-if="$slots.before" #before><slot name="before" /></template>
    <template v-if="$slots.after" #after><slot name="after" /></template>
    <template v-if="$slots.hint" #hint><slot name="hint" /></template>
  </q-select>
</template>
