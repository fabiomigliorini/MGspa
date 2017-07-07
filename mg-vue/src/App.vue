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
      <transition name="fade">
        <router-view></router-view>
      </transition>
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
.btn--floating {
  z-index: 4;
}
.speed-dial {
  z-index: 5;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity .5s
}
.fade-enter, .fade-leave-to /* .fade-leave-active in <2.1.8 */ {
  opacity: 0
}

/* Enter and leave animations can use different */
/* durations and timing functions.              */
.slide-fade-enter-active {
  transition: all .3s ease;
}
/*.slide-fade-leave-active {
transition: all .8s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}*/

.slide-fade-enter, .slide-fade-leave-to
/* .slide-fade-leave-active for <2.1.8 */ {
  transform: translateX(15px);
  opacity: 0;
}

/*.toolbar--absolute+main, .toolbar--fixed+main {
  padding-top: 50px;
}*/
</style>
