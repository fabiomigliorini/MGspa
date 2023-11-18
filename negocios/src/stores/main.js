import { defineStore } from "pinia";

export const mainStore = defineStore("negocios", {
  state: () => ({
    pesquisa: [],
    textoPesuisa: null,
    negocio: null,
    outros: [],
    ultimaSincronizacaoProdutos: null,
    padrao: {
      codfilial: 101,
      codestoquelocal: 101001,
      codpessoa: 1,
      codnaturezaoperacao: 1,
    },
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
      preco
    ) {
      if (this.negocio == null) {
        await this.criarNovoNegocio();
      }

      const index = this.negocio.NegocioProdutoBarraS.findIndex(function (npb) {
        return npb.inativo === null && npb.codprodutobarra === codprodutobarra;
      });

      if (index >= 0) {
        var item = this.negocio.NegocioProdutoBarraS.splice(index, 1);
        item = item[0];
        item.quantidade += quantidade;
        item.valortotal = item.quantidade * item.preco;
      } else {
        var item = {
          codprodutobarra,
          barras,
          codproduto,
          produto,
          codimagem,
          quantidade,
          preco,
          valortotal: quantidade * preco,
          inativo: null,
        };
      }
      this.negocio.NegocioProdutoBarraS.unshift(item);
    },

    async adicionarQuantidade(index, quantidade) {
      var item = this.negocio.NegocioProdutoBarraS[index];
      if (item.quantidade + quantidade <= 0) {
        return;
      }
      item.quantidade += quantidade;
      item.valortotal = item.quantidade * item.preco;
    },

    inativar(index) {
      var item = this.negocio.NegocioProdutoBarraS.splice(index, 1);
      item = item[0];
      item.inativo = Date();
      this.negocio.NegocioProdutoBarraS.push(item);
    },

    atualizaValorTotal(iNegocio) {
      this.counter++;
    },
  },
  persist: true,
});
