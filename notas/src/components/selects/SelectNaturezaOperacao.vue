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
    @filter="filterNaturezaOperacao"
    :placeholder="placeholder"
    :bottom-slots="bottomSlots"
    :class="customClass"
    :disable="disable"
    :readonly="readonly"
    :loading="loading"
    :dense="dense"
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon
            :name="scope.opt.operacao === 'Entrada' ? 'arrow_downward' : 'arrow_upward'"
            :color="scope.opt.operacao === 'Entrada' ? 'blue' : 'green'"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.label }}</q-item-label>
          <q-item-label
            caption
            :class="scope.opt.operacao === 'Entrada' ? 'text-blue' : 'text-green'"
          >
            {{ scope.opt.operacao }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-slot:selected-item="scope">
      <q-chip
        removable
        dense
        @remove="handleUpdate(null)"
        :color="scope.opt.operacao === 'Entrada' ? 'blue' : 'green'"
        text-color="white"
        :icon="scope.opt.operacao === 'Entrada' ? 'arrow_downward' : 'arrow_upward'"
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

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSelectNaturezaOperacaoStore } from 'stores/selects/naturezaOperacao'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'Natureza de Operação',
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

const naturezaOperacaoStore = useSelectNaturezaOperacaoStore()
const options = ref([])
const loading = ref(false)

// Função para truncar o label
const truncateLabel = (label) => {
  if (!label) return ''
  return label.length > props.maxChars ? label.substring(0, props.maxChars) + '...' : label
}

// Carrega a natureza de operação quando há um modelValue inicial
onMounted(async () => {
  if (props.modelValue) {
    await loadNaturezaOperacao(props.modelValue)
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
        await loadNaturezaOperacao(newValue)
      }
    }
  },
)

const loadNaturezaOperacao = async (codnaturezaoperacao) => {
  try {
    loading.value = true
    const natureza = await naturezaOperacaoStore.fetch(codnaturezaoperacao)
    if (natureza) {
      // Adiciona a natureza de operação nas options se não existir
      const exists = options.value.find((o) => o.value === natureza.value)
      if (!exists) {
        options.value = [natureza]
      }
    }
  } catch (error) {
    console.error('Erro ao carregar natureza de operação:', error)
  } finally {
    loading.value = false
  }
}

const filterNaturezaOperacao = async (val, update) => {
  if (!val || val.length < 2) {
    update(() => {
      options.value = []
    })
    return
  }

  update(async () => {
    try {
      loading.value = true
      options.value = await naturezaOperacaoStore.search(val)
    } catch (error) {
      console.error('Erro ao buscar natureza de operação:', error)
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
