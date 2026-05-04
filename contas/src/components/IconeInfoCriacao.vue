<script setup>
import { formataData } from 'src/utils/formatters.js'

defineProps({
  usuariocriacao: { type: String, default: null },
  criacao: { type: String, default: null },
  usuarioalteracao: { type: String, default: null },
  alteracao: { type: String, default: null },
})
</script>

<template>
  <q-btn flat round dense icon="info" size="sm" color="grey-7">
    <q-tooltip>
      <div v-if="criacao">
        Criado<template v-if="usuariocriacao"> por {{ usuariocriacao }}</template>
        em {{ formataData(criacao) }}
      </div>
      <div v-if="alteracao">
        Alterado<template v-if="usuarioalteracao"> por {{ usuarioalteracao }}</template>
        em {{ formataData(alteracao) }}
      </div>
    </q-tooltip>
  </q-btn>
</template>
