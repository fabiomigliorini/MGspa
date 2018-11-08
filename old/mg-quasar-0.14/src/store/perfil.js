export default {

  namespaced: true,

  state: {
    usuario: {
      usuario: `${localStorage.getItem('auth.usuario.usuario')}`,
      avatar: `${localStorage.getItem('auth.usuario.avatar')}`,
      codusuario: `${localStorage.getItem('auth.usuario.codusuario')}`
    }
  },

  getters: {
    usuario: state => {
      return state.usuario
    }
  },

  mutations: {
    usuario (state, payload) {
      state.usuario = payload
    }
  }
}
