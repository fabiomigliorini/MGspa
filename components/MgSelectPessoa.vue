<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from 'src/services/api'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { formataCnpjCpf } from '@components/formatters'

// ===== REFERÊNCIA do padrão REMOTE (entidade grande) =====
// Busca no backend (?busca=, debounce) com PAGINAÇÃO 20/20 via scroll infinito
// no dropdown; cacheia resultados por id; resolve o valor atual por v1/select/pessoa/{id}.
// Self-contained: cada app importa seu próprio `api` (baseURL = .../api/).
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Pessoa' },
  placeholder: { type: String, default: null },
  bottomSlots: { type: Boolean, default: true },
  customClass: { type: String, default: '' },
  disable: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  maxChars: { type: Number, default: 25 },
  inativos: { type: Boolean, default: false },
  somenteVendedores: { type: Boolean, default: false },
  clearable: { type: Boolean, default: false },
  // Quando setado (>= 11 dígitos), busca automática e abre o popup.
  searchCnpj: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue', 'clear', 'select'])

const cache = useSelectCacheStore()
const ENTITY = 'pessoa'
const ENDPOINT = 'v1/select/pessoa'
const PER_PAGE = 20

const options = ref([])
const loading = ref(false)
const selectRef = ref(null)
const optionsFromCnpj = ref([])

// estado da paginação da busca corrente
let buscaAtual = ''
let pagina = 1
let temMais = false

function mapPessoa(item) {
  return {
    label: item.fantasia || item.pessoa,
    sublabel: item.pessoa !== item.fantasia ? item.pessoa : null,
    value: item.codpessoa,
    cnpj: item.cnpj,
    ie: item.ie,
    fisica: item.fisica,
    cidade: item.cidade,
    uf: item.sigla || item.uf,
    inativo: item.inativo,
    codgrupoeconomico: item.codgrupoeconomico,
    grupoeconomico: item.grupoeconomico,
  }
}

async function buscar(busca, page) {
  const { data } = await api.get(ENDPOINT, {
    params: {
      busca,
      page,
      inativos: props.inativos ? 1 : 0,
      somenteVendedores: props.somenteVendedores ? 1 : 0,
    },
  })
  const rows = (Array.isArray(data) ? data : data?.data || []).map(mapPessoa)
  cache.mergeById(ENTITY, rows)
  return rows
}

async function carregarPorId(codpessoa) {
  if (!codpessoa) return
  if (options.value.find((o) => o.value === codpessoa)) return
  const cached = cache.getById(ENTITY, codpessoa)
  if (cached) {
    options.value = [cached]
    return
  }
  try {
    const { data } = await api.get(`${ENDPOINT}/${codpessoa}`)
    const row = Array.isArray(data) ? data[0] : data?.data || data
    if (row && (row.codpessoa != null || row.value != null)) {
      const mapped = row.value != null ? row : mapPessoa(row)
      cache.mergeById(ENTITY, [mapped])
      options.value = [mapped]
    }
  } catch {
    // sem registro: deixa o select sem opção resolvida
  }
}

const truncateLabel = (label) => {
  if (!label) return ''
  return label.length > props.maxChars ? label.substring(0, props.maxChars) + '...' : label
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

watch(
  () => props.searchCnpj,
  async (cnpj) => {
    if (!cnpj || cnpj.length < 11) return
    try {
      loading.value = true
      buscaAtual = cnpj
      pagina = 1
      const results = await buscar(cnpj, 1)
      temMais = results.length === PER_PAGE
      optionsFromCnpj.value = results
      options.value = results
      if (results.length > 0 && selectRef.value) {
        await new Promise((resolve) => setTimeout(resolve, 100))
        selectRef.value.focus()
        selectRef.value.showPopup()
      }
    } catch (error) {
      console.error('Erro ao buscar pessoa por CNPJ:', error)
      optionsFromCnpj.value = []
      options.value = []
    } finally {
      loading.value = false
    }
  },
)

const filterPessoa = (val, update) => {
  if (optionsFromCnpj.value.length > 0 && (!val || val.trim().length < 2)) {
    update(() => {
      options.value = optionsFromCnpj.value
    })
    return
  }
  if (!val || val.length < 2) {
    update(() => {
      options.value = []
    })
    return
  }
  // Busca no backend FORA do update; só chama update() (síncrono) com o
  // resultado pronto — senão o Quasar fecha o ciclo de filtro com a lista vazia.
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
      console.error('Erro ao buscar pessoa:', error)
      update(() => {
        options.value = []
      })
    })
    .finally(() => {
      loading.value = false
    })
}

// Scroll infinito: ao chegar perto do fim, pede a próxima página e dá append.
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
    const pessoaSelecionada = options.value.find((o) => o.value === value)
    if (pessoaSelecionada) emit('select', pessoaSelecionada)
    optionsFromCnpj.value = []
  }
}
</script>

<template>
  <q-select
    ref="selectRef"
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
    @filter="filterPessoa"
    @virtual-scroll="onScroll"
    :placeholder="placeholder"
    :bottom-slots="bottomSlots"
    :class="customClass"
    :disable="disable"
    :readonly="readonly"
    :loading="loading"
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon
            :name="scope.opt.fisica ? 'person' : 'business'"
            :color="scope.opt.fisica ? 'blue' : 'purple'"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">
            {{ scope.opt.label }}
          </q-item-label>
          <q-item-label caption class="text-grey-7">
            {{ formataCnpjCpf(scope.opt.cnpj, scope.opt.fisica) }}
            <span v-if="scope.opt.ie"> | IE: {{ scope.opt.ie }} </span>
            <span v-if="scope.opt.cidade">
              | {{ scope.opt.cidade }}<span v-if="scope.opt.uf">/{{ scope.opt.uf }}</span>
            </span>
          </q-item-label>
          <q-item-label v-if="scope.opt.sublabel" caption class="text-grey-6">
            {{ scope.opt.sublabel }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-slot:selected-item="scope">
      <q-chip
        removable
        @remove="handleUpdate(null)"
        :color="scope.opt.fisica ? 'blue' : 'purple'"
        text-color="white"
        :icon="scope.opt.fisica ? 'person' : 'business'"
      >
        <span :class="scope.opt.inativo ? 'text-strike' : ''">
          {{ truncateLabel(scope.opt.label) }}
        </span>
      </q-chip>
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          {{ options.length === 0 ? 'Digite ao menos 2 caracteres' : 'Nenhum resultado' }}
        </q-item-section>
      </q-item>
    </template>

    <template v-if="$slots.prepend" v-slot:prepend>
      <slot name="prepend" />
    </template>

    <template v-if="$slots.append" v-slot:append>
      <slot name="append" />
    </template>
  </q-select>
</template>
