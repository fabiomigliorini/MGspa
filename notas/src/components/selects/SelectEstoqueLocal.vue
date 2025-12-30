<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useSelectEstoqueLocalStore } from 'stores/selects/estoqueLocal'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  codfilial: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'Local de Estoque',
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
    default: 20,
  },
})

const emit = defineEmits(['update:modelValue', 'clear'])

const estoqueLocalStore = useSelectEstoqueLocalStore()
const options = ref([])
const loading = ref(false)

// Função para truncar o label
const truncateLabel = (label) => {
  if (!label) return ''
  return label.length > props.maxChars ? label.substring(0, props.maxChars) + '...' : label
}

// Computed para locais filtrados por filial
const locaisFiltrados = computed(() => {
  if (props.codfilial) {
    return estoqueLocalStore.filterByFilial(props.codfilial)
  }
  return estoqueLocalStore.locais
})

// Carrega todos os locais de estoque ao montar o componente
onMounted(async () => {
  try {
    loading.value = true
    await estoqueLocalStore.loadAll()
    // Inicializa com locais filtrados por filial
    options.value = locaisFiltrados.value
  } catch (error) {
    console.error('Erro ao carregar locais de estoque:', error)
  } finally {
    loading.value = false
  }
})

// Observa mudanças no codfilial
watch(
  () => props.codfilial,
  (newCodfilial, oldCodfilial) => {
    if (newCodfilial !== oldCodfilial) {
      // Atualiza as opções filtradas
      options.value = locaisFiltrados.value

      // Se houver apenas uma opção após o filtro, seleciona automaticamente
      if (locaisFiltrados.value.length === 1) {
        emit('update:modelValue', locaisFiltrados.value[0].value)
      }
      // Se a opção selecionada não está mais disponível, limpa a seleção
      else if (props.modelValue) {
        const exists = locaisFiltrados.value.find((l) => l.value === props.modelValue)
        if (!exists) {
          emit('update:modelValue', null)
          emit('clear')
        }
      }
    }
  },
)

const filterEstoqueLocal = (val, update) => {
  update(() => {
    if (!val) {
      // Se não tem busca, mostra locais filtrados por filial
      options.value = locaisFiltrados.value
    } else {
      // Filtra por filial e texto
      options.value = estoqueLocalStore.filterByFilialAndText(props.codfilial, val)
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
    @filter="filterEstoqueLocal"
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
          <q-icon name="warehouse" color="orange" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.label }}</q-item-label>
          <q-item-label caption class="text-grey-7"> Código: {{ scope.opt.value }} </q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-slot:selected-item="scope">
      <q-chip
        removable
        dense
        @remove="handleUpdate(null)"
        color="orange"
        text-color="white"
        icon="warehouse"
      >
        {{ truncateLabel(scope.opt.label) }}
      </q-chip>
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey"> Nenhum local de estoque encontrado </q-item-section>
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
