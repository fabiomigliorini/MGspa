<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSelectTributoStore } from 'stores/selects/tributo'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  label: {
    type: String,
    default: 'Tributo',
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

const tributoStore = useSelectTributoStore()
const options = ref([])
const loading = ref(false)

// Carrega o tributo quando há um modelValue inicial
onMounted(async () => {
  if (props.modelValue) {
    await loadTributo(props.modelValue)
  }
})

// Recarrega quando o modelValue muda
watch(
  () => props.modelValue,
  async (newValue, oldValue) => {
    if (newValue && newValue !== oldValue) {
      const exists = options.value.find((o) => o.value === newValue)
      if (!exists) {
        await loadTributo(newValue)
      }
    }
  },
)

const loadTributo = async (codtributo) => {
  try {
    loading.value = true
    const tributo = await tributoStore.fetch(codtributo)
    if (tributo) {
      const exists = options.value.find((o) => o.value === tributo.value)
      if (!exists) {
        options.value = [tributo]
      }
    }
  } catch (error) {
    console.error('Erro ao carregar tributo:', error)
  } finally {
    loading.value = false
  }
}

const filterTributo = async (val, update) => {
  if (!val || val.length < 1) {
    update(() => {
      options.value = []
    })
    return
  }

  update(async () => {
    try {
      loading.value = true
      options.value = await tributoStore.search(val)
    } catch (error) {
      console.error('Erro ao buscar tributo:', error)
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
    const tributoSelecionado = options.value.find((o) => o.value === value)
    if (tributoSelecionado) {
      emit('select', tributoSelecionado)
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
    @filter="filterTributo"
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
          <q-icon name="gavel" color="purple" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.sigla }} - {{ scope.opt.nome }}</q-item-label>
          <q-item-label v-if="scope.opt.descricao" caption class="text-grey-7">
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
