
import Vue from 'vue'
// import { HTTP } from 'http'
import axios from 'axios'
import App from './App'
import router from './router'

Vue.config.productionTip = false

window._ = require('lodash')
// window.axios = require('axios')
// axios.defaults.headers.common = {
//   'X-Requested-With': 'XMLHttpRequest'
// }

axios.interceptors.request.use(function (config) {
  const AUTH_TOKEN = localStorage.getItem('auth.token')

  if (AUTH_TOKEN) {
    config.headers.common['Authorization'] = `Bearer ${AUTH_TOKEN}`
  }

  return config
}, function (error) {
  return Promise.reject(error)
})

// axios.interceptors.response.use(config => {
//   const AUTH_TOKEN = localStorage.getItem('auth.token')
//   console.log('token => ' + AUTH_TOKEN)
//   if (AUTH_TOKEN) {
//     config.config.headers['Authorization'] = 'Bearer ' + AUTH_TOKEN
//     console.log('anexado => ' + JSON.stringify(config.config.headers))
//   }
//   return config
// }, error => {
//   return error
// })

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  template: '<App/>',
  components: {
    App
  }
})
