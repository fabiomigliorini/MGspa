import { defineStore } from 'pinia'

export const saldoFiltroStore = defineStore('saldoFiltro', {
  state: () => ({
    anoSelecionado: null,
    mesSelecionado: null,
  }),
  persist: true // se estiver usando pinia-plugin-persistedstate
})
