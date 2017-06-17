import Vue from 'vue'
import Router from 'vue-router'
import Hello from '@/components/Hello'
import BootstrapTest from '@/components/BootstrapTest'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'Hello',
      component: Hello
    },
    {
      path: '/bootstrap-test',
      name: 'BootstrapTest',
      component: BootstrapTest
    }
  ]
})
