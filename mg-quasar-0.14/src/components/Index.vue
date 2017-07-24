<template>
  <mg-layout>

    <template slot="title">
      Início
    </template>

    <div slot="content" class="layout-padding">

      <div class="row wrap">

        <q-icon name="menu" />
        <div class="text-center" v-for="aplicativo in aplicativos" :key="aplicativo.title">
          <router-link :icon="aplicativo.icon" :to="{ path: aplicativo.path }">
            <small>
              {{aplicativo.title}}
            </small>
          </router-link>
        </div>

      </div>

    </div>

  </mg-layout>
</template>

<script>
import QIcon from 'quasar'
import MgLayout from './layouts/MgLayout'

export default {

  components: {
    QIcon,
    MgLayout
  },

  data () {
    return {
    }
  },

  computed: {
    aplicativos: function () {
      const aplicativos = this.$store.getters['aplicativos/listagem']
      return aplicativos
    }
  },

  methods: {
  },

  mounted () {
  }

}
</script>

<style>
</style>
