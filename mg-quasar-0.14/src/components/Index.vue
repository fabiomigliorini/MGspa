<template>
  <mg-layout>

    <template slot="title">
      Início
    </template>

    <div slot="content" class="layout-padding">

      <div class="row wrap">

        <div class="text-center col-md-1 col-xs-3 col-sm-2" v-for="aplicativo in aplicativos">
          <router-link :to="{ path: aplicativo.path, params: {} }">
            <q-icon :name="aplicativo.icon" style="font-size:3em" />
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
import { QIcon } from 'quasar'
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
