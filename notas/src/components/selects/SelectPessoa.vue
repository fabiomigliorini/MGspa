<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSelectPessoaStore } from 'stores/selects/pessoa'
import { formatCnpjCpf } from 'src/utils/formatters'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'Pessoa',
  },
  placeholder: {
    type: String,
    default: null,
  },
  bottomSlots: {
    type: Boolean,
    default: true,
  },
  customClass: {
    type: String,
    default: '',
  },
  disable: {
    type: Boolean,
    default: false,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  dense: {
    type: Boolean,
    default: false,
  },
  maxChars: {
    type: Number,
    default: 25,
  },
  somenteAtivos: {
    type: Boolean,
    default: true,
  },
  somenteVendedores: {
    type: Boolean,
    default: false,
  },
  searchCnpj: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'clear', 'select'])

const pessoaStore = useSelectPessoaStore()
const options = ref([])
const loading = ref(false)
const selectRef = ref(null)
const optionsFromCnpj = ref([]) // Armazena opções carregadas via CNPJ

// Função para truncar o label
const truncateLabel = (label) => {
  if (!label) return ''
  return label.length > props.maxChars ? label.substring(0, props.maxChars) + '...' : label
}

// Carrega a pessoa quando há um modelValue inicial
onMounted(async () => {
  if (props.modelValue) {
    await loadPessoa(props.modelValue)
  }
})

// Recarrega quando o modelValue muda (para caso seja setado externamente)
watch(
  () => props.modelValue,
  async (newValue, oldValue) => {
    if (newValue && newValue !== oldValue) {
      // Se mudou o valor e não está nas options, carrega
      const exists = options.value.find((o) => o.value === newValue)
      if (!exists) {
        await loadPessoa(newValue)
      }
    }
  },
)

// Watch para quando searchCnpj mudar, fazer busca automática
watch(
  () => props.searchCnpj,
  async (cnpj) => {
    if (cnpj && cnpj.length >= 11) {
      try {
        loading.value = true
        const results = await pessoaStore.search(cnpj, {
          somenteAtivos: props.somenteAtivos,
          somenteVendedores: props.somenteVendedores,
        })

        // Armazena os resultados
        optionsFromCnpj.value = results
        options.value = results

        // Abre o select automaticamente se houver resultados
        if (results.length > 0 && selectRef.value) {
          // Aguarda um pouco para garantir que o DOM foi atualizado
          await new Promise(resolve => setTimeout(resolve, 100))

          // Foca no campo e abre o popup
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
    }
  },
)

const loadPessoa = async (codpessoa) => {
  try {
    loading.value = true
    const pessoa = await pessoaStore.fetch(codpessoa)
    if (pessoa) {
      // Adiciona a pessoa nas options se não existir
      const exists = options.value.find((o) => o.value === pessoa.value)
      if (!exists) {
        options.value = [pessoa]
      }
    }
  } catch (error) {
    console.error('Erro ao carregar pessoa:', error)
  } finally {
    loading.value = false
  }
}

const filterPessoa = async (val, update) => {
  // Se há opções do CNPJ e o campo está vazio ou com espaço, mostra essas opções
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
      options.value = await pessoaStore.search(val, {
        somenteAtivos: props.somenteAtivos,
        somenteVendedores: props.somenteVendedores,
      })
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
    // Encontra a pessoa selecionada nas options
    const pessoaSelecionada = options.value.find((o) => o.value === value)
    if (pessoaSelecionada) {
      emit('select', pessoaSelecionada)
    }
    // Limpa as opções do CNPJ após a seleção
    optionsFromCnpj.value = []
  }
}


</script>

<template>
  <q-select ref="selectRef" :model-value="modelValue" @update:model-value="handleUpdate" :label="label" outlined clearable
    :options="options" option-value="value" option-label="label" emit-value map-options use-input input-debounce="500"
    @filter="filterPessoa" :placeholder="placeholder" :bottom-slots="bottomSlots" :class="customClass"
    :disable="disable" :readonly="readonly" :loading="loading" :dense="dense">
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon :name="scope.opt.fisica ? 'person' : 'business'" :color="scope.opt.fisica ? 'blue' : 'purple'" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.label }}</q-item-label>
          <q-item-label caption class="text-grey-7">
            {{ formatCnpjCpf(scope.opt.cnpj, scope.opt.fisica) }}
            <span v-if="scope.opt.ie">
              | IE: {{ scope.opt.ie }}
            </span>
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
      <q-chip removable dense @remove="handleUpdate(null)" :color="scope.opt.fisica ? 'blue' : 'purple'"
        text-color="white" :icon="scope.opt.fisica ? 'person' : 'business'">
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
