import { defineStore } from 'pinia'

export const useSelectTipoClienteStore = defineStore('selectTipoCliente', {
  state: () => ({
    // Tipos de cliente (estático)
    tiposCliente: [
      { label: 'PFC', value: 'PFC', descricao: 'Pessoa Física Contribuinte' },
      { label: 'PFN', value: 'PFN', descricao: 'Pessoa Física Não Contribuinte' },
      { label: 'PJC', value: 'PJC', descricao: 'Pessoa Jurídica Contribuinte' },
      { label: 'PJN', value: 'PJN', descricao: 'Pessoa Jurídica Não Contribuinte' },
    ],
  }),

  getters: {},

  actions: {},
})
