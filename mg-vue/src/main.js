// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './App'
import router from './router'
import axios from 'axios'
import VueAxios from 'vue-axios'

// window._ = require('lodash')

Vue.config.productionTip = false

Vue.use(VueAxios, axios)
axios.defaults.headers.common = {
  'X-Requested-With': 'XMLHttpRequest'
}

axios.interceptors.response.use(config => {
  const AUTH_TOKEN = localStorage.getItem('auth.token')
  console.log(config)
  if (AUTH_TOKEN) {
    config.headers.push({
      'Authorization': `Bearer ${AUTH_TOKEN}`
    })
  }
  return config
}, error => {
  return error
})

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  template: '<App/>',
  components: { App }
})
