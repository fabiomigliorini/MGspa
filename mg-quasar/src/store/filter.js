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
    }
  },

  // this.$store.getters['filter/marca']
  getters: {
    marca: state => {
      return state.marca
    }
  },

  // this.$store.commit('filter/marca', filter)
  mutations: {

    marca (state, payload) {
      state.marca = payload
    }

  }

}
