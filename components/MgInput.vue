<script setup>
// q-input da casa: igual ao q-input do Quasar, com as duas regras que a gente
// repetia em cada tela — o X de limpar e o campo readonly ficam fora da ordem
// do Tab. O X do `clearable` do Quasar tem tabindex="0" fixo no fonte
// (use-field.js), por isso o limpar aqui é desenhado no slot #append.
// Mesmo comportamento do MgInputValor e do MgInputData.
import { computed, ref } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: [String, Number], default: null },
  outlined: { type: Boolean, default: true },
  clearable: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  disable: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)

const temValor = computed(
  () => props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== '',
)

const mostraLimpar = computed(
  () => props.clearable && !props.readonly && !props.disable && temValor.value,
)

const nativeEl = computed(() => inputRef.value?.nativeEl)

defineExpose({
  focus: () => inputRef.value?.focus(),
  blur: () => inputRef.value?.blur(),
  select: () => inputRef.value?.select(),
  validate: (valor) => inputRef.value?.validate(valor),
  resetValidation: () => inputRef.value?.resetValidation(),
  nativeEl,
})
</script>

<template>
  <q-input
    ref="inputRef"
    v-bind="$attrs"
    :model-value="modelValue"
    :outlined="outlined"
    :readonly="readonly"
    :disable="disable"
    :tabindex="readonly ? -1 : $attrs.tabindex"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <template v-if="$slots.prepend" #prepend><slot name="prepend" /></template>
    <template v-if="mostraLimpar || $slots.append" #append>
      <q-icon
        v-if="mostraLimpar"
        name="cancel"
        tabindex="-1"
        class="cursor-pointer"
        @click.stop="emit('update:modelValue', null)"
      />
      <slot name="append" />
    </template>
    <template v-if="$slots.before" #before><slot name="before" /></template>
    <template v-if="$slots.after" #after><slot name="after" /></template>
    <template v-if="$slots.hint" #hint><slot name="hint" /></template>
  </q-input>
</template>
