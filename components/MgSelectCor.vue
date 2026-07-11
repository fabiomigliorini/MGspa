<script>
// ===== Select ESTÁTICO de cor (paleta fixa, sem backend) =====
// O campo mostra um box com a cor selecionada; a lista abre os swatches da
// paleta. Cada app pode passar sua própria :palette (default: paleta saturada
// com bom contraste sobre satélite/verde).
// Em module scope (não no <script setup>): defineProps é hoisted e não pode
// referenciar binding declarado dentro do setup.
export const PALETA_PADRAO = [
  '#e53935',
  '#fb8c00',
  '#fdd835',
  '#d81b60',
  '#8e24aa',
  '#00acc1',
  '#3949ab',
  '#f4511e',
  '#c0ca33',
  '#5e35b1',
  '#00897b',
  '#ec407a',
  '#ffb300',
  '#26a69a',
]
</script>

<script setup>
defineProps({
  modelValue: { type: String, default: null },
  label: { type: String, default: 'Cor' },
  clearable: { type: Boolean, default: false },
  palette: { type: Array, default: () => PALETA_PADRAO },
})
const emit = defineEmits(['update:modelValue', 'select'])

function onUpdate(v) {
  emit('update:modelValue', v)
  emit('select', v || null)
}

// Borda sutil no swatch p/ cores claras (amarelo) não sumirem no fundo branco.
const swatch = 'border: 1px solid rgba(0, 0, 0, 0.15)'
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="palette"
    :label="label"
    outlined
    :clearable="clearable"
    @update:model-value="onUpdate"
    v-bind="$attrs"
  >
    <!-- Campo: box da cor selecionada -->
    <template #selected>
      <div
        v-if="modelValue"
        class="rounded-borders"
        :style="`background-color: ${modelValue}; width: 40px; height: 22px; ${swatch}`"
      />
    </template>
    <!-- Lista: swatch + hex -->
    <template #option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section avatar>
          <div
            class="rounded-borders"
            :style="`background-color: ${scope.opt}; width: 40px; height: 22px; ${swatch}`"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-uppercase text-grey-7">{{ scope.opt }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template v-if="$slots.prepend" #prepend><slot name="prepend" /></template>
    <template v-if="$slots.before" #before><slot name="before" /></template>
    <template v-if="$slots.after" #after><slot name="after" /></template>
    <template v-if="$slots.hint" #hint><slot name="hint" /></template>
  </q-select>
</template>
