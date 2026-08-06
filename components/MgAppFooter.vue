<script setup>
import { formataTimestampIso } from './formatters'

defineProps({
  appName: {
    type: String,
    required: true,
  },
})

const version = process.env.APP_VERSION
const buildDate = formataTimestampIso(process.env.BUILD_DATE)
const commitNumber = process.env.COMMIT_NUMBER
// Identidade exata do build — é o mesmo valor comparado com /version.json em
// pwaAtualizacao.js. Serve pro suporte: "me diz o que está escrito no rodapé".
const buildId = process.env.BUILD_ID
</script>

<template>
  <div class="q-ma-xs text-center">
    <span class="gt-xs"> App {{ appName }} | MG Papelaria &copy; | </span>
    <span class="gt-xs"> v{{ version }} | </span>
    <span v-if="commitNumber"> #{{ commitNumber }} | </span>
    <span v-if="buildDate" :title="buildId"> Build {{ buildDate }}</span>
  </div>
</template>
