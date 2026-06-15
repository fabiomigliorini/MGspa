<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from 'src/services/api'
import { formataCnpjCpf } from '@components/formatters'

// Seletor de pessoa padrão de todos os apps (busca remota em v1/select/pessoa).
// Self-contained: cada app importa seu próprio `api` (baseURL = .../api/), então
// o prefixo v1 vai aqui na chamada. Portado do notas; é a referência única —
// não criar SelectPessoa local por app. (negócios é offline/Dexie, exceção.)
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Pessoa' },
  placeholder: { type: String, default: null },
  bottomSlots: { type: Boolean, default: true },
  customClass: { type: String, default: '' },
  disable: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  maxChars: { type: Number, default: 25 },
  somenteAtivos: { type: Boolean, default: true },
  somenteVendedores: { type: Boolean, default: false },
  // Quando setado (>= 11 dígitos), busca automática e abre o popup.
  searchCnpj: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue', 'clear', 'select'])

const options = ref([])
const loading = ref(false)
const selectRef = ref(null)
const optionsFromCnpj = ref([]) // opções carregadas via CNPJ

// Formata cada pessoa da API no shape do select.
function mapPessoa(item) {
  return {
    label: item.fantasia || item.pessoa,
    sublabel: item.pessoa !== item.fantasia ? item.pessoa : null,
    value: item.codpessoa,
    cnpj: item.cnpj,
    ie: item.ie,
    fisica: item.fisica,
    cidade: item.cidade,
    uf: item.sigla,
    inativo: item.inativo,
    codgrupoeconomico: item.codgrupoeconomico,
    grupoeconomico: item.grupoeconomico,
  }
}

async function buscar(busca) {
  if (!busca || busca.length < 2) return []
  const { data } = await api.get('v1/select/pessoa', {
    params: {
      pessoa: busca,
      somenteAtivos: props.somenteAtivos ? 1 : 0,
      somenteVendedores: props.somenteVendedores ? 1 : 0,
    },
  })
  return (data || []).map(mapPessoa)
}

async function carregarPorId(codpessoa) {
  if (!codpessoa) return
  if (options.value.find((o) => o.value === codpessoa)) return
  const { data } = await api.get('v1/select/pessoa', { params: { codpessoa } })
  if (data && data.length) options.value = [mapPessoa(data[0])]
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

// Busca automática por CNPJ (ex.: vindo de um campo de NF) + abre o popup.
watch(
  () => props.searchCnpj,
  async (cnpj) => {
    if (!cnpj || cnpj.length < 11) return
    try {
      loading.value = true
      const results = await buscar(cnpj)
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
  // Com opções vindas de CNPJ e campo vazio, mantém essas opções.
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
  update(async () => {
    try {
      loading.value = true
      options.value = await buscar(val)
    } catch (error) {
      console.error('Erro ao buscar pessoa:', error)
      options.value = []
    } finally {
      loading.value = false
    }
  })
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
    clearable
    :options="options"
    option-value="value"
    option-label="label"
    emit-value
    map-options
    use-input
    input-debounce="500"
    @filter="filterPessoa"
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
          <q-item-label>{{ scope.opt.label }}</q-item-label>
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
        <q-item-section v-if="scope.opt.inativo" side>
          <q-badge color="negative" label="Inativo" />
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
        {{ truncateLabel(scope.opt.label) }}
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
