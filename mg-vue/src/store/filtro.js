export default {

  namespaced: true,

  state: {
    marca: {
      marca: null
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
