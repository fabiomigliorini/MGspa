<script setup>
import { ref, onMounted, watch } from 'vue'
import { LoadingBar } from 'quasar'
import { api } from 'src/services/api'
import { useSelectCacheStore } from 'src/stores/selectCacheStore'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
})
const emit = defineEmits(['update:modelValue'])

const cache = useSelectCacheStore()
const opcoes = ref([])

const resolverInicial = async (id) => {
  if (!id) {
    opcoes.value = []
    return
  }
  try {
    const rec = await cache.loadById('banco', 'v1/select/banco', 'codbanco', id)
    opcoes.value = rec ? [rec] : []
  } catch {
    opcoes.value = []
  }
}

watch(() => props.modelValue, resolverInicial)
onMounted(() => resolverInicial(props.modelValue))

const pesquisa = (texto, update) => {
  update(async () => {
    const t = (texto || '').trim()
    if (t.length < 2) return
    LoadingBar.start()
    try {
      const ret = await api.get('v1/select/banco', { params: { busca: t } })
      const rows = Array.isArray(ret.data) ? ret.data : ret.data.data || []
      opcoes.value = rows
      cache.mergeById('banco', rows, 'codbanco')
    } catch {
      opcoes.value = []
    }
    LoadingBar.stop()
  })
}
</script>

<template>
  <q-select
    :options="opcoes"
    :model-value="modelValue"
    @update:model-value="(v) => emit('update:modelValue', v)"
    use-input
    @filter="pesquisa"
    option-value="codbanco"
    option-label="banco"
    emit-value
    map-options
    v-bind="$attrs"
  >
    <template v-for="(_, name) in $slots" :key="name" #[name]="slotData">
      <slot :name="name" v-bind="slotData ?? {}" />
    </template>
  </q-select>
</template>
