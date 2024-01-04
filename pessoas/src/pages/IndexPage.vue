<template>
  <MGLayout>
    <template #content>
      <q-page class="flex flex-center">
        <img alt="Quasar logo" src="~assets/quasar-logo-vertical.svg" style="width: 200px; height: 200px">
      </q-page>
    </template>
  </MGLayout>
</template>

<script>
import { defineComponent, defineAsyncComponent, onMounted } from 'vue'
import { useRouter } from 'vue-router'

export default defineComponent({
  name: 'IndexPage',
  components: {
    MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue'))
  },

  setup() {
    const router = useRouter()

    onMounted(() => {
      router.replace('/', null)
    })


  }

})
</script>
