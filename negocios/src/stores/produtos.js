import { defineStore } from 'pinia'

export const produtosStore = defineStore('counter', {
  state: () => ({
    counter: 0,
    listagemPdv: []
  }),

  getters: {
    doubleCount (state) {
      return state.counter * 2
    }
  },

  actions: {
    increment () {
      this.counter++
    }
  },
  persist: true
})
