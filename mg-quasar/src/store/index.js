import Vue from 'vue'
import Vuex from 'vuex'

import aplicativos from './aplicativos'
import filtro from './filtro'

Vue.use(Vuex)

export default new Vuex.Store({
  modules: {
    aplicativos,
    filtro
  }
})
