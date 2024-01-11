<template>
  <mg-layout drawer>
  <h1>TESTE</h1>
  <template #tituloPagina>
    Notas Fiscais
  </template>

  <template #content>
  <h1>Notas Fiscais</h1>
</template>
</mg-layout>
</template>

<script>
import { defineComponent, defineAsyncComponent } from 'vue'

export default defineComponent({
  name: 'IndexPage',
  components: {
    MgLayout: defineAsyncComponent(() => import('layouts/MgLayout.vue'))
  }
})
</script>
