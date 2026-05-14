<script setup>
import { ref, watch, onMounted, computed, useSlots } from 'vue'
import { api } from 'src/services/api'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
})
const emit = defineEmits(['update:modelValue'])

const opcoes = ref([])
const loading = ref(false)

async function buscar(busca = '') {
  loading.value = true
  try {
    const { data } = await api.get('v1/select/grupo-economico', {
      params: { busca: busca || ' ' },
    })
    opcoes.value = Array.isArray(data) ? data : data.data || []
  } finally {
    loading.value = false
  }
}

async function carregarPorId(codgrupoeconomico) {
  if (!codgrupoeconomico) return
  if (opcoes.value.find((p) => p.codgrupoeconomico === codgrupoeconomico)) return
  const { data } = await api.get('v1/select/grupo-economico', { params: { codgrupoeconomico } })
  const lista = Array.isArray(data) ? data : data.data || []
  if (lista.length) opcoes.value = [...opcoes.value, ...lista]
}

const filterFn = (val, update) => {
  update(async () => {
    await buscar(val)
  })
}

onMounted(() => {
  if (props.modelValue) carregarPorId(props.modelValue)
})

watch(
  () => props.modelValue,
  (v) => carregarPorId(v),
)

const slots = useSlots()
const slotsForwarded = computed(() => Object.keys(slots).filter((n) => n !== 'prepend'))
</script>

<template>
  <q-select
    :options="opcoes"
    :model-value="modelValue"
    @update:model-value="(v) => emit('update:modelValue', v)"
    use-input
    input-debounce="300"
    @filter="filterFn"
    option-value="codgrupoeconomico"
    option-label="grupoeconomico"
    emit-value
    map-options
    :loading="loading"
    class="q-select--truncado"
    input-class="ellipsis"
    popup-content-style="max-width: 95vw"
    v-bind="$attrs"
  >
    <template #selected-item="scope">
      <span class="ellipsis" style="max-width: 100%">{{ scope.opt.grupoeconomico }}</span>
    </template>
    <template #no-option>
      <q-item>
        <q-item-section class="text-grey-6">Digite para buscar</q-item-section>
      </q-item>
    </template>
    <template #prepend>
      <slot name="prepend"><q-icon name="groups" /></slot>
    </template>
    <template v-for="name in slotsForwarded" :key="name" #[name]="slotData">
      <slot :name="name" v-bind="slotData ?? {}" />
    </template>
  </q-select>
</template>
