import Vue from 'vue'
import Vuex from 'vuex'

import snackbar from './snackbar'
import filtro from './filtro'

Vue.use(Vuex)

export default new Vuex.Store({
  modules: {
    snackbar,
    filtro
  }
})
