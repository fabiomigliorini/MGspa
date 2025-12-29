<template>
  <q-select
    :model-value="modelValue"
    @update:model-value="handleUpdate"
    :label="label"
    outlined
    clearable
    :options="options"
    option-value="value"
    option-label="sigla"
    emit-value
    map-options
    use-input
    @filter="filterEstados"
    :placeholder="placeholder"
    :bottom-slots="bottomSlots"
    :class="customClass"
    :disable="disable"
    :readonly="readonly"
    :loading="loading"
    :dense="dense"
  >
    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey"> Nenhum resultado </q-item-section>
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
import { useSelectEstadoStore } from 'stores/selects/estado'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'UF',
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
})

const emit = defineEmits(['update:modelValue', 'clear'])

const estadoStore = useSelectEstadoStore()
const options = ref([])
const loading = ref(false)

// Carrega estados ao montar
onMounted(async () => {
  await loadEstados()
})

// Recarrega quando o modelValue muda (para caso seja setado externamente)
watch(
  () => props.modelValue,
  async (newValue) => {
    if (newValue && options.value.length === 0) {
      await loadEstados()
    }
  },
)

const loadEstados = async () => {
  try {
    loading.value = true
    options.value = await estadoStore.search()
  } catch (error) {
    console.error('Erro ao carregar estados:', error)
    options.value = []
  } finally {
    loading.value = false
  }
}

const filterEstados = async (val, update) => {
  update(async () => {
    if (!estadoStore.estadosLoaded) {
      await loadEstados()
    } else {
      // Filtra localmente
      if (val === '') {
        options.value = estadoStore.estados
      } else {
        const needle = val.toLowerCase()
        options.value = estadoStore.estados.filter(
          (v) =>
            v.sigla.toLowerCase().indexOf(needle) > -1 || v.nome.toLowerCase().indexOf(needle) > -1,
        )
      }
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
