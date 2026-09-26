<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from 'src/services/api'

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Modelo' },
  clearable: { type: Boolean, default: false },
  bottomSlots: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'select', 'clear'])
const ENDPOINT = 'v1/vale-modelo'
const PER_PAGE = 40
const options = ref([])
const loading = ref(false)
let buscaAtual = ''
let pagina = 1
let ultimaPagina = 1
let solicitacao = 0

function mapModelo(modelo) {
  return {
    value: modelo.codvalemodelo,
    label: modelo.modelo,
    favorecido: modelo.favorecido,
    inativo: Boolean(modelo.inativo),
  }
}

async function buscar(busca, page, id) {
  const { data } = await api.get(ENDPOINT, {
    params: { modelo: busca, per_page: PER_PAGE, page },
  })
  if (id !== solicitacao) return []
  ultimaPagina = data.meta?.last_page || 1
  return (data.data || []).map(mapModelo)
}

async function carregarPorId(id) {
  if (!id || options.value.some((option) => option.value == id)) return
  try {
    const { data } = await api.get(`${ENDPOINT}/${id}`)
    const modelo = data.data || data
    const option = mapModelo(modelo)
    options.value = [option, ...options.value]
  } catch {
    // Se o modelo não existir mais, o select fica sem opção resolvida.
  }
}

async function carregarIniciais() {
  const id = ++solicitacao
  buscaAtual = ''
  pagina = 1
  loading.value = true
  try {
    options.value = await buscar('', pagina, id)
    if (props.modelValue && !options.value.some((option) => option.value == props.modelValue)) {
      await carregarPorId(props.modelValue)
    }
  } catch {
    if (id === solicitacao) options.value = []
  } finally {
    if (id === solicitacao) loading.value = false
  }
}

onMounted(carregarIniciais)
watch(() => props.modelValue, carregarPorId)

function filtrar(valor, update) {
  const busca = String(valor || '').trim()
  if (busca.length < 2) {
    if (!busca) {
      if (buscaAtual === '' && options.value.length) {
        update(() => {})
        return
      }
      buscaAtual = ''
      pagina = 1
      const id = ++solicitacao
      loading.value = true
      buscar('', pagina, id)
        .then((modelos) => {
          if (id === solicitacao) update(() => (options.value = modelos))
        })
        .catch(() => {
          if (id === solicitacao) update(() => (options.value = []))
        })
        .finally(() => {
          if (id === solicitacao) loading.value = false
        })
      return
    }
    solicitacao++
    update(() => {
      options.value = []
      loading.value = false
    })
    return
  }

  buscaAtual = busca
  pagina = 1
  const id = ++solicitacao
  loading.value = true
  buscar(busca, pagina, id)
    .then((modelos) => {
      if (id === solicitacao) update(() => (options.value = modelos))
    })
    .catch(() => {
      if (id === solicitacao) update(() => (options.value = []))
    })
    .finally(() => {
      if (id === solicitacao) loading.value = false
    })
}

async function carregarMais({ to }) {
  if (loading.value || pagina >= ultimaPagina || to < options.value.length - 2) return
  const id = solicitacao
  loading.value = true
  try {
    const proximos = await buscar(buscaAtual, pagina + 1, id)
    if (id === solicitacao) {
      pagina++
      options.value = options.value.concat(proximos)
    }
  } catch {
    ultimaPagina = pagina
  } finally {
    if (id === solicitacao) loading.value = false
  }
}

function atualizar(value) {
  emit('update:modelValue', value)
  emit('select', options.value.find((option) => option.value === value) || null)
  if (value === null) emit('clear')
}
</script>

<template>
  <q-select
    :model-value="modelValue"
    @update:model-value="atualizar"
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
    :loading="loading"
    :bottom-slots="bottomSlots"
    @filter="filtrar"
    @virtual-scroll="carregarMais"
  >
    <template #option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">
            {{ scope.opt.label }}
          </q-item-label>
          <q-item-label v-if="scope.opt.favorecido" caption>
            {{ scope.opt.favorecido }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template #no-option>
      <q-item>
        <q-item-section class="text-grey">
          {{ options.length === 0 ? 'Digite ao menos 2 caracteres' : 'Nenhum resultado' }}
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
