export default {

  namespaced: true,

  state: {
    marca: {
      marca: null,
      sort: 'abcposicao',
      inativo: 1,
      sobrando: false,
      faltando: false,
      abccategoria: {
        min: 0,
        max: 3
      }
    },
    usuario: {
      usuario: null,
      inativo: 1
    }
  },

  // this.$store.getters['filter/marca']
  getters: {
    marca: state => {
      return state.marca
    },
    usuario: state => {
      return state.usuario
    }
  },

  // this.$store.commit('filter/marca', filter)
  mutations: {

    marca (state, payload) {
      state.marca = payload
    },
    usuario (state, payload) {
      state.usuario = payload
    }
  }

}
