<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSelectCacheStore } from 'src/stores/selectCacheStore'

const props = defineProps({
  // null = todos selecionados (padrão) | [] = nenhum | [ids] = seleção parcial
  modelValue: { type: Array, default: null },
})
const emit = defineEmits(['update:modelValue'])

const cache = useSelectCacheStore()
const opcoes = ref([])

onMounted(async () => {
  await cache.loadList('grupoCliente', 'v1/grupo-cliente?todos=1', (data) => data.data || [])
  opcoes.value = cache.grupoCliente.items
})

const todosIds = computed(() =>
  (cache.grupoCliente.items || []).map((v) => v.codgrupocliente),
)

// null = todos -> manda a lista inteira pro q-select aparecer tudo marcado
const valorQSelect = computed(() =>
  props.modelValue === null ? todosIds.value : props.modelValue || [],
)

const rotulo = computed(() => {
  if (Array.isArray(props.modelValue) && props.modelValue.length === 0) return 'Nenhum grupo'
  if (props.modelValue === null) return 'Todos os grupos'
  return `${props.modelValue.length} de ${todosIds.value.length} grupos`
})

function onUpdate(v) {
  const arr = Array.isArray(v) ? v : []
  // seleção completa volta a ser representada como null (canônico)
  if (arr.length > 0 && arr.length === todosIds.value.length) {
    emit('update:modelValue', null)
  } else {
    emit('update:modelValue', arr)
  }
}

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
    :model-value="valorQSelect"
    @update:model-value="onUpdate"
    multiple
    :display-value="rotulo"
    use-input
    input-debounce="100"
    @filter="filterFn"
    option-value="codgrupocliente"
    option-label="grupocliente"
    emit-value
    map-options
    class="q-select--truncado"
    input-class="ellipsis"
    v-bind="$attrs"
  >
    <template #option="scope">
      <q-item v-bind="scope.itemProps" :class="scope.selected ? 'bg-blue-2' : ''">
        <q-item-section>
          <q-item-label>{{ scope.opt.grupocliente }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-for="(_, name) in $slots" :key="name" #[name]="slotData">
      <slot :name="name" v-bind="slotData ?? {}" />
    </template>
  </q-select>
</template>
