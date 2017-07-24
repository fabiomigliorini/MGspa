import Vue from 'vue'
import Vuex from 'vuex'

import aplicativos from './aplicativos'
import filter from './filter'

Vue.use(Vuex)

export default new Vuex.Store({
  modules: {
    aplicativos,
    filter
  }
})
