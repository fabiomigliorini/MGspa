<template>
  <mg-layout>

    <template slot="title">
      Início
    </template>

    <div slot="content" class="layout-padding">
      <div class="row wrap">
        <div class="text-center col-md-1 col-xs-3 col-sm-2" v-for="aplicativo in aplicativos">
          <q-btn flat color="primary" :icon="aplicativo.icon" @click="$router.push(aplicativo.path)" size="2rem" style="min-height:0"/>
          <br>
          <small @click="$router.push(aplicativo.path)" class="text-primary" style="cursor:pointer">
            {{aplicativo.title}}
          </small>
        </div>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../layouts/MgLayout'

export default {
  name: 'index',
  components: {
    MgLayout
  },

  data () {
    return {
      left: false
    }
  },

  computed: {
    aplicativos: {
      get () {
        return this.$store.state.aplicativos.aplicativos
      }
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
