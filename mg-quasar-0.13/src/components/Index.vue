<template>
  <mg-layout>

    <template slot="title">
      Iníciossss
    </template>

    <div slot="content" class="layout-padding">

      <div class="row wrap">

        <div class="text-center" v-for="aplicativo in aplicativos">
          <router-link :to="{ path: aplicativo.path, params: {} }">
            <i class="icone-app">{{aplicativo.icon}}</i>
            <br>
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

import MgLayout from './layouts/MgLayout'

export default {

  components: {
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
