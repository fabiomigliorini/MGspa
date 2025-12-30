<script setup>
import { computed } from 'vue'
import { useSelectTipoClienteStore } from 'stores/selects/tipoCliente'

defineProps({
  modelValue: {
    type: String,
    default: null,
  },
  label: {
    type: String,
    default: 'Tipo de Cliente',
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

const tipoClienteStore = useSelectTipoClienteStore()

// Opções de tipo de cliente
const options = computed(() => tipoClienteStore.tiposCliente)

// Retorna a cor do badge de tipo de cliente
const getColor = (tipo) => {
  const colors = {
    PFC: 'blue',
    PFN: 'cyan',
    PJC: 'green',
    PJN: 'teal',
  }
  return colors[tipo] || 'grey'
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
    :placeholder="placeholder"
    :bottom-slots="bottomSlots"
    :class="customClass"
    :disable="disable"
    :readonly="readonly"
    :dense="dense"
  >
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon
            name="person"
            :color="getColor(scope.opt.value)"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ scope.opt.label }}</q-item-label>
          <q-item-label caption class="text-grey-6">
            {{ scope.opt.descricao }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-slot:selected-item="scope">
      <q-chip
        removable
        dense
        @remove="handleUpdate(null)"
        :color="getColor(scope.opt.value)"
        text-color="white"
        icon="person"
      >
        {{ scope.opt.label }}
      </q-chip>
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          Nenhum resultado
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
