
export default [
  {
    path: '/',
    component: () => import('components/Index')
  },
  {
    path: '/permissao',
    component: () => import('components/views/permissao/Index')
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
