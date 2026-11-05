<script setup>
// Select do tipo de conta (origem/destino do grão): PLANTIO / UNIDADE / CONTRATO.
// Substitui o chip fixo — o operador escolhe de onde vem / pra onde vai.
import { computed } from 'vue'
import MgSelect from '@components/MgSelect.vue'
import { CONTATIPOS, PONTOS_POR_SENTIDO } from 'src/utils/carga'

const props = defineProps({
  modelValue: { type: String, default: null },
  papel: { type: String, default: 'ORIGEM' },
  // Só os tipos que o sentido aceita nesse papel (PONTOS_POR_SENTIDO); o
  // servidor recusa o resto.
  sentido: { type: String, default: null },
  label: { type: String, default: 'Tipo' },
})
defineEmits(['update:modelValue'])

// Ícone/cor vêm de CONTATIPO_META (utils/carga) — o mesmo par que os blocos de
// exibição usam pra desenhar o ponto já escolhido.
const opcoes = computed(() => {
  const permitidos = PONTOS_POR_SENTIDO[props.sentido]?.[props.papel]
  if (permitidos) return CONTATIPOS.filter((t) => permitidos.includes(t.value))
  return props.papel === 'DESTINO' ? CONTATIPOS.filter((t) => t.value !== 'PLANTIO') : CONTATIPOS
})
</script>

<template>
  <MgSelect
    :model-value="modelValue"
    :options="opcoes"
    :label="label"
    option-value="value"
    option-label="label"
    emit-value
    map-options
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
  </MgSelect>
</template>
