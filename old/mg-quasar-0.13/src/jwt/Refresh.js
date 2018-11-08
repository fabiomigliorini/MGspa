// import router from 'vue-router'

export default {
  handle: function (response) {
    var retry = function () {
      console.log(response.data)
      localStorage.setItem('auth.token', response.data.token)

      return this.retry(response.request).then(function () {
        return response
      })
    }.bind(this)

    // Refresh JWT, then retry previous request if successful, and catch any errors (auth fail of some kind)
    return window.axios.get('/auth/refresh')
      .then(retry)
      .catch(this.redirect)
  },

  retry: function (request) {
    var method = request.method.toLowerCase()
    return window.axios[method](request.url, request.params)
  }

  // redirect: function () {
  //   router.push('/login/')
  // }
}
