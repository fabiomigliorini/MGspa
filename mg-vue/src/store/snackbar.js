export default {

  namespaced: true,

  state: {
    mensagem: '---',
    error: false,
    mostrar: false
  },

  // this.$store.getters['snackbar/dados']
  getters: {
    dados: state => {
      return state
    }
  },

  // this.$store.commit('snackbar/error', 'mensagem')
  mutations: {

    info (state, mensagem) {
      state.mensagem = mensagem
      state.info = true
      state.warning = false
      state.error = false
      state.primary = false
      state.secondary = false
      state.success = false
      state.mostrar = true
    },

    warning (state, mensagem) {
      state.mensagem = mensagem
      state.info = false
      state.warning = true
      state.error = false
      state.primary = false
      state.secondary = false
      state.success = false
      state.mostrar = true
    },

    error (state, mensagem) {
      state.mensagem = mensagem
      state.info = false
      state.warning = false
      state.error = true
      state.primary = false
      state.secondary = false
      state.success = false
      state.mostrar = true
    },

    primary (state, mensagem) {
      state.mensagem = mensagem
      state.info = false
      state.warning = false
      state.error = false
      state.primary = true
      state.secondary = false
      state.success = false
      state.mostrar = true
    },

    secondary (state, mensagem) {
      state.mensagem = mensagem
      state.info = false
      state.warning = false
      state.error = false
      state.primary = false
      state.secondary = true
      state.success = false
      state.mostrar = true
    },

    success (state, mensagem) {
      state.mensagem = mensagem
      state.info = false
      state.warning = false
      state.error = false
      state.primary = false
      state.secondary = false
      state.success = true
      state.mostrar = true
    }
  }

}
