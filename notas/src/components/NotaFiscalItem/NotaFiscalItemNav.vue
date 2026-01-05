<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const props = defineProps({
  codnotafiscal: {
    type: [Number, String],
    required: true,
  },
  codnotafiscalitem: {
    type: [Number, String],
    required: true,
  },
})

const route = useRoute()
const router = useRouter()

const currentSection = computed(() => {
  const path = route.path
  if (path.includes('/detalhes')) return 'detalhes'
  if (path.includes('/impostos-rural')) return 'impostos-rural'
  if (path.includes('/impostos-reforma')) return 'impostos-reforma'
  if (path.includes('/impostos')) return 'impostos'
  return 'detalhes'
})

const tabs = [
  {
    name: 'detalhes',
    label: 'Detalhes',
    icon: 'shopping_cart',
    route: 'nota-fiscal-item-detalhes',
  },
  {
    name: 'impostos',
    label: 'Impostos',
    icon: 'calculate',
    route: 'nota-fiscal-item-impostos',
  },
  {
    name: 'impostos-rural',
    label: 'Impostos Rural',
    icon: 'agriculture',
    route: 'nota-fiscal-item-impostos-rural',
  },
  {
    name: 'impostos-reforma',
    label: 'Reforma Tributária',
    icon: 'gavel',
    route: 'nota-fiscal-item-impostos-reforma',
  },
]

const navigateTo = (tab) => {
  router.push({
    name: tab.route,
    params: {
      codnotafiscal: props.codnotafiscal,
      codnotafiscalitem: props.codnotafiscalitem,
    },
  })
}
</script>

<template>
  <q-tabs v-model="currentSection" class="text-grey-7 bg-grey-2" active-color="white" active-bg-color="primary"
    indicator-color="transparent" align="left" inline-label no-caps>
    <q-tab v-for="tab in tabs" :key="tab.name" :name="tab.name" :icon="tab.icon" :label="tab.label"
      @click="navigateTo(tab)" />
  </q-tabs>
</template>
