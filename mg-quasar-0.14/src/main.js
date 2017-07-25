// === DEFAULT / CUSTOM STYLE ===
// WARNING! always comment out ONE of the two require() calls below.
// 1. use next line to activate CUSTOM STYLE (./src/themes)
// require(`./themes/app.${__THEME}.styl`)
// 2. or, use next line to activate DEFAULT QUASAR STYLE
require(`quasar/dist/quasar.${__THEME}.css`)
// ==============================

// Uncomment the following lines if you need IE11/Edge support
// require(`quasar/dist/quasar.ie`)
// require(`quasar/dist/quasar.ie.${__THEME}.css`)

import Vue from 'vue'
import Quasar from 'quasar'
import router from './router'
import store from './store'
// import refresh from 'jwt/Refresh'

Vue.config.productionTip = false
Vue.use(Quasar) // Install Quasar Framework

if (__THEME === 'mat') {
  require('quasar-extras/roboto-font')
}
import 'quasar-extras/material-icons'
// import 'quasar-extras/ionicons'
// import 'quasar-extras/fontawesome'
// import 'quasar-extras/animate'

// Moment js
import moment from 'moment'
moment.locale('pt-BR')
Vue.prototype.moment = moment

// Moment js
import numeral from 'numeral'
numeral.register('locale', 'pt-BR', {
  delimiters: {
    thousands: '.',
    decimal: ','
  },
  abbreviations: {
    thousand: 'K',
    million: 'M',
    billion: 'B',
    trillion: 'T'
  },
  ordinal: function (number) {
    return 'º'
  },
  currency: {
    symbol: 'R$'
  }
})
numeral.locale('pt-BR')
numeral.defaultFormat('0,0.00')
Vue.prototype.numeral = numeral

// Axios
import Axios from 'axios'
window.axios = Axios.create({
  baseURL: process.env.API_BASE_URL,
  'X-Requested-With': 'XMLHttpRequest'
})
window.axios.interceptors.request.use(function (config) {
  const AUTH_TOKEN = localStorage.getItem('auth.token')
  if (AUTH_TOKEN) {
    config.headers.common['Authorization'] = `Bearer ${AUTH_TOKEN}`
  }
  // Quasar.Loading.show()
  return config
}, function (error) {
  return Promise.reject(error)
})

window.axios.interceptors.response.use((response) => {
  // Quasar.Loading.hide()
  return response
}, function (error) {
  // Quasar.Loading.hide()
  let mensagem = 'Erro ao acessar API'
  if (error.response) {
    if (error.response.status) {
      const originalRequest = error.config
      if (error.response.status === 401 && !originalRequest._retry) {
        // refresh.handle()
        return router.push('/login/')
      }
      mensagem += ' - ' + error.response.status
      if (error.response.data.mensagem) {
        mensagem += ' - ' + error.response.data.mensagem
      }
    }
  }
  Quasar.Toast.create.negative({html: mensagem})
  return Promise.reject(error)
})

Quasar.start(() => {
  /* eslint-disable no-new */
  new Vue({
    el: '#q-app',
    router,
    store,
    render: h => h(require('./App'))
  })
})
