import Vue from 'vue'
import Vuex from 'vuex'

import example from './module-example'
import aplicativos from './aplicativos'

Vue.use(Vuex)

const store = new Vuex.Store({
  modules: {
    example,
    aplicativos
  }
})

export default store
