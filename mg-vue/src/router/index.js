import Vue from 'vue'
import Router from 'vue-router'
import Hello from '@/components/Hello'
import BootstrapTest from '@/components/BootstrapTest'
import Login from '@/components/Login'
import axios from 'axios'

Vue.use(Router)

const routes = [
  {
    path: '/',
    name: 'Hello',
    component: Hello,
    meta: { requerAutenticacao: true }
  },
  {
    path: '/Login',
    name: 'Login',
    component: Login
  },
  {
    path: '/bootstrap-test',
    name: 'BootstrapTest',
    component: BootstrapTest,
    meta: { requerAutenticacao: true }
  }
]

const router = new Router({
  routes: routes
})

router.beforeEach((to, from, next) => {
  if (to.matched.some(m => m.meta.requerAutenticacao)) {
    axios.get('http://api.notmig01.teste/api/auth/check').then(response => {
      if (!response.data.autenticado) {
        return next({ path: '/Login' })
      }
    }).catch(error => {
      console.log(error.response)
    })
  }
  return next()
})

export default router
