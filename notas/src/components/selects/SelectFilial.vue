<script setup>
import { ref, onMounted } from 'vue'
import { useSelectFilialStore } from 'stores/selects/filial'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'Filial',
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

const filialStore = useSelectFilialStore()
const options = ref([])
const loading = ref(false)

// Função para truncar o label
const truncateLabel = (label) => {
  if (!label) return ''
  return label.length > props.maxChars ? label.substring(0, props.maxChars) + '...' : label
}

// Carrega todas as filiais ao montar o componente
onMounted(async () => {
  try {
    loading.value = true
    await filialStore.loadAll()
    // Inicializa com todas as filiais
    options.value = filialStore.filiais
  } catch (error) {
    console.error('Erro ao carregar filiais:', error)
  } finally {
    loading.value = false
  }
})

const filterFilial = (val, update) => {
  update(() => {
    if (!val) {
      // Se não tem busca, mostra todas
      options.value = filialStore.filiais
    } else {
      // Filtra localmente
      options.value = filialStore.filter(val)
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
    @filter="filterFilial"
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
          <q-icon name="store" color="primary" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.label }}</q-item-label>
          <q-item-label caption class="text-grey-7">
            Código: {{ scope.opt.value }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-slot:selected-item="scope">
      <q-chip
        removable
        dense
        @remove="handleUpdate(null)"
        color="primary"
        text-color="white"
        icon="store"
      >
        {{ truncateLabel(scope.opt.label) }}
      </q-chip>
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          Nenhuma filial encontrada
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
