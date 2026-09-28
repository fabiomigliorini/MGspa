<script setup>
// Lista com campo de filtro em cima: digita para filtrar, ↑/↓ navegam, Enter escolhe.
// Se o texto digitado não bate com nenhum serial cadastrado, oferece "usar o digitado".
import { ref, computed } from 'vue'
import MgInput from '@components/MgInput.vue'
import ListaOpcoes from './ListaOpcoes.vue'

const props = defineProps({
  opcoes: {
    type: Array,
    required: true,
  },
  label: {
    type: String,
    default: 'Filtrar',
  },
  permitirDigitado: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['escolher'])

const listaRef = ref(null)
const filtro = ref('')

const filtradas = computed(() => {
  const texto = filtro.value.trim().toLowerCase()
  let lista = props.opcoes
  if (texto) {
    lista = props.opcoes.filter((o) =>
      [o.label, o.caption, o.serial].some((c) => c && String(c).toLowerCase().includes(texto)),
    )
  }
  const batePorSerial = lista.some((o) => o.serial && o.serial.toLowerCase() === texto)
  if (props.permitirDigitado && texto && !batePorSerial) {
    lista = [
      ...lista,
      {
        valor: '__digitado__',
        label: `Usar serial digitado: ${filtro.value.trim()}`,
        serial: filtro.value.trim(),
        icone: 'keyboard',
        cor: 'indigo-6',
        digitado: true,
      },
    ]
  }
  return lista
})

const escolher = (opcao) => {
  emit('escolher', opcao)
}

// devolve true quando consumiu a tecla; o resto é digitação no filtro
const tecla = (e) => {
  if (['ArrowUp', 'ArrowDown', 'Enter'].includes(e.key)) {
    return !!listaRef.value?.tecla(e)
  }
  return false
}

defineExpose({ tecla })
</script>
<template>
  <div>
    <!-- a rolagem é do dialog: o filtro gruda no topo enquanto a lista rola -->
    <MgInput
      v-model="filtro"
      :label="label"
      autofocus
      class="q-pb-sm bg-white"
      style="position: sticky; top: 0; z-index: 1"
    >
      <template #prepend>
        <q-icon name="search" />
      </template>
    </MgInput>
    <lista-opcoes ref="listaRef" :opcoes="filtradas" @escolher="escolher" />
    <div v-if="!filtradas.length" class="text-grey-6 text-italic q-pa-sm">Nada encontrado</div>
  </div>
</template>
