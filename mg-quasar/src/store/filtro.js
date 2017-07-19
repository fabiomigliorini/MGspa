export default {

  namespaced: true,

  state: {
    marca: {
      marca: null,
      sort: 'abcposicao',
      inativo: 1,
      sobrando: false,
      faltando: false,
      abccategoriaB: {
        min: 0,
        max: 3
      }
    }
  },

  // this.$store.getters['filtro/marca']
  getters: {
    marca: state => {
      return state.marca
    }
  },

  // this.$store.commit('filtro/marca', filtro)
  mutations: {

    marca (state, payload) {
      state.marca = payload
    }

  }

}
