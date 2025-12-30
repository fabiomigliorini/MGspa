<script setup>
import { ref, onMounted } from 'vue'
import { useSelectLocalEstoqueStore } from 'stores/selects/localEstoque'

const props = defineProps({
  modelValue: {
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

const localEstoqueStore = useSelectLocalEstoqueStore()
const options = ref([])
const loading = ref(false)

// Função para truncar o label
const truncateLabel = (label) => {
  if (!label) return ''
  return label.length > props.maxChars ? label.substring(0, props.maxChars) + '...' : label
}

// Carrega todos os locais de estoque ao montar o componente
onMounted(async () => {
  try {
    loading.value = true
    await localEstoqueStore.loadAll()
    // Inicializa com todos os locais
    options.value = localEstoqueStore.locais
  } catch (error) {
    console.error('Erro ao carregar locais de estoque:', error)
  } finally {
    loading.value = false
  }
})

// const filterLocalEstoque = (val, update) => {
//   update(() => {
//     if (!val) {
//       // Se não tem busca, mostra todos
//       options.value = localEstoqueStore.locais
//     } else {
//       // Filtra localmente
//       options.value = localEstoqueStore.filter(val)
//     }
//   })
// }

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
