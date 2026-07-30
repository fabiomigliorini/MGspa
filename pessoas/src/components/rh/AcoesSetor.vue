<script setup>
defineProps({
  setor: { type: Object, required: true },
})

const emit = defineEmits(['editar', 'toggle-inativo', 'excluir', 'adicionar-colaboradores'])
</script>

<template>
  <div class="row items-center no-wrap" v-if="setor.codsetor">
    <q-btn flat round size="sm" color="grey-7" icon="edit" @click="emit('editar')">
      <q-tooltip>Editar setor</q-tooltip>
    </q-btn>
    <q-btn
      flat
      round
      size="sm"
      color="grey-7"
      :icon="setor.inativo ? 'play_arrow' : 'pause'"
      @click="emit('toggle-inativo')"
    >
      <q-tooltip>{{ setor.inativo ? 'Ativar' : 'Inativar' }} setor</q-tooltip>
    </q-btn>
    <q-btn flat round size="sm" color="grey-7" icon="delete" @click="emit('excluir')">
      <q-tooltip>Excluir setor</q-tooltip>
    </q-btn>
    <q-btn
      flat
      round
      size="sm"
      color="primary"
      icon="person_add"
      @click="emit('adicionar-colaboradores')"
    >
      <q-tooltip>Adicionar colaboradores</q-tooltip>
    </q-btn>
  </div>
</template>
