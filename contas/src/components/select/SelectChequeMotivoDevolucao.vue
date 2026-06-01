<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'src/services/api'

defineProps({
  modelValue: { type: [Number, String], default: null },
})
const emit = defineEmits(['update:modelValue'])

const todas = ref([])
const opcoes = ref([])

async function carregar() {
  try {
    const { data } = await api.get('v1/cheque-motivo-devolucao/autocompletar')
    todas.value = Array.isArray(data) ? data : data.data || []
    opcoes.value = todas.value
  } catch {
    todas.value = []
    opcoes.value = []
  }
}

const filtrar = (texto, update) => {
  update(() => {
    const t = (texto || '').toLowerCase().trim()
    opcoes.value = t ? todas.value.filter((o) => o.label.toLowerCase().includes(t)) : todas.value
  })
}

onMounted(carregar)
</script>

<template>
  <q-select
    :options="opcoes"
    :model-value="modelValue"
    @update:model-value="(v) => emit('update:modelValue', v)"
    use-input
    @filter="filtrar"
    option-value="value"
    option-label="label"
    emit-value
    map-options
    v-bind="$attrs"
  >
    <template v-for="(_, name) in $slots" :key="name" #[name]="slotData">
      <slot :name="name" v-bind="slotData ?? {}" />
    </template>
  </q-select>
</template>
