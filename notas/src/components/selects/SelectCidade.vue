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
  >
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
import { useSelectsStore } from 'stores/selects'

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
    default: 'Digite para buscar',
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
})

const emit = defineEmits(['update:modelValue', 'clear'])

const selectsStore = useSelectsStore()
const options = ref([])
const loading = ref(false)

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
    const cidade = await selectsStore.fetchCidade(codcidade)
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
      options.value = await selectsStore.searchCidades(val)
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
