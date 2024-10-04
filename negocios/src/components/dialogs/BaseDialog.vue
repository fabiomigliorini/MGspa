<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
})

const model = defineModel();
</script>

<template>
  <q-dialog v-model="model">
    <q-card style="min-width: 350px">
      <q-card-section class="q-pb-none q-pt-sm">
        <div class="text-h6">{{ title }}</div>
      </q-card-section>

      <q-card-section clas="pa-md">
        <slot />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
