import router from '../router'

export default {
  handle: function (response, path) {
    // let resource = response
    // console.log(JSON.stringify(resource))
    return window.axios.get('/auth/refresh').then(function (response) {
      localStorage.setItem('auth.token', response.data)
      return router.replace(path)
      // let method = resource.config.method.toLowerCase()
      // return window.axios[method](resource.config.url, resource.config.params)
    }).catch(function () {
      return router.push('/login/')
    })
  }
}
