<template>
  <q-card v-if="!plain" flat bordered class="text-center text-grey-6 q-py-xl">
    <q-icon :name="icon" size="40px" class="text-grey-4 q-mb-sm" />
    <div class="q-px-md"><slot>Nenhum registro encontrado.</slot></div>
  </q-card>
  <div v-else class="text-center text-grey-6 q-py-xl">
    <q-icon :name="icon" size="40px" class="text-grey-4 q-mb-sm" />
    <div class="q-px-md"><slot>Nenhum registro encontrado.</slot></div>
  </div>
</template>

<script setup>
// Estado vazio padrão de todos os apps. `plain` remove o card (use dentro de um q-card já existente).
defineProps({
  icon: { type: String, default: 'inbox' },
  plain: { type: Boolean, default: false },
})
</script>
