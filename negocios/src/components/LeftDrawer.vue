<template>
  <q-list>
    <q-item-label
      header
    >
    Essential Links
    </q-item-label>
    teste
    <q-item
      clickable
      tag="a"
      target="_blank"
      :href="link"
    >
      <q-item-section avatar>
        <q-icon name="delete" />
      </q-item-section>

      <q-item-section>
        <q-item-label>teste</q-item-label>
        <q-item-label caption>caption</q-item-label>
      </q-item-section>
    </q-item>


    <!-- <EssentialLink
      v-for="link in essentialLinks"
      :key="link.title"
      v-bind="link"
    /> -->
  </q-list>

</template>

<script>
import { defineComponent } from 'vue'
import { mainStore } from "stores/main";

export default defineComponent({
  name: 'LeftDrawer',
  props: {
    title: {
      type: String,
      required: true
    },

    caption: {
      type: String,
      default: ''
    },

    link: {
      type: String,
      default: '#'
    },

    icon: {
      type: String,
      default: ''
    }
  },
  setup () {
    const store = mainStore();
    return {
      store
    };
  }
})
</script>
