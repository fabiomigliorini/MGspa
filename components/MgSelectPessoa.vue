<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from 'src/services/api'

// Seletor de pessoa com busca remota — componente compartilhado (@components).
// Usa o endpoint v1/select/pessoa. Cada app importa seu próprio `api` (baseURL
// = .../api/), então o prefixo v1 vai aqui na chamada, igual aos demais selects.
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
})
const emit = defineEmits(['update:modelValue'])

const opcoes = ref([])
const loading = ref(false)

async function buscar(busca = '') {
  loading.value = true
  try {
    const { data } = await api.get('v1/select/pessoa', {
      params: { pessoa: busca || ' ', somenteAtivos: false },
      skipLoading: true,
    })
    opcoes.value = Array.isArray(data) ? data : data.data || []
  } finally {
    loading.value = false
  }
}

async function carregarPorId(codpessoa) {
  if (!codpessoa) return
  if (opcoes.value.find((p) => p.codpessoa === codpessoa)) return
  const { data } = await api.get('v1/select/pessoa', { params: { codpessoa }, skipLoading: true })
  const lista = Array.isArray(data) ? data : data.data || []
  if (lista.length) opcoes.value = [...opcoes.value, ...lista]
}

function filterFn(val, update) {
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
</script>

<template>
  <q-select
    :options="opcoes"
    :model-value="modelValue"
    use-input
    input-debounce="300"
    option-value="codpessoa"
    option-label="fantasia"
    emit-value
    map-options
    outlined
    :loading="loading"
    @update:model-value="(v) => emit('update:modelValue', v)"
    @filter="filterFn"
    v-bind="$attrs"
  >
    <template #option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label>{{ scope.opt.fantasia || scope.opt.pessoa }}</q-item-label>
          <q-item-label caption>
            {{ scope.opt.pessoa }}<span v-if="scope.opt.cnpj"> · {{ scope.opt.cnpj }}</span>
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template #no-option>
      <q-item>
        <q-item-section class="text-grey-6">Digite para buscar</q-item-section>
      </q-item>
    </template>
    <template #prepend><q-icon name="person" /></template>
  </q-select>
</template>
