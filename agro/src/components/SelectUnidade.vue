<script setup>
// Select de unidade armazenadora. Lê a store carga — funciona offline.
// Se só existe uma unidade cadastrada, já vem preenchida (sem sobrescrever
// uma seleção existente ao editar uma carga). `excluir` esconde silos (na
// transferência, o destino não oferece o silo da origem).
import { computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import MgSelect from '@components/MgSelect.vue'
import { useCargaStore } from 'src/stores/carga'

const props = defineProps({
  modelValue: { type: Number, default: null },
  label: { type: String, default: 'Unidade' },
  excluir: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

const { unidadesAtivas } = storeToRefs(useCargaStore())

const opcoes = computed(() =>
  unidadesAtivas.value.filter((u) => !props.excluir.includes(u.codunidadearmazenadora)),
)

function autoselecionar() {
  if (!props.modelValue && opcoes.value.length === 1) {
    emit('update:modelValue', opcoes.value[0].codunidadearmazenadora)
  }
}
onMounted(autoselecionar)
watch(opcoes, autoselecionar)
</script>

<template>
  <MgSelect
    :model-value="modelValue"
    :options="opcoes"
    :label="label"
    option-value="codunidadearmazenadora"
    option-label="unidadearmazenadora"
    emit-value
    map-options
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
