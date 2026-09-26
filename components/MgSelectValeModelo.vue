<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from 'src/services/api'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// ===== Padrão REMOTE (ver MgSelectPessoa.vue), com duas diferenças =====
// - abre já listando os primeiros 40 modelos, sem precisar digitar;
// - `codpessoa` restringe aos modelos daquele favorecido. Trocar o
//   favorecido limpa o modelo escolhido quando ele é de outra escola.
// - lista só os ativos; "Mostrar inativos", no fim da lista, traz os
//   inativos também, só até fechar a lista (a prop `inativos` já abre mostrando).
// Paginação 40/40 via scroll infinito; cacheia por id; resolve o valor atual
// por v1/select/vale-modelo/{id}. A busca procura na descrição e na escola.
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Modelo de Vale' },
  clearable: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
  codpessoa: { type: [Number, String], default: null },
})

const emit = defineEmits(['update:modelValue', 'select', 'clear'])

const cache = useSelectCacheStore()
const ENTITY = 'valeModelo'
const ENDPOINT = 'v1/select/vale-modelo'
const PER_PAGE = 40

const options = ref([])
const loading = ref(false)
const mostrarInativos = ref(props.inativos)

let buscaAtual = null
let pagina = 1
let temMais = false

async function buscar(busca, page) {
  const { data } = await api.get(ENDPOINT, {
    params: {
      busca,
      page,
      inativos: mostrarInativos.value ? 1 : 0,
      codpessoa: props.codpessoa || null,
    },
  })
  const rows = Array.isArray(data) ? data : data?.data || []
  cache.mergeById(ENTITY, rows)
  return rows
}

async function carregarPorId(id) {
  if (!id) return
  if (options.value.find((o) => o.value == id)) return
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
    if (newValue && newValue !== oldValue && !options.value.find((o) => o.value == newValue)) {
      carregarPorId(newValue)
    }
  },
)

// Favorecido trocou: a lista passa a ser a da nova escola. O modelo escolhido
// fica se for dela (ex.: o atalho que abre com os dois preenchidos) e sai se
// for de outra.
watch(
  () => props.codpessoa,
  async (codpessoa) => {
    buscaAtual = null
    if (!props.modelValue) {
      options.value = []
      return
    }
    await carregarPorId(props.modelValue)
    const atual = options.value.find((o) => o.value == props.modelValue)
    if (
      codpessoa &&
      atual?.codpessoafavorecido !== undefined &&
      atual.codpessoafavorecido != codpessoa
    ) {
      options.value = []
      handleUpdate(null)
    }
  },
)

// Sem valor escolhido, o QSelect chama o @filter('') de novo a cada vez que as
// opcoes mudam (QSelect.js, watch do innerValue). Buscar de novo aqui trocaria
// as opcoes, que disparariam outro filtro: a pagina 2 do scroll recarregava a
// 1, que pedia a 2... Mesmo texto ja carregado = so reabre a lista.
const filtrar = (val, update) => {
  if ((val || '') === buscaAtual) {
    update(() => {})
    return
  }
  buscaAtual = val || ''
  pagina = 1
  loading.value = true
  buscar(buscaAtual, 1)
    .then((rows) => {
      temMais = rows.length === PER_PAGE
      update(() => {
        options.value = rows
      })
    })
    .catch((error) => {
      console.error('Erro ao buscar modelo de vale:', error)
      buscaAtual = null
      update(() => {
        options.value = []
      })
    })
    .finally(() => {
      loading.value = false
    })
}

// Refaz a busca atual ja trazendo os inativos. buscaAtual continua o mesmo
// texto, entao o @filter que o QSelect dispara com a troca das opcoes nao
// busca de novo.
const incluirInativos = async () => {
  mostrarInativos.value = true
  pagina = 1
  loading.value = true
  try {
    const rows = await buscar(buscaAtual || '', 1)
    temMais = rows.length === PER_PAGE
    options.value = rows
  } finally {
    loading.value = false
  }
}

// Fechou a lista: "Mostrar inativos" volta ao original, e a proxima abertura
// busca de novo.
const aoFechar = () => {
  if (mostrarInativos.value === props.inativos) return
  mostrarInativos.value = props.inativos
  buscaAtual = null
}

const onScroll = async ({ to }) => {
  if (!temMais || loading.value || buscaAtual === null) return
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
  emit('select', (options.value || []).find((o) => o.value === value) || null)
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
    fill-input
    hide-selected
    input-debounce="500"
    @filter="filtrar"
    @virtual-scroll="onScroll"
    @popup-hide="aoFechar"
    :loading="loading"
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">
            {{ scope.opt.label }}
          </q-item-label>
          <q-item-label caption>{{ scope.opt.favorecido || 'Ao portador' }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-if="!mostrarInativos" v-slot:after-options>
      <q-item clickable @click="incluirInativos">
        <q-item-section class="text-primary">Mostrar inativos</q-item-section>
      </q-item>
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">Nenhum modelo encontrado</q-item-section>
      </q-item>
      <q-item v-if="!mostrarInativos" clickable @click="incluirInativos">
        <q-item-section class="text-primary">Mostrar inativos</q-item-section>
      </q-item>
    </template>
    <template v-if="$slots.prepend" #prepend><slot name="prepend" /></template>
    <template v-if="$slots.hint" #hint><slot name="hint" /></template>
  </q-select>
</template>
