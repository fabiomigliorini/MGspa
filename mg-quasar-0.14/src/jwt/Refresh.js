import router from 'vue-router'

export default {
  handle: function (response) {
    let resource = response
    console.log(JSON.stringify(resource))
    return window.axios.get('/auth/refresh').then(function (response) {
      localStorage.setItem('auth.token', response.data)
      let method = resource.config.method.toLowerCase()
      return window.axios[method](resource.config.url, resource.config.params)
    }).catch(this.redirect())
  },

  redirect: function () {
    router.push('/login')
  }
}
