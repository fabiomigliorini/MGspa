<script setup>
defineProps({
  title: { type: String, required: true },
  first: { type: Boolean, default: false },
})
</script>

<template>
  <!-- <q-separator v-if="!first" class="q-my-md" /> -->
  <div class="text-caption text-grey-7 q-mt-md q-mb-sm">{{ title }}</div>
  <slot />
</template>
