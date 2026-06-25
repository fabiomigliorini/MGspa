<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { api } from 'src/services/api'

// Radio de seleção de cultura mostrando o emoji de cada uma. Busca a lista de
// culturas uma vez. Além do v-model (codcultura), emite o evento `change` com o
// objeto completo da cultura escolhida, pra tela poder reagir (ex.: sugerir os
// anos de plantio/colheita conforme o ciclo da cultura).
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Cultura' },
  autofocus: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'change'])

const grupo = ref(null)
const culturas = ref([])
const opcoes = computed(() =>
  culturas.value.map((c) => ({ label: c.cultura, value: c.codcultura, icone: c.icone })),
)

function selecionar(cod) {
  emit('update:modelValue', cod)
  emit('change', culturas.value.find((c) => c.codcultura === cod) || null)
}

onMounted(async () => {
  const { data } = await api.get('v1/cultura')
  culturas.value = data.data ?? data
  // q-option-group não foca sozinho: depois que os radios renderizam, foca o
  // primeiro pra ser o campo inicial do formulário.
  if (props.autofocus) {
    await nextTick()
    grupo.value?.$el?.querySelector('.q-radio')?.focus()
  }
})
</script>

<template>
  <div>
    <div v-if="label" class="text-caption text-grey-7 q-mb-xs">{{ label }}</div>
    <q-option-group
      ref="grupo"
      :model-value="modelValue"
      :options="opcoes"
      type="radio"
      color="primary"
      inline
      @update:model-value="selecionar"
    >
      <template v-slot:label="opt">
        <span class="q-mr-xs" style="font-size: 1.3em">{{ opt.icone }}</span>
        <span>{{ opt.label }}</span>
      </template>
    </q-option-group>
  </div>
</template>
