<template>
  <div id="app">
    <v-snackbar
      v-model="snackbar.mostrar"
      :info="snackbar.info"
      :warning="snackbar.warning"
      :error="snackbar.error"
      :primary="snackbar.primary"
      :secondary="snackbar.secondary"
      :success="snackbar.success"
      > {{ snackbar.mensagem }} </v-snackbar>
    <router-view></router-view>
  </div>
</template>

<script>
export default {
  name: 'app',
  data () {
    return {
    }
  },
  computed: {
    snackbar () {
      return this.$store.getters['snackbar/dados']
    }
  }
}
</script>

<style>
#app {
  height: 100%
}
</style>
