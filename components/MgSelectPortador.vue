<script setup>
import { ref, onMounted, computed } from 'vue'
import { api } from 'src/services/api'

// Seletor de portador padrão de todos os apps (lista de v1/select/portador,
// que retorna o conjunto todo). Self-contained com busca ao digitar (filtro no
// cliente, já que a lista é pequena). Espelha o SelectPortador do contas, mas
// sem depender do cache store — cada app importa seu próprio `api`.
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Portador' },
  // Se array de codfilial, restringe aos portadores dessas filiais.
  filiais: { type: Array, default: null },
})
const emit = defineEmits(['update:modelValue'])

const todos = ref([])
const opcoes = ref([])

const permitidos = computed(() => {
  if (!props.filiais) return todos.value
  const set = new Set(props.filiais.map((f) => Number(f)))
  return todos.value.filter((v) => set.has(Number(v.codfilial)))
})

onMounted(async () => {
  try {
    const { data } = await api.get('v1/select/portador')
    todos.value = (Array.isArray(data) ? data : data.data || []).map((p) => ({
      value: p.codportador,
      label: p.portador,
      codfilial: p.codfilial,
    }))
    opcoes.value = permitidos.value
  } catch {
    todos.value = []
    opcoes.value = []
  }
})

function filtrar(val, update) {
  update(() => {
    const needle = (val || '').toLowerCase()
    opcoes.value = needle
      ? permitidos.value.filter((v) => (v.label || '').toLowerCase().includes(needle))
      : permitidos.value
  })
}
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="opcoes"
    :label="label"
    use-input
    input-debounce="100"
    outlined
    clearable
    emit-value
    map-options
    @filter="filtrar"
    @update:model-value="(v) => emit('update:modelValue', v)"
    v-bind="$attrs"
  >
    <template #no-option>
      <q-item><q-item-section class="text-grey-6">Nenhum portador</q-item-section></q-item>
    </template>
  </q-select>
</template>
