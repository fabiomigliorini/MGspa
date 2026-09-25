<script setup>
// Select do tipo de conta (origem/destino do grão): PLANTIO / UNIDADE / CONTRATO.
// Substitui o chip fixo — o operador escolhe de onde vem / pra onde vai.
import { computed } from 'vue'
import { CONTATIPOS } from 'src/utils/carga'

const props = defineProps({
  modelValue: { type: String, default: null },
  // ORIGEM aceita os 3 tipos; DESTINO não recebe grão de volta pro talhão.
  papel: { type: String, default: 'ORIGEM' },
  label: { type: String, default: 'Tipo' },
})
defineEmits(['update:modelValue'])

// Ícone/cor vêm de CONTATIPO_META (utils/carga) — o mesmo par que os blocos de
// exibição usam pra desenhar o ponto já escolhido.
const opcoes = computed(() =>
  props.papel === 'DESTINO' ? CONTATIPOS.filter((t) => t.value !== 'PLANTIO') : CONTATIPOS,
)
</script>

<template>
  <q-select
    :model-value="modelValue"
    :options="opcoes"
    :label="label"
    option-value="value"
    option-label="label"
    emit-value
    map-options
    outlined
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #option="{ opt, itemProps }">
      <q-item v-bind="itemProps">
        <q-item-section avatar>
          <q-icon :name="opt.icon" :color="opt.color" />
        </q-item-section>
        <q-item-section>{{ opt.label }}</q-item-section>
      </q-item>
    </template>
  </q-select>
</template>
