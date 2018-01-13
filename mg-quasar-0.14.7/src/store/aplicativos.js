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
      },
      {
        icon: 'supervisor_account',
        title: 'Usuários',
        path: '/usuario'
      },
      {
        icon: 'lock_open',
        title: 'Permissões',
        path: '/permissao'
      },
      {
        icon: 'multiline_chart',
        title: 'Vendas',
        path: '/estoque-estatistica/100'
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
