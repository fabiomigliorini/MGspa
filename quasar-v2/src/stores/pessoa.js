import { defineStore } from 'pinia';
import { api } from 'boot/axios';
import { useQuasar } from 'quasar'

export const pessoaStore = defineStore('counter', {
  state: () => ({
    counter: 0,
    item: {}
  }),

  getters: {
    doubleCount(state) {
      return state.counter * 2
    },
  },

  actions: {
    increment() {
      this.counter++
    },
    async get(codpessoa) {
      const { data } = await api.get('v1/pessoa/' + codpessoa)
      this.item = data.data
      return data
    },
    async telefoneParaCima(codpessoa, codpessoatelefone) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/telefone/' + codpessoatelefone + '/cima')
      return ret;
    },
    async telefoneParaBaixo(codpessoa, codpessoatelefone) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/telefone/' + codpessoatelefone + '/baixo')
      return ret;
    },
    async telefoneAtivar(codpessoa, codpessoatelefone) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/telefone/' + codpessoatelefone + '/inativo')
      return ret;
    },
    async telefoneInativar(codpessoa, codpessoatelefone) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/telefone/' + codpessoatelefone + '/inativo')
      return ret;
    },

    async telefoneNovo(codpessoa, model) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/telefone', model)
      return ret;
    },

    async telefoneAlterar(codpessoa, codpessoatelefone, model) {
      const ret = await api.put('v1/pessoa/' + codpessoa + '/telefone/' + codpessoatelefone, model)
      const i = this.item.PessoaTelefoneS.findIndex(item => item.codpessoatelefone === codpessoatelefone)
      this.item.PessoaTelefoneS[i] = ret.data.data
      return ret;
    },

    async telefoneExcluir(codpessoa, codpessoatelefone) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/telefone/' + codpessoatelefone)
      return ret;
    },

    async telefoneVerificar(codpessoa, codpessoatelefone) {
      const ret = await api.get('v1/pessoa/' + codpessoa + '/telefone/' + codpessoatelefone + '/verificar')
      return ret;
    },

    async telefoneConfirmaVerificacao(codpessoa, codpessoatelefone, codverificacao) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/telefone/' + codpessoatelefone + '/verificar', {codverificacao: codverificacao})
      return ret;
    },
  }
})

