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
      const i = this.item.PessoaTelefoneS.findIndex(item => item.codpessoatelefone === codpessoatelefone)
      this.item.PessoaTelefoneS[i] = ret.data.data
      return ret;
    },

    // PESSOA EMAIL

    async emailParaCima(codpessoa, codpessoatelefone) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email/' + codpessoatelefone + '/cima')
      return ret;
    },

    async emailParaBaixo(codpessoa, codpessoatelefone) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email/' + codpessoatelefone + '/baixo')
      return ret;
    },

    async emailNovo(codpessoa, modelnovoemail) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email', modelnovoemail)
      this.item.PessoaEmailS = ret.data.data
      return ret;
    },

    async emailExcluir(codpessoa, codpessoatelefone) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/email/' + codpessoatelefone)
      return ret;
    },
    
    async emailSalvar(codpessoa, codpessoatelefone , modelalteraremail) {
      const ret = await api.put('v1/pessoa/' + codpessoa + '/email/' + codpessoatelefone, modelalteraremail) 
      const i = this.item.PessoaEmailS.findIndex(item => item.codpessoatelefone === codpessoatelefone)
      this.item.PessoaEmailS[i] = ret.data.data
      return ret;
    },

    async emailInativar(codpessoa, codpessoatelefone) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email/' + codpessoatelefone + '/inativo')
      const i = this.item.PessoaEmailS.findIndex(item => item.codpessoatelefone === codpessoatelefone)
      this.item.PessoaEmailS[i] = ret.data.data
      return ret;
    },

    async emailAtivar(codpessoa, codpessoatelefone) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/email/' + codpessoatelefone + '/inativo')
      const i = this.item.PessoaEmailS.findIndex(item => item.codpessoatelefone === codpessoatelefone)
      this.item.PessoaEmailS[i] = ret.data.data
      return ret;
    },

    async emailVerificar(codpessoa, codpessoatelefone) {
      const ret = await api.get('v1/pessoa/' + codpessoa + '/email/' + codpessoatelefone + '/verificar')
      return ret;
    },

    async emailConfirmaVerificacao(codpessoa, codpessoatelefone, codverificacao) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email/' + codpessoatelefone + '/verificar', {codverificacao: codverificacao})
      const i = this.item.PessoaEmailS.findIndex(item => item.codpessoatelefone === codpessoatelefone)
        this.item.PessoaEmailS[i] = ret.data.data
      return ret
   },
  }
})

