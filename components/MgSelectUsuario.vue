<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from 'src/services/api'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== Padrão REMOTE (ver MgSelectPessoa.vue) =====
// Busca no backend (?busca=, debounce) com PAGINAÇÃO 20/20 via scroll infinito;
// cacheia por id; resolve o valor atual por v1/select/usuario/{id}.
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Usuário' },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'clear'])

const cache = useSelectCacheStore()
const ENTITY = 'usuario'
const ENDPOINT = 'v1/select/usuario'
const PER_PAGE = 20

const options = ref([])
const loading = ref(false)

let buscaAtual = ''
let pagina = 1
let temMais = false

async function buscar(busca, page) {
  const { data } = await api.get(ENDPOINT, {
    params: { busca, page, inativos: props.inativos ? 1 : 0 },
  })
  const rows = Array.isArray(data) ? data : data?.data || []
  cache.mergeById(ENTITY, rows)
  return rows
}

async function carregarPorId(id) {
  if (!id) return
  if (options.value.find((o) => o.value === id)) return
  const cached = cache.getById(ENTITY, id)
  if (cached) {
    options.value = [cached]
    return
  }
  try {
    const { data } = await api.get(`${ENDPOINT}/${id}`)
    const row = Array.isArray(data) ? data[0] : data?.data || data
    if (row && row.value != null) {
      cache.mergeById(ENTITY, [row])
      options.value = [row]
    }
  } catch {
    // sem registro: deixa o select sem opção resolvida
  }
}

onMounted(() => {
  if (props.modelValue) carregarPorId(props.modelValue)
})

watch(
  () => props.modelValue,
  (newValue, oldValue) => {
    if (newValue && newValue !== oldValue && !options.value.find((o) => o.value === newValue)) {
      carregarPorId(newValue)
    }
  },
)

const filtrar = (val, update) => {
  if (!val || val.length < 2) {
    update(() => {
      options.value = []
    })
    return
  }
  update(async () => {
    try {
      loading.value = true
      buscaAtual = val
      pagina = 1
      const rows = await buscar(val, 1)
      temMais = rows.length === PER_PAGE
      options.value = rows
    } catch (error) {
      console.error('Erro ao buscar usuário:', error)
      options.value = []
    } finally {
      loading.value = false
    }
  })
}

const onScroll = async ({ to }) => {
  if (!temMais || loading.value || !buscaAtual) return
  if (to < options.value.length - 2) return
  try {
    loading.value = true
    pagina += 1
    const rows = await buscar(buscaAtual, pagina)
    temMais = rows.length === PER_PAGE
    options.value = [...options.value, ...rows]
  } catch {
    temMais = false
  } finally {
    loading.value = false
  }
}

const handleUpdate = (value) => {
  emit('update:modelValue', value)
  if (value === null) emit('clear')
}
</script>

<template>
  <q-select
    :model-value="modelValue"
    @update:model-value="handleUpdate"
    :label="label"
    outlined
    :clearable="clearable"
    :options="options"
    option-value="value"
    option-label="label"
    emit-value
    map-options
    use-input
    input-debounce="500"
    @filter="filtrar"
    @virtual-scroll="onScroll"
    :loading="loading"
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">
            {{ scope.opt.label }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-slot:selected-item="scope">
      <span :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">{{ scope.opt.label }}</span>
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          {{ options.length === 0 ? 'Digite ao menos 2 caracteres' : 'Nenhum resultado' }}
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
