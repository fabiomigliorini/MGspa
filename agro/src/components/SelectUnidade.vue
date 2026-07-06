<script setup>
// Select de unidade armazenadora. Lê a store carga — funciona offline.
// Se só existe uma unidade cadastrada, já vem preenchida (sem sobrescrever
// uma seleção existente ao editar uma carga).
import { onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'

const props = defineProps({
  modelValue: { type: Number, default: null },
  label: { type: String, default: 'Unidade' },
})
const emit = defineEmits(['update:modelValue'])

const { unidadesAtivas } = storeToRefs(useCargaStore())

function autoselecionar() {
  if (!props.modelValue && unidadesAtivas.value.length === 1) {
    emit('update:modelValue', unidadesAtivas.value[0].codunidadearmazenadora)
  }
}
onMounted(autoselecionar)
watch(unidadesAtivas, autoselecionar)
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="unidadesAtivas"
    :label="label"
    option-value="codunidadearmazenadora"
    option-label="unidadearmazenadora"
    emit-value
    map-options
    outlined
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
