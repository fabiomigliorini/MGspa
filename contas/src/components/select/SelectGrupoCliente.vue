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
  await cache.loadList('grupoCliente', 'v1/grupo-cliente?todos=1', (data) => data.data || [])
  opcoes.value = cache.grupoCliente.items
})

const filterFn = (val, update) => {
  update(() => {
    if (!val) {
      opcoes.value = cache.grupoCliente.items
      return
    }
    const needle = val.toLowerCase()
    opcoes.value = cache.grupoCliente.items.filter((v) =>
      (v.grupocliente || '').toLowerCase().includes(needle),
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
    option-value="codgrupocliente"
    option-label="grupocliente"
    emit-value
    map-options
    v-bind="$attrs"
  >
    <template v-for="(_, name) in $slots" :key="name" #[name]="slotData">
      <slot :name="name" v-bind="slotData ?? {}" />
    </template>
  </q-select>
</template>
