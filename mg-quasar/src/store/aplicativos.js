export default {

  namespaced: true,

  state: {
    listagem: [
      {
        icon: 'home',
        title: 'Início',
        path: '/'
      },
      {
        icon: 'label_outline',
        title: 'Marcas',
        path: '/marca'
      }
    ]
  },

  // this.$store.getters['aplicativos/listagem']
  getters: {
    listagem: state => {
      return state.listagem
    }
  },

  // this.$store.commit('aplicativos/listagem', filtro)
  mutations: {

    listagem (state, payload) {
      state.listagem = payload
    }

  }

}
