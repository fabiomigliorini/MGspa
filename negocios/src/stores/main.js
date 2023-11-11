import { defineStore } from "pinia";

export const mainStore = defineStore("negocios", {
  state: () => ({
    pesquisa: [],
    negocio: null,
    outros: [],
    padrao: {
      codfilial: 101,
      codestoquelocal: 101001,
      codpessoa: 1,
      codnaturezaoperacao: 1,
    }
  }),

  getters: {
    doubleCount(state) {
      return state.counter * 2;
    },
  },

  actions: {
    criarNovoNegocio() {
      this.negocio = {
        codnegocio: null,
        codfilial: this.padrao.codfilial,
        codestoquelocal: this.padrao.codestoquelocal,
        codestoquelocaldestino: null,
        codnaturezaoperacao: this.padrao.codnaturezaoperacao,
        codnegociostatus: 1,
        codpessoa: this.padrao.codpessoa,
        codpessoatransportador: null,
        codpessoavendedor: null,
        codusuario: null,
        codusuarioacertoentrega: null,
        codusuariorecebimento: null,
        cpf: null,
        entrega: null,
        lancamento: null,
        observacoes: null,
        recebimento: null,
        valoraprazo: null,
        valoravista: null,
        valordesconto: null,
        valorfrete: null,
        valorjuros: null,
        valorprodutos: null,
        valortotal: null,
        criacao: null,
        alteracao: null,
        codusuarioalteracao: null,
        codusuariocriacao: null,
        NegocioProdutoBarraS: [],
        sincronizado: null,
      };
    },

    async adicionarItem(
      codprodutobarra,
      barras,
      codproduto,
      produto,
      codimagem,
      quantidade,
      preco,
      valortotal) {

      if (this.negocio == null) {
        await this.criarNovoNegocio();
      }

      console.log(codprodutobarra)
      const index = this.negocio.NegocioProdutoBarraS.findIndex(function (npb) {
        return npb.codprodutobarra === codprodutobarra;
      });

      if (index >= 0) {
        console.log('achout');
        this.negocio.NegocioProdutoBarraS[index].quantidade += quantidade;
        this.negocio.NegocioProdutoBarraS[index].valortotal =
          this.negocio.NegocioProdutoBarraS[index].quantidade *
          this.negocio.NegocioProdutoBarraS[index].preco;
      } else {
        console.log('nao achou');
        this.negocio.NegocioProdutoBarraS.push({
          codprodutobarra,
          barras,
          codproduto,
          produto,
          codimagem,
          quantidade,
          preco,
          valortotal: quantidade * preco,
        });
      }


    },

    atualizaValorTotal(iNegocio) {
      this.counter++;
    },
  },
  persist: true,
});
