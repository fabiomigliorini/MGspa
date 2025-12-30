<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSelectCidadeStore } from 'stores/selects/cidade'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'Cidade',
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
    default: 12,
  },
})

const emit = defineEmits(['update:modelValue', 'clear'])

const cidadeStore = useSelectCidadeStore()
const options = ref([])
const loading = ref(false)

// Função para truncar o label mantendo a UF visível
const truncateLabel = (label) => {
  if (!label) return ''

  // Verifica se tem "/" para separar cidade de UF
  const parts = label.split('/')
  if (parts.length === 2) {
    const cidade = parts[0].trim()
    const uf = parts[1].trim()

    // Se a cidade for muito longa, abrevia
    if (cidade.length > props.maxChars) {
      return cidade.substring(0, props.maxChars) + '.../' + uf
    }
    return label
  }

  // Se não tem /, abrevia normalmente
  const maxTotal = props.maxChars + 3 // +3 para dar um pouco mais de espaço
  return label.length > maxTotal ? label.substring(0, maxTotal) + '...' : label
}

// Carrega a cidade quando há um modelValue inicial
onMounted(async () => {
  if (props.modelValue) {
    await loadCidade(props.modelValue)
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
        await loadCidade(newValue)
      }
    }
  },
)

const loadCidade = async (codcidade) => {
  try {
    loading.value = true
    const cidade = await cidadeStore.fetch(codcidade)
    if (cidade) {
      // Adiciona a cidade nas options se não existir
      const exists = options.value.find((o) => o.value === cidade.value)
      if (!exists) {
        options.value = [cidade]
      }
    }
  } catch (error) {
    console.error('Erro ao carregar cidade:', error)
  } finally {
    loading.value = false
  }
}

const filterCidades = async (val, update) => {
  if (!val || val.length < 2) {
    update(() => {
      options.value = []
    })
    return
  }

  update(async () => {
    try {
      loading.value = true
      options.value = await cidadeStore.search(val)
    } catch (error) {
      console.error('Erro ao buscar cidades:', error)
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
  }
}
</script>

<template>
  <q-select
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
    @filter="filterCidades"
    :placeholder="placeholder"
    :bottom-slots="bottomSlots"
    :class="customClass"
    :disable="disable"
    :readonly="readonly"
    :loading="loading"
    :dense="dense"
  >
    <template v-slot:selected-item="scope">
      <q-chip
        removable
        dense
        @remove="handleUpdate(null)"
        color="primary"
        text-color="white"
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
