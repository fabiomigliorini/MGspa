<script setup>
import { ref, computed, watch } from 'vue'
import { useCulturaStore } from 'src/stores/cultura'

// Avatar com o emoji da cultura. Recebe só o codcultura; o emoji vem do cache
// Pinia (store cultura), buscando no backend uma única vez. Sem emoji cadastrado
// cai no ícone padrão.
const props = defineProps({
  codcultura: { type: [Number, String], default: null },
  size: { type: String, default: undefined },
  color: { type: String, default: 'light-green-1' },
  fallbackColor: { type: String, default: 'light-green-7' },
  fallbackIcon: { type: String, default: 'grain' },
})

const store = useCulturaStore()
const cultura = ref(null)
const emoji = computed(() => cultura.value?.icone || null)

watch(
  () => props.codcultura,
  async (cod) => {
    cultura.value = cod ? await store.buscar(cod) : null
  },
  { immediate: true },
)
</script>

<template>
  <q-avatar
    :size="size"
    :color="emoji ? color : fallbackColor"
    :text-color="emoji ? undefined : 'white'"
  >
    <span v-if="emoji" style="font-size: 0.55em">{{ emoji }}</span>
    <q-icon v-else :name="fallbackIcon" />
  </q-avatar>
</template>
