<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSelectTipoProdutoStore } from 'stores/selects/tipoProduto'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'Tipo de Produto',
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

const tipoProdutoStore = useSelectTipoProdutoStore()
const options = ref([])
const loading = ref(false)

// Função para truncar o label
const truncateLabel = (label) => {
  if (!label) return ''
  return label.length > props.maxChars ? label.substring(0, props.maxChars) + '...' : label
}

// Carrega o tipo de produto quando há um modelValue inicial
onMounted(async () => {
  if (props.modelValue) {
    await loadTipoProduto(props.modelValue)
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
        await loadTipoProduto(newValue)
      }
    }
  },
)

const loadTipoProduto = async (codtipoproduto) => {
  try {
    loading.value = true
    const tipo = await tipoProdutoStore.fetch(codtipoproduto)
    if (tipo) {
      // Adiciona o tipo de produto nas options se não existir
      const exists = options.value.find((o) => o.value === tipo.value)
      if (!exists) {
        options.value = [tipo]
      }
    }
  } catch (error) {
    console.error('Erro ao carregar tipo de produto:', error)
  } finally {
    loading.value = false
  }
}

const filterTipoProduto = async (val, update) => {
  if (!val || val.length < 2) {
    update(() => {
      options.value = []
    })
    return
  }

  update(async () => {
    try {
      loading.value = true
      options.value = await tipoProdutoStore.search(val)
    } catch (error) {
      console.error('Erro ao buscar tipo de produto:', error)
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
  <q-select :model-value="modelValue" @update:model-value="handleUpdate" :label="label" outlined clearable
    :options="options" option-value="value" option-label="label" emit-value map-options use-input input-debounce="500"
    @filter="filterTipoProduto" :placeholder="placeholder" :bottom-slots="bottomSlots" :class="customClass"
    :disable="disable" :readonly="readonly" :loading="loading" :dense="dense">
    <template v-slot:selected-item="scope">
      <span :title="scope.opt.label">{{ truncateLabel(scope.opt.label) }}</span>
    </template>

    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label>{{ scope.opt.label }}</q-item-label>
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

    <template v-if="$slots.prepend" v-slot:prepend>
      <slot name="prepend" />
    </template>

    <template v-if="$slots.append" v-slot:append>
      <slot name="append" />
    </template>
  </q-select>
</template>
