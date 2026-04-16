<script setup>
import { ref, onMounted } from 'vue'
import { useSelectCacheStore } from 'src/stores/selectCacheStore'

defineProps({
  modelValue: { type: [Number, String], default: null },
})
const emit = defineEmits(['update:modelValue'])

const cache = useSelectCacheStore()
const opcoes = ref([])

onMounted(async () => {
  await cache.loadList('filial', 'v1/select/filial', (data) =>
    Array.isArray(data) ? data : data.data || [],
  )
  opcoes.value = cache.filial.items
})

const filterFn = (val, update) => {
  update(() => {
    if (!val) {
      opcoes.value = cache.filial.items
      return
    }
    const needle = val.toLowerCase()
    opcoes.value = cache.filial.items.filter((v) => (v.label || '').toLowerCase().includes(needle))
  })
}
</script>

<template>
  <q-select
    :options="opcoes"
    :model-value="modelValue"
    @update:model-value="(v) => emit('update:modelValue', v)"
    use-input
    input-debounce="100"
    @filter="filterFn"
    option-value="value"
    option-label="label"
    emit-value
    map-options
    v-bind="$attrs"
  />
</template>
