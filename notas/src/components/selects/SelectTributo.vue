<script setup>
import { ref, onMounted, watch } from 'vue'
import { useSelectTributoStore } from 'stores/selects/tributo'
import { getEnteIcon, getEnteColor } from 'src/composables/useTributoIcons'

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

// Carrega todos os tributos ao montar
onMounted(async () => {
  await loadTributos()
})

// Recarrega quando o modelValue muda
watch(
  () => props.modelValue,
  async (newValue) => {
    if (newValue && options.value.length === 0) {
      await loadTributos()
    }
  },
)

const loadTributos = async () => {
  try {
    loading.value = true
    options.value = await tributoStore.loadAll()
  } catch (error) {
    console.error('Erro ao carregar tributos:', error)
    options.value = []
  } finally {
    loading.value = false
  }
}

const filterTributo = async (val, update) => {
  update(async () => {
    if (!tributoStore.tributosLoaded) {
      await loadTributos()
    } else {
      // Filtra localmente
      if (val === '') {
        options.value = tributoStore.tributos
      } else {
        const needle = val.toLowerCase()
        options.value = tributoStore.tributos.filter(
          (t) =>
            t.codigo?.toLowerCase().indexOf(needle) > -1 ||
            t.sigla?.toLowerCase().indexOf(needle) > -1 ||
            t.ente?.toLowerCase().indexOf(needle) > -1 ||
            t.descricao?.toLowerCase().indexOf(needle) > -1,
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
    const tributoSelecionado = options.value.find((o) => o.value === value)
    if (tributoSelecionado) {
      emit('select', tributoSelecionado)
    }
  }
}
</script>

<template>
  <q-select :model-value="modelValue" @update:model-value="handleUpdate" :label="label" outlined clearable
    :options="options" option-value="value" option-label="label" emit-value map-options use-input input-debounce="300"
    @filter="filterTributo" :placeholder="placeholder" :bottom-slots="bottomSlots" :class="customClass"
    :disable="disable" :readonly="readonly" :loading="loading" :dense="dense">
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon :name="getEnteIcon(scope.opt.ente)" :color="getEnteColor(scope.opt.ente)" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.codigo }} - {{ scope.opt.ente }}</q-item-label>
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
