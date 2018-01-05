import Vue from 'vue'
import Vuex from 'vuex'

import aplicativos from './aplicativos'
import filter from './filter'
import perfil from './perfil'

Vue.use(Vuex)

export default new Vuex.Store({
  modules: {
    aplicativos,
    filter,
    perfil
  }
})
