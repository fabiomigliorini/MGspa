<script setup>
// Uma auditoria (TASK-204) como linha de q-list: o que mudou, quem, quando, a justificativa e,
// campo a campo, antes → depois. Usado no MgHistorico e na tela da ocorrência do contas.
import { computed } from 'vue'
import { formataTimestamp } from '@components/formatters'

const props = defineProps({
  auditoria: { type: Object, required: true },
})

// campo: antes → depois, só os campos que a auditoria gravou
const valor = (v) => {
  if (v === null || v === undefined) return '—'
  if (typeof v === 'object') return JSON.stringify(v).replace(/,/g, ', ')
  return v
}
const mudancas = computed(() => {
  const antes = props.auditoria.antes ?? {}
  const depois = props.auditoria.depois ?? {}
  return [...new Set([...Object.keys(antes), ...Object.keys(depois)])].map(
    (campo) => `${campo}: ${valor(antes[campo])} → ${valor(depois[campo])}`,
  )
})
</script>

<template>
  <q-item>
    <q-item-section>
      <q-item-label class="text-weight-medium">{{ auditoria.tipodescricao }}</q-item-label>
      <q-item-label caption>
        {{ auditoria.usuario ?? 'sem usuário' }} · {{ formataTimestamp(auditoria.criacao) }}
      </q-item-label>
      <q-item-label v-if="auditoria.justificativa" caption class="text-grey-9">
        “{{ auditoria.justificativa }}”
      </q-item-label>
      <q-item-label v-for="m in mudancas" :key="m" caption>{{ m }}</q-item-label>
    </q-item-section>
  </q-item>
</template>
