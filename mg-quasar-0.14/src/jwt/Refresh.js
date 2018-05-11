import router from '../router'

export default {
  handle: function (response) {
    return window.axios.get('/auth/refresh').then(function (response) {
      if (!response.data.mensagem) {
        localStorage.setItem('auth.token', response.data)
        window.location.reload()
        // let method = resource.config.method.toLowerCase()
        // return window.axios[method](resource.config.url, resource.config.params)
      }
      else {
        router.push('/login/')
      }
    }).catch(function () {
      return router.push('/login/')
    })
  }
}
