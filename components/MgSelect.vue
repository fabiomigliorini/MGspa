<script setup>
// q-select da casa: igual ao q-select do Quasar, com as mesmas regras do MgInput — o X de
// limpar e o campo readonly ficam fora da ordem do Tab. O X do `clearable` do Quasar tem
// tabindex="0" fixo no fonte (use-field.js), por isso o limpar aqui é desenhado no slot #append.
// Os demais slots (option, selected-item, no-option, prepend…) passam direto pro q-select.
import { computed, ref } from 'vue'
import { useQuasar } from 'quasar'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { default: null },
  outlined: { type: Boolean, default: true },
  clearable: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  disable: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const $q = useQuasar()
const selectRef = ref(null)

const temValor = computed(() =>
  Array.isArray(props.modelValue)
    ? props.modelValue.length > 0
    : props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== '',
)

const mostraLimpar = computed(
  () => props.clearable && !props.readonly && !props.disable && temValor.value,
)

// Igual ao X do Quasar: o clique para no ícone (.stop.prevent — sem o prevent, o <label> do
// QField repassa o clique pro input e o menu abre) e o foco volta pro campo, menos no celular,
// pra não abrir o teclado.
function limpar() {
  emit('update:modelValue', null)
  if (!$q.platform.is.mobile) selectRef.value?.focus()
}

defineExpose({
  focus: () => selectRef.value?.focus(),
  blur: () => selectRef.value?.blur(),
  showPopup: () => selectRef.value?.showPopup(),
  hidePopup: () => selectRef.value?.hidePopup(),
  updateInputValue: (valor, semFiltro) => selectRef.value?.updateInputValue(valor, semFiltro),
  validate: (valor) => selectRef.value?.validate(valor),
  resetValidation: () => selectRef.value?.resetValidation(),
})
</script>

<template>
  <q-select
    ref="selectRef"
    v-bind="$attrs"
    :model-value="modelValue"
    :outlined="outlined"
    :readonly="readonly"
    :disable="disable"
    :tabindex="readonly ? -1 : $attrs.tabindex"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <template
      v-for="nome in Object.keys($slots).filter((n) => n !== 'append')"
      :key="nome"
      #[nome]="escopo"
    >
      <slot :name="nome" v-bind="escopo || {}" />
    </template>
    <template v-if="mostraLimpar || $slots.append" #append>
      <q-icon
        v-if="mostraLimpar"
        name="cancel"
        tabindex="-1"
        class="cursor-pointer"
        @click.stop.prevent="limpar"
      />
      <slot name="append" />
    </template>
  </q-select>
</template>
