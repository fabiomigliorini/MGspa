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
  await cache.loadList('formaPagamento', 'v1/forma-pagamento?todos=1', (data) => data.data || [])
  opcoes.value = cache.formaPagamento.items
})

const filterFn = (val, update) => {
  update(() => {
    if (!val) {
      opcoes.value = cache.formaPagamento.items
      return
    }
    const needle = val.toLowerCase()
    opcoes.value = cache.formaPagamento.items.filter((v) =>
      (v.formapagamento || '').toLowerCase().includes(needle),
    )
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
    option-value="codformapagamento"
    option-label="formapagamento"
    emit-value
    map-options
    v-bind="$attrs"
  />
</template>
