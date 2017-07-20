export default {

  namespaced: true,

  state: {
    marca: {
      marca: null,
      sort: 'abcposicao',
      inativo: 1
    },
    grupousuario: {
      inativo: 1
    }
  },

  // this.$store.getters['filtro/marca']
  getters: {
    marca: state => {
      return state.marca
    },
    grupousuario: state => {
      return state.grupousuario
    }
  },

  // this.$store.commit('filtro/marca', filtro)
  mutations: {

    marca (state, payload) {
      state.marca = payload
    },
    grupousuario (state, payload) {
      state.grupousuario = payload
    }

  }

}
