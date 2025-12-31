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
})

const emit = defineEmits(['update:modelValue', 'clear', 'select'])

const cfopStore = useSelectCfopStore()
const options = ref([])
const loading = ref(false)

// Carrega o CFOP quando há um modelValue inicial
onMounted(async () => {
  if (props.modelValue) {
    await loadCfop(props.modelValue)
  }
})

// Recarrega quando o modelValue muda
watch(
  () => props.modelValue,
  async (newValue, oldValue) => {
    if (newValue && newValue !== oldValue) {
      const exists = options.value.find((o) => o.value === newValue)
      if (!exists) {
        await loadCfop(newValue)
      }
    }
  },
)

const loadCfop = async (codcfop) => {
  try {
    loading.value = true
    const cfop = await cfopStore.fetch(codcfop)
    if (cfop) {
      const exists = options.value.find((o) => o.value === cfop.value)
      if (!exists) {
        options.value = [cfop]
      }
    }
  } catch (error) {
    console.error('Erro ao carregar CFOP:', error)
  } finally {
    loading.value = false
  }
}

const filterCfop = async (val, update) => {
  if (!val || val.length < 1) {
    update(() => {
      options.value = []
    })
    return
  }

  update(async () => {
    try {
      loading.value = true
      options.value = await cfopStore.search(val)
    } catch (error) {
      console.error('Erro ao buscar CFOP:', error)
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
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon name="receipt_long" color="orange" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.codigo }} - {{ scope.opt.nome }}</q-item-label>
          <q-item-label caption class="text-grey-7">
            {{ scope.opt.descricao }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          {{ options.length === 0 ? 'Digite para buscar' : 'Nenhum resultado' }}
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
