<script setup>
function formataData(data) {
  if (!data) return ''
  const d = new Date(data)
  return d.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

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
