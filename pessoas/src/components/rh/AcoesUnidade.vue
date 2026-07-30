<script setup>
defineProps({
  unidade: { type: Object, required: true },
})

const emit = defineEmits(['editar', 'toggle-inativo', 'excluir', 'adicionar-setor'])
</script>

<template>
  <div class="row items-center no-wrap">
    <q-btn flat round size="sm" color="grey-7" icon="edit" @click="emit('editar')">
      <q-tooltip>Editar unidade</q-tooltip>
    </q-btn>
    <q-btn
      flat
      round
      size="sm"
      color="grey-7"
      :icon="unidade.inativo ? 'play_arrow' : 'pause'"
      @click="emit('toggle-inativo')"
    >
      <q-tooltip>{{ unidade.inativo ? 'Ativar' : 'Inativar' }} unidade</q-tooltip>
    </q-btn>
    <q-btn flat round size="sm" color="grey-7" icon="delete" @click="emit('excluir')">
      <q-tooltip>Excluir unidade</q-tooltip>
    </q-btn>
    <q-btn flat round size="sm" color="primary" icon="add" @click="emit('adicionar-setor')">
      <q-tooltip>Adicionar setor</q-tooltip>
    </q-btn>
  </div>
</template>
