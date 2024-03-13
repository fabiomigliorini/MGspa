import { defineStore } from 'pinia';
import { api } from 'boot/axios';
import { ref } from 'vue';


export const pessoaStore = defineStore('pessoa', {
  persist:{
    paths:[
      'arrPessoas',
      'filtroPesquisa',
      // 'item',
      // 'counter',
    ]
  },

  state: () => ({
    counter: 0,
    item: {},
    arrPessoas: [],
    filtroPesquisa: {
      codpessoa: null,
      pessoa: null,
      cnpj: null,
      email: null,
      fone: null,
      codgrupoeconomico: null,
      codcidade: null,
      inativo: 'A',
      codformapagamento: null,
      codgrupocliente: null,
      page: 1,
      per_page: 108
    },
    desativaScrollnoFiltro: ref(false)
  }),

  actions: {
    async get(codpessoa) {
      this.item = null;
      const { data } = await api.get('v1/pessoa/' + codpessoa)
      this.item = data.data
      return data
    },

    async criarPessoa(model) {
      const ret = await api.post('v1/pessoa', model)
      return ret;
    },

    async removePessoa(codpessoa) {
      const ret = await api.delete('v1/pessoa/' + codpessoa)
      return ret;
    },

    async inativarPessoa(codpessoa) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/inativo')
      return ret;
    },

    async ativarPessoa(codpessoa) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/inativo')
      return ret;
    },

    async getEndereco(codpessoa, codpessoaendereco) {
      const { data } = await api.get('v1/pessoa/' + codpessoa + '/endereco/' + codpessoaendereco)
      this.item.PessoaEnderecoS = data.data
      return data
    },

    // PESSOA TELEFONE

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
      const ret = await api.post('v1/pessoa/' + codpessoa + '/telefone/' + codpessoatelefone + '/verificar', { codverificacao: codverificacao })
      const i = this.item.PessoaTelefoneS.findIndex(item => item.codpessoatelefone === codpessoatelefone)
      this.item.PessoaTelefoneS[i] = ret.data.data
      return ret;
    },

    // PESSOA EMAIL

    async emailParaCima(codpessoa, codpessoaemail) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email/' + codpessoaemail + '/cima')
      return ret;
    },

    async emailParaBaixo(codpessoa, codpessoaemail) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email/' + codpessoaemail + '/baixo')
      return ret;
    },

    async emailNovo(codpessoa, modelnovoemail) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email', modelnovoemail)
      this.item.PessoaEmailS = ret.data.data
      return ret;
    },

    async emailExcluir(codpessoa, codpessoaemail) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/email/' + codpessoaemail)
      return ret;
    },

    async emailSalvar(codpessoa, codpessoaemail, modelalteraremail) {
      const ret = await api.put('v1/pessoa/' + codpessoa + '/email/' + codpessoaemail, modelalteraremail)
      const i = this.item.PessoaEmailS.findIndex(item => item.codpessoaemail === codpessoaemail)
      this.item.PessoaEmailS[i] = ret.data.data
      return ret;
    },

    async emailInativar(codpessoa, codpessoaemail) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email/' + codpessoaemail + '/inativo')
      const i = this.item.PessoaEmailS.findIndex(item => item.codpessoaemail === codpessoaemail)
      this.item.PessoaEmailS[i] = ret.data.data
      return ret;
    },

    async emailAtivar(codpessoa, codpessoaemail) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/email/' + codpessoaemail + '/inativo')
      const i = this.item.PessoaEmailS.findIndex(item => item.codpessoaemail === codpessoaemail)
      this.item.PessoaEmailS[i] = ret.data.data
      return ret;
    },

    async emailVerificar(codpessoa, codpessoaemail) {
      const ret = await api.get('v1/pessoa/' + codpessoa + '/email/' + codpessoaemail + '/verificar')
      return ret;
    },

    async emailConfirmaVerificacao(codpessoa, codpessoaemail, codverificacao) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/email/' + codpessoaemail + '/verificar', { codverificacao: codverificacao })
      const i = this.item.PessoaEmailS.findIndex(item => item.codpessoaemail === codpessoaemail)
      this.item.PessoaEmailS[i] = ret.data.data
      return ret
    },

    // PESSOA ENDEREÇO

    async enderecoParaCima(codpessoa, codpessoaendereco) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/endereco/' + codpessoaendereco + '/cima')
      return ret;
    },

    async enderecoParaBaixo(codpessoa, codpessoaendereco) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/endereco/' + codpessoaendereco + '/baixo')
      return ret;
    },

    async enderecoNovo(codpessoa, modelendereco) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/endereco', modelendereco)
      this.item.PessoaEnderecoS = ret.data.data.PessoaEnderecoS
      return ret;
    },

    async enderecoExcluir(codpessoa, codpessoaendereco) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/endereco/' + codpessoaendereco)
      return ret;
    },

    async enderecoSalvar(codpessoa, codpessoaendereco, modelEndereco) {
      const ret = await api.put('v1/pessoa/' + codpessoa + '/endereco/' + codpessoaendereco, modelEndereco)
      // const i = this.item.PessoaEnderecoS.findIndex(item => item.codpessoaendereco === codpessoaendereco)
      // this.item.PessoaEnderecoS[i] = ret.data.data
      return ret;
    },

    async enderecoInativar(codpessoa, codpessoaendereco) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/endereco/' + codpessoaendereco + '/inativo')
      const i = this.item.PessoaEnderecoS.findIndex(item => item.codpessoaendereco === codpessoaendereco)
      this.item.PessoaEnderecoS[i] = ret.data.data
      return ret;
    },

    async enderecoAtivar(codpessoa, codpessoaendereco) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/endereco/' + codpessoaendereco + '/inativo')
      const i = this.item.PessoaEnderecoS.findIndex(item => item.codpessoaendereco === codpessoaendereco)
      this.item.PessoaEnderecoS[i] = ret.data.data
      return ret;
    },

    async buscagrupoCliente() {
      const ret = await api.get('v1/grupocliente')
      return ret;
    },

    async consultaCidade(cidade) {
      if (typeof cidade === 'number') {
        const ret = await api.get('v1/select/cidade?codcidade=' + cidade)
        return ret;
      }
      const ret = await api.get('v1/select/cidade?cidade=' + cidade)
      return ret;
    },

    async clienteSalvar(codpessoa, modelEditarCliente) {

      if (modelEditarCliente.tipotransportador == null) {
        modelEditarCliente.tipotransportador = 0
      }
       const ret = await api.put('v1/pessoa/' + codpessoa,  modelEditarCliente);
       const i = this.arrPessoas.findIndex(item => item.codpessoa === codpessoa)
       this.arrPessoas[i] = ret.data.data

      return ret;

    },

    async buscaFormaPagamento(codformapagamento) {
      const ret = await api.get('v1/pessoa/formadepagamento?codformapagamento=' + codformapagamento)
      return ret;
    },

    async selectPagamento() {
      const ret = await api.get('v1/pessoa/formadepagamento')
      return ret;
    },

    async selectBanco(banco) {
      const ret = await api.get('v1/pessoa/conta/banco/select', {params: banco})
      return ret;
    },

    async selectFilial() {
      const ret = await api.get('v1/select/filial')
      return ret;
    },

    async buscarPessoas() {
      const ret = await api.get('v1/pessoa', {
        params: this.filtroPesquisa
      })

      if (this.filtroPesquisa.page == 1) {
        this.arrPessoas = ret.data.data;
      } else {
        this.arrPessoas.push(...ret.data.data);
      }
      return ret;
    },

    async getCobrancaHistorico(codpessoa, page) {
      const ret = await api.get('v1/pessoa/' + codpessoa + '/cobrancahistorico/', { params: page })
      return ret;
    },

    async novoHistoricoCobranca(codpessoa, historico) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/cobrancahistorico/', { historico: historico, codpessoa: codpessoa })
      return ret;
    },

    async deletaCobrancaHistorico(codpessoa, codcobrancahistorico) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/cobrancahistorico/' + codcobrancahistorico)
      return ret;
    },

    //TEM QUE POR O CODPESSOA
    async salvarHistoricoCobranca(codpessoa, codcobrancahistorico, modelCobrancaHistorico) {
      const ret = await api.put('v1/pessoa/' + codpessoa + '/cobrancahistorico/' + codcobrancahistorico, modelCobrancaHistorico)
      return ret;
    },

    async getRegistroSpc(codpessoa, page) {
      const ret = await api.get('v1/pessoa/' + codpessoa + '/registrospc/', { params: page })
      return ret;
    },

    async novoRegistroSpc(codpessoa, modelnovoRegistroSpc) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/registrospc/', modelnovoRegistroSpc)
      return ret;
    },

    async salvarEdicaoRegistro(codpessoa, codregistrospc, modelEdicaoRegistroSpc) {
      const ret = await api.put('v1/pessoa/' + codpessoa + '/registrospc/' + codregistrospc, modelEdicaoRegistroSpc)
      return ret;
    },

    async excluirRegistroSpc(codpessoa, codregistrospc) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/registrospc/' + codregistrospc)
      return ret;
    },

    async selectCertidaoEmissor() {
      const ret = await api.get('v1/select/certidao/emissor')
      return ret;
    },

    async selectCertidaoTipo() {
      const ret = await api.get('v1/select/certidao/tipo')
      return ret;
    },

    async novaCertidao(modelNovaCertidao) {
      const ret = await api.post('v1/certidao', modelNovaCertidao)
      return ret;
    },

    async salvarEdicaoCertidao(codpessoacertidao, modelCertidao) {
      const ret = await api.put('v1/certidao/' + codpessoacertidao, modelCertidao)
      return ret;
    },

    async inativarCertidao(codpessoacertidao) {
      const ret = await api.post('v1/certidao/' + codpessoacertidao + '/inativo')
      return ret;
    },

    async ativarCertidao(codpessoacertidao) {
      const ret = await api.delete('v1/certidao/' + codpessoacertidao + '/inativo')
      return ret;
    },

    async deletarCertidao(codpessoacertidao) {
      const ret = await api.delete('v1/certidao/' + codpessoacertidao)
      return ret;
    },

    async totaisNegocios(codgrupoeconomico, Negocios) {
      const ret = await api.get('v1/grupo-economico/' + codgrupoeconomico + '/totais-negocios', { params: Negocios })
      return ret;
    },

    async titulosAbertos(codgrupoeconomico, model) {
      const ret = await api.get('v1/grupo-economico/' + codgrupoeconomico + '/titulos-abertos', { params: model })
      return ret;
    },

    async nfeTerceiro(codgrupoeconomico, model) {
      const ret = await api.get('v1/grupo-economico/' + codgrupoeconomico + '/nfe-terceiro', { params: model })
      return ret;
    },

    async salvarEdicaoContaBancaria(codpessoa, codpessoaconta, modelContaBancaria) {
      const ret = await api.put('v1/pessoa/' + codpessoa + '/conta/' + codpessoaconta, modelContaBancaria)
      return ret;
    },

    async novaContaBancaria(codpessoa, modelContaBancaria) {
      const ret = await api.post('v1/pessoa/' + codpessoa + '/conta/', modelContaBancaria)
      this.item.PessoaContaS.push(ret.data.data)      
      return ret;
    },

    async excluirContaBancaria(codpessoa, codpessoaconta) {
      const ret = await api.delete('v1/pessoa/' + codpessoa + '/conta/' + codpessoaconta)
      return ret;
    },

    async contaBancariaInativar(codpessoaconta) {
      const ret = await api.post('v1/pessoa/conta/' + codpessoaconta + '/inativo')
      const i = this.item.PessoaContaS.findIndex(item => item.codpessoaconta === codpessoaconta)
      this.item.PessoaContaS[i] = ret.data.data
      return ret;
    },

    async contaBancariaAtivar(codpessoaconta) {
      const ret = await api.delete('v1/pessoa/conta/' + codpessoaconta + '/inativo')
      const i = this.item.PessoaContaS.findIndex(item => item.codpessoaconta === codpessoaconta)
      this.item.PessoaContaS[i] = ret.data.data
      return ret;
    },

    async novoCargo(modelNovoCargo) {
      const ret = await api.post('v1/cargo/', modelNovoCargo)
      return ret;
    },

    async selectCargo() {
      const ret = await api.get('v1/cargo/')
      return ret;
    },

    async selectEstado() {

      const ret = await api.get('v1/select/estado')
      return ret;
    },

    async getCargos(filtroCargo) {

      const ret = await api.get('v1/cargo', { params: filtroCargo})
      return ret;
    },

    async alterarCargo(modelCargo) {
      const ret = await api.put('v1/cargo/' + modelCargo.codcargo, modelCargo)
      return ret;
    },

    async excluirCargo(codcargo) {
      const ret = await api.delete('v1/cargo/' + codcargo)
      return ret;
    },

    async cargoInativar(codcargo) {
      const ret = await api.post('v1/cargo/' + codcargo + '/inativo')
      return ret;
    },

    async cargoAtivar(codcargo) {
      const ret = await api.delete('v1/cargo/' + codcargo + '/inativo')
      return ret;
    },

    async pessoasColaboradorCargo(codcargo) {
      const ret = await api.get('v1/cargo/pessoas-cargo/' + codcargo)
      return ret;
    },

    async programacaoFerias(ano) {
      const ret = await api.get('v1/programacao-ferias/' + ano)
      return ret;
    },

    async updateFeriasRange(codferias, dates) {
      const ret = await api.put('v1/ferias/' + codferias, dates)
      return ret;
    },

    async descobreNomePeloPix(cnpjCpf) {
      const ret = await api.get('v1/pix/descobre-nome', {
        params: {
          cnpjCpf: cnpjCpf
        }
      });
      return ret;
    },


    async VerificaExisteCnpjCpf(cnpjcpf) {
      const ret = await api.get('v1/pessoa', {
        params: cnpjcpf
      })
      return ret;
    },


    async verificaIeSefaz(codfilial, fisica, cnpjCpf) {
      var params = {
        codfilial: codfilial,
      }
      if (fisica) {
        params.cpf = cnpjCpf
      } else {
        params.cnpj = cnpjCpf
      }
      const ret = await api.get('v1/pessoa/verifica-ie-sefaz', {
        params: params
      })
      return ret;
    },

    async buscaAniversarios(tipo) {
      const ret = await api.get('v1/pessoa/aniversarios', {
        params: {tipo:tipo}
      })
      return ret;
    },

    async buscaPessoasSelectUsuario(model) {
      const ret = await api.get('v1/pessoa', {
        params: model 
      })
      return ret;
    },
  }
})

