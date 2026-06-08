<script setup>
import { ref, watch } from 'vue'
import { api } from 'src/services/api'

// Select com busca remota (autocompletar). Os endpoints do backend retornam
// [{ label, value, id, ... }]. O termo digitado vai no parâmetro `searchParam`
// (varia por entidade: 'marca', 'unidademedida', 'busca'...).
const props = defineProps({
  modelValue: { type: [Number, String, null], default: null },
  endpoint: { type: String, required: true },
  label: { type: String, default: '' },
  searchParam: { type: String, default: 'busca' },
  extraParams: { type: Object, default: () => ({}) },
  initialOption: { type: Object, default: null }, // { label, value } pra exibir valor atual
  clearable: { type: Boolean, default: true },
  rules: { type: Array, default: () => [] },
  autofocus: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'selected'])

const options = ref([])
if (props.initialOption && props.initialOption.value != null) {
  options.value = [props.initialOption]
}

// Mantém a opção atual visível mesmo após trocar extraParams
watch(
  () => props.initialOption,
  (op) => {
    if (op && op.value != null && !options.value.find((o) => o.value === op.value)) {
      options.value = [op, ...options.value]
    }
  },
)

const onFilter = async (val, update, abort) => {
  try {
    const params = { ...props.extraParams }
    params[props.searchParam] = val
    const { data } = await api.get(props.endpoint, { params })
    update(() => {
      options.value = data
    })
  } catch {
    abort()
  }
}

const onUpdate = (value) => {
  emit('update:modelValue', value)
  const op = options.value.find((o) => o.value === value)
  emit('selected', op || null)
}
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="options"
    :label="label"
    outlined
    clearable
    use-input
    emit-value
    map-options
    input-debounce="350"
    :bottom-slots="false"
    :rules="rules"
    :autofocus="autofocus"
    @filter="onFilter"
    @update:model-value="onUpdate"
  >
    <template #no-option>
      <q-item>
        <q-item-section class="text-grey-6">Digite para buscar…</q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
