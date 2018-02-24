
export default [
  {
    path: '/',
    component: () => import('components/Index')
  },

  { // Always leave this as last one
    path: '*',
    component: () => import('pages/404')
  },
  {
    path: '/login',
    component: () => import('components/Login')
  }
]
