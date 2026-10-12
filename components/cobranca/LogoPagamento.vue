<script setup>
// Visual único de pagamento (passo do wizard Receber e listagem do negócio): logo da marca
// (bandeira, operadora, banco) ou ícone branco sobre a cor da forma, sempre quadrado arredondado
import { ref, watch } from 'vue'

const props = defineProps({
  logo: {
    type: String,
    default: null,
  },
  icone: {
    type: String,
    default: 'payments',
  },
  cor: {
    type: String,
    default: 'blue-grey-5',
  },
  size: {
    type: String,
    default: '44px',
  },
})

// logo que não existe (ex.: banco sem svg em public/bancos) cai no ícone
const falhou = ref(false)
watch(
  () => props.logo,
  () => (falhou.value = false),
)
</script>
<template>
  <q-avatar v-if="logo && !falhou" rounded :size="size">
    <img :src="logo" @error="falhou = true" />
  </q-avatar>
  <q-avatar v-else rounded :size="size" :color="cor" text-color="white" :icon="icone" />
</template>
