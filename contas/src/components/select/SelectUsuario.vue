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
    const { data } = await api.get('v1/select/usuario', {
      params: { busca: busca || ' ', somenteAtivos: false },
    })
    opcoes.value = Array.isArray(data) ? data : data.data || []
  } finally {
    loading.value = false
  }
}

async function carregarPorId(codusuario) {
  if (!codusuario) return
  if (opcoes.value.find((u) => u.codusuario === codusuario)) return
  const { data } = await api.get('v1/select/usuario', { params: { codusuario } })
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
    option-value="codusuario"
    option-label="usuario"
    emit-value
    map-options
    :loading="loading"
    class="q-select--truncado"
    input-class="ellipsis"
    v-bind="$attrs"
  >
    <template #option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label>{{ scope.opt.usuario }}</q-item-label>
          <q-item-label v-if="scope.opt.fantasia" caption>
            {{ scope.opt.fantasia }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template #no-option>
      <q-item>
        <q-item-section class="text-grey-6">Digite para buscar</q-item-section>
      </q-item>
    </template>
    <template #prepend>
      <slot name="prepend"><q-icon name="person" /></slot>
    </template>
    <template v-for="name in slotsForwarded" :key="name" #[name]="slotData">
      <slot :name="name" v-bind="slotData ?? {}" />
    </template>
  </q-select>
</template>
