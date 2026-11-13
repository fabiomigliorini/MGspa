<script setup>
// Select de contrato. Lê a store carga — funciona offline. O rótulo (contrato +
// pessoa) vem do helper da store. Só oferece contrato da safra da carga (a
// safra define a cultura) e da operação do lado: compra na origem, venda no
// destino. O contrato já escolhido segue com rótulo mesmo fora do filtro.
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import MgSelect from '@components/MgSelect.vue'
import { useCargaStore } from 'src/stores/carga'

const props = defineProps({
  modelValue: { type: Number, default: null },
  operacao: { type: String, default: 'compra' },
  codsafra: { type: Number, default: null },
  label: { type: String, default: null },
})
defineEmits(['update:modelValue'])

const store = useCargaStore()
const { contratosAtivos } = storeToRefs(store)

const opcoes = computed(() =>
  contratosAtivos.value.filter(
    (c) =>
      c.operacao === props.operacao.toUpperCase() &&
      (!props.codsafra || c.codsafra === props.codsafra),
  ),
)

const lbl = computed(() => props.label || `Contrato (${props.operacao})`)
</script>

<template>
  <MgSelect
    :model-value="modelValue"
    :options="opcoes"
    :label="lbl"
    option-value="codcontrato"
    :option-label="(c) => store.rotuloContrato(c?.codcontrato ?? c)"
    emit-value
    map-options
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #selected>
      {{ modelValue ? store.rotuloContrato(modelValue) : '' }}
    </template>
    <template #no-option>
      <q-item>
        <q-item-section class="text-grey-7">Nenhum contrato de {{ operacao }} desta safra</q-item-section>
      </q-item>
    </template>
  </MgSelect>
</template>
