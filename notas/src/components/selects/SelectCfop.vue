<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSelectCfopStore } from 'stores/selects/cfop'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'CFOP',
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

const cfopStore = useSelectCfopStore()
const options = ref([])
const loading = ref(false)

onMounted(async () => {
  await loadCfops()
})

watch(
  () => props.modelValue,
  async (newValue) => {
    if (newValue && options.value.length === 0) {
      await loadCfops()
    }
  },
)

const loadCfops = async () => {
  try {
    loading.value = true
    options.value = await cfopStore.loadAll()
  } catch (error) {
    console.error('Erro ao carregar CFOPs:', error)
    options.value = []
  } finally {
    loading.value = false
  }
}

const filterCfop = async (val, update) => {
  update(async () => {
    if (!cfopStore.cfopsLoaded) {
      await loadCfops()
    } else {
      if (val === '') {
        options.value = cfopStore.cfops
      } else {
        const needle = val.toLowerCase()
        options.value = cfopStore.cfops.filter(
          (c) =>
            String(c.codcfop)?.toLowerCase().indexOf(needle) > -1 ||
            c.cfop?.toLowerCase().indexOf(needle) > -1 ||
            c.descricao?.toLowerCase().indexOf(needle) > -1,
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
    const cfopSelecionado = options.value.find((o) => o.value === value)
    if (cfopSelecionado) {
      emit('select', cfopSelecionado)
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
    @filter="filterCfop"
    :placeholder="placeholder"
    :bottom-slots="bottomSlots"
    :class="customClass"
    :disable="disable"
    :readonly="readonly"
    :loading="loading"
    :dense="dense"
    :rules="rules"
    class="ellipsis"
  >
    <template v-slot:prepend>
      <q-icon name="numbers" />
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey ellipsis">
          {{ options.length === 0 ? 'Carregando...' : 'Nenhum resultado' }}
        </q-item-section>
      </q-item>
    </template>

    <template v-if="$slots.append" v-slot:append>
      <slot name="append" class="ellipsis" />
    </template>
  </q-select>
</template>
