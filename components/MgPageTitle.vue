<script setup>
import { computed, useSlots } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  appName: { type: String, required: true },
  homeRoute: { type: [String, Object], default: null },
  title: { type: String, default: null },
})

const route = useRoute()
const slots = useSlots()

const computedTitle = computed(() => props.title || route.meta?.title || props.appName)
const hasSlot = computed(() => !!slots.default)
</script>

<template>
  <q-toolbar-title class="q-ml-sm">
    <component
      :is="homeRoute ? 'router-link' : 'span'"
      v-bind="homeRoute ? { to: homeRoute } : {}"
      class="text-white"
      style="text-decoration: none"
    >
      <q-avatar size="36px" class="q-mr-sm" :class="{ 'cursor-pointer': !!homeRoute }">
        <img src="/MGPapelariaQuadrado.svg" alt="MG Papelaria" />
        <q-tooltip v-if="homeRoute">Início</q-tooltip>
      </q-avatar>
    </component>
    <slot v-if="hasSlot" />
    <template v-else>{{ computedTitle }}</template>
  </q-toolbar-title>
</template>
