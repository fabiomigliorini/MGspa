<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSelectCacheStore } from 'src/stores/selectCacheStore'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  // Se null, sem restrição. Se array, mostra só portadores cujo codfilial está na lista.
  filiais: { type: Array, default: null },
})
const emit = defineEmits(['update:modelValue'])

const cache = useSelectCacheStore()
const opcoes = ref([])

const itensPermitidos = computed(() => {
  const all = cache.portador?.items || []
  if (!props.filiais) return all
  const set = new Set(props.filiais.map((f) => Number(f)))
  return all.filter((v) => set.has(Number(v.codfilial)))
})

onMounted(async () => {
  await cache.loadList('portador', 'v1/select/portador', (data) =>
    Array.isArray(data) ? data : data.data || [],
  )
  opcoes.value = itensPermitidos.value
})

const filterFn = (val, update) => {
  update(() => {
    if (!val) {
      opcoes.value = itensPermitidos.value
      return
    }
    const needle = val.toLowerCase()
    opcoes.value = itensPermitidos.value.filter((v) =>
      (v.portador || '').toLowerCase().includes(needle),
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
    option-value="codportador"
    option-label="portador"
    emit-value
    map-options
    class="q-select--truncado"
    input-class="ellipsis"
    v-bind="$attrs"
  >
    <template v-for="(_, name) in $slots" :key="name" #[name]="slotData">
      <slot :name="name" v-bind="slotData ?? {}" />
    </template>
  </q-select>
</template>
