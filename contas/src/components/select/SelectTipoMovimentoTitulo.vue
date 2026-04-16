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
  await cache.loadList(
    'tipoMovimentoTitulo',
    'v1/tipo-movimento-titulo?todos=1',
    (data) => data.data || [],
  )
  opcoes.value = cache.tipoMovimentoTitulo.items
})

const filterFn = (val, update) => {
  update(() => {
    if (!val) {
      opcoes.value = cache.tipoMovimentoTitulo.items
      return
    }
    const needle = val.toLowerCase()
    opcoes.value = cache.tipoMovimentoTitulo.items.filter((v) =>
      (v.tipomovimentotitulo || '').toLowerCase().includes(needle),
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
    option-value="codtipomovimentotitulo"
    option-label="tipomovimentotitulo"
    emit-value
    map-options
    v-bind="$attrs"
  >
    <template v-for="(_, name) in $slots" :key="name" #[name]="slotData">
      <slot :name="name" v-bind="slotData ?? {}" />
    </template>
  </q-select>
</template>
