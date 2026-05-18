import { store } from 'quasar/wrappers'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'

export default store(() => {
  const pinia = createPinia()
  pinia.use(piniaPluginPersistedstate)

  return pinia
})

export const router = createRouter({
  history: createWebHistory('/'),
  linkActiveClass: 'active',
  routes: [{ path: '/' }, { path: '/login' }],
})

export { useAuthStore } from './auth'
