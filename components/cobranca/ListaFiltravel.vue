<script setup>
// Lista com campo de filtro em cima: digita para filtrar, ↑/↓ navegam, Enter escolhe.
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
})

const emit = defineEmits(['escolher'])

const listaRef = ref(null)
const filtro = ref('')

// aqui os dígitos vão para o filtro: a lista não mostra número de atalho (mostrar e não
// funcionar levava a escolher outro portador)
const semAtalho = computed(() => props.opcoes.map((o) => ({ ...o, tecla: null })))

const filtradas = computed(() => {
  const texto = filtro.value.trim().toLowerCase()
  if (!texto) {
    return semAtalho.value
  }
  return semAtalho.value.filter((o) =>
    [o.label, o.caption, o.serial, o.filial].some(
      (c) => c && String(c).toLowerCase().includes(texto),
    ),
  )
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
