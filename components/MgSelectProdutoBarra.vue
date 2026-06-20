<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from 'src/services/api'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== Padrão REMOTE (ver MgSelectPessoa.vue) =====
// Busca no backend (?busca=, debounce) com PAGINAÇÃO 20/20 via scroll infinito;
// cacheia por id; resolve o valor atual por v1/select/produto-barra/{id}.
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Produto' },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'clear', 'select'])

const cache = useSelectCacheStore()
const ENTITY = 'produtoBarra'
const ENDPOINT = 'v1/select/produto-barra'
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
  buscaAtual = val
  pagina = 1
  loading.value = true
  buscar(val, 1)
    .then((rows) => {
      temMais = rows.length === PER_PAGE
      update(() => {
        options.value = rows
      })
    })
    .catch((error) => {
      console.error('Erro ao buscar produto:', error)
      update(() => {
        options.value = []
      })
    })
    .finally(() => {
      loading.value = false
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
  if (value === null) {
    emit('clear')
  } else {
    const selecionado = options.value.find((o) => o.value === value)
    if (selecionado) emit('select', selecionado)
  }
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
    fill-input
    hide-selected
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
          <q-item-label v-if="scope.opt.barras || scope.opt.referencia" caption class="text-grey-7">
            <span v-if="scope.opt.barras">{{ scope.opt.barras }}</span>
            <span v-if="scope.opt.referencia">
              <span v-if="scope.opt.barras"> | </span>{{ scope.opt.referencia }}
            </span>
          </q-item-label>
        </q-item-section>
      </q-item>
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
