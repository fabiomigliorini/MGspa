<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSelectTributacaoStore } from 'stores/selects/tributacao'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'Tributacao',
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
  rules: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:modelValue', 'clear', 'select'])

const tributacaoStore = useSelectTributacaoStore()
const options = ref([])
const loading = ref(false)

onMounted(async () => {
  await loadTributacoes()
})

watch(
  () => props.modelValue,
  async (newValue) => {
    if (newValue && options.value.length === 0) {
      await loadTributacoes()
    }
  },
)

const loadTributacoes = async () => {
  try {
    loading.value = true
    options.value = await tributacaoStore.loadAll()
  } catch (error) {
    console.error('Erro ao carregar tributacoes:', error)
    options.value = []
  } finally {
    loading.value = false
  }
}

const filterTributacao = async (val, update) => {
  update(async () => {
    if (!tributacaoStore.tributacoesLoaded) {
      await loadTributacoes()
    } else {
      if (val === '') {
        options.value = tributacaoStore.tributacoes
      } else {
        const needle = val.toLowerCase()
        options.value = tributacaoStore.tributacoes.filter(
          (t) => t.tributacao?.toLowerCase().indexOf(needle) > -1,
        )
      }
    }
  })
}

const handleUpdate = (value) => {
  emit('update:modelValue', value)
  if (value === null) {
    emit('clear')
  } else {
    const tributacaoSelecionada = options.value.find((o) => o.value === value)
    if (tributacaoSelecionada) {
      emit('select', tributacaoSelecionada)
    }
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
    input-debounce="300"
    @filter="filterTributacao"
    :placeholder="placeholder"
    :bottom-slots="bottomSlots"
    :class="customClass"
    :disable="disable"
    :readonly="readonly"
    :loading="loading"
    :dense="dense"
    :rules="rules"
  >
    <template v-slot:prepend>
      <q-icon name="receipt" />
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          {{ options.length === 0 ? 'Digite para buscar' : 'Nenhum resultado' }}
        </q-item-section>
      </q-item>
    </template>

    <template v-if="$slots.append" v-slot:append>
      <slot name="append" />
    </template>
  </q-select>
</template>
