import { defineStore } from "pinia";
import { toRaw } from "vue";
import { db } from "boot/db";
import { uid } from "quasar";

export const negocioStore = defineStore("negocio", {
  persist: true,

  state: () => ({
    pesquisa: [],
    textoPesuisa: null,
    negocio: null,
    negocios: [],
    outros: [],
    padrao: {
      codestoquelocal: 101001,
      codpessoa: 1,
      codnaturezaoperacao: 1,
      impressora: null,
      codpagarmepos: null,
    },
    paginaAtual: 1,
  }),

  getters: {
    quantidadeProdutosAtivos() {
      return this.itensAtivos.length;
    },
    itensAtivos() {
      if (!this.negocio) {
        return [];
      }
      return this.negocio.itens.filter((item) => {
        return item.inativo == null;
      });
    },
    itensInativos() {
      if (!this.negocio) {
        return [];
      }
      return this.negocio.itens.filter((item) => {
        return item.inativo != null;
      });
    },
  },

  actions: {
    async atualizarListagem() {
      this.negocios = await db.negocio
        .where("codnegociostatus")
        .equals(1)
        .reverse()
        .sortBy("criacao");
    },

    async carregar(id) {
      const negocio = await db.negocio.get(id);
      if (negocio != undefined) {
        this.negocio = negocio;
      }
      this.carregarChavesEstrangeiras();
      this.atualizarListagem();
      return negocio;
    },

    async recarregar() {
      this.negocio = await db.negocio.get(this.negocio.id);
      return true;
    },

    criar() {
      const id = uid();
      const negocio = {
        id: id,
        codnegocio: null,
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
        lancamento: new Date(),
        observacoes: null,
        recebimento: null,
        //valoraprazo: null,
        //valoravista: null,
        valorprodutos: null,
        percentualdesconto: null,
        valordesconto: null,
        valorfrete: null,
        valorseguro: null,
        valoroutras: null,
        valorjuros: null,
        valortotal: 0,
        criacao: new Date(),
        alteracao: new Date(),
        codusuarioalteracao: null,
        codusuariocriacao: null,
        itens: [],
        sincronizado: null,
        codnegociostatus: 1, //aberto
      };
      db.negocio.add(negocio, id);
      this.atualizarListagem();
      this.negocio = negocio;
      return negocio;
    },

    async recalcularValorTotal() {
      let valorprodutos = 0;
      let valordesconto = 0;
      let valorfrete = 0;
      let valorseguro = 0;
      let valoroutras = 0;
      this.negocio.itens
        .filter((item) => {
          return item.inativo == null;
        })
        .forEach((item) => {
          valorprodutos += parseFloat(item.valorprodutos);
          if (item.valordesconto > 0) {
            valordesconto += parseFloat(item.valordesconto);
          }
          if (item.valorfrete > 0) {
            valorfrete += parseFloat(item.valorfrete);
          }
          if (item.valorseguro > 0) {
            valorseguro += parseFloat(item.valorseguro);
          }
          if (item.valoroutras > 0) {
            valoroutras += parseFloat(item.valoroutras);
          }
        });
      let valortotal =
        valorprodutos - valordesconto + valorfrete + valorseguro + valoroutras;
      if (this.negocio.valorjuros > 0) {
        valortotal += parseFloat(this.negocio.valorjuros);
      }
      this.negocio.valorprodutos = Math.round(valorprodutos * 100) / 100;
      this.negocio.valordesconto = Math.round(valordesconto * 100) / 100;
      this.negocio.valorfrete = Math.round(valorfrete * 100) / 100;
      this.negocio.valorseguro = Math.round(valorseguro * 100) / 100;
      this.negocio.valoroutras = Math.round(valoroutras * 100) / 100;
      this.negocio.valortotal = Math.round(valortotal * 100) / 100;
    },

    async carregarPrimeiroVazio() {
      const negocios = await db.negocio
        .where({
          codnegociostatus: 1,
        })
        .filter((neg) => {
          if (neg.valortotal) {
            return false;
          }
          if (neg.codestoquelocal != this.padrao.codestoquelocal) {
            return false;
          }
          if (neg.codpessoa != this.padrao.codpessoa) {
            return false;
          }
          if (neg.codnaturezaoperacao != this.padrao.codnaturezaoperacao) {
            return false;
          }
          return true;
        })
        .sortBy("lancamento");
      if (negocios.length > 0) {
        this.negocio = negocios[0];
        return negocios[0];
      }
      return false;
    },

    async carregarPrimeiroVazioOuCriar() {
      var negocio = await this.carregarPrimeiroVazio();
      if (negocio == false) {
        negocio = this.criar();
      }
      await this.carregarChavesEstrangeiras();
      this.salvar();
      return negocio;
    },

    async carregarChavesEstrangeiras() {
      var naturezaoperacao = null;
      var codoperacao = null;
      var operacao = null;
      var negociostatus = null;
      var estoquelocal = null;
      var fantasia = null;
      var fantasiavendedor = null;

      //faker - apagar
      // this.negocio.codpessoavendedor = 224;
      // this.negocio.cpf = "80345271068";
      // this.negocio.observacoes = "teste \n linha 2 \n linha 3";

      // natureza
      if (this.negocio.codnaturezaoperacao) {
        const nat = await db.naturezaOperacao.get(
          this.negocio.codnaturezaoperacao
        );
        naturezaoperacao = nat.naturezaoperacao;
        codoperacao = nat.codoperacao;
        if (nat.codoperacao == 1) {
          operacao = "Entrada";
        } else {
          operacao = "Saída";
        }
      }

      // status
      switch (parseInt(this.negocio.codnegociostatus)) {
        case 2:
          negociostatus = "Fechado";
          break;
        case 3:
          negociostatus = "Cancelado";
          break;
        case 1:
        default:
          negociostatus = "Aberto";
          break;
      }

      // estoquelocal
      if (this.negocio.codestoquelocal) {
        const loc = await db.estoqueLocal.get(this.negocio.codestoquelocal);
        estoquelocal = loc.estoquelocal;
      }

      // natureza
      if (this.negocio.codnaturezaoperacao) {
        const nat = await db.naturezaOperacao.get(
          this.negocio.codnaturezaoperacao
        );
        naturezaoperacao = nat.naturezaoperacao;
        codoperacao = nat.codoperacao;
        if (nat.codoperacao == 1) {
          operacao = "Entrada";
        } else {
          operacao = "Saída";
        }
      }

      // Pessoa
      if (this.negocio.codpessoa) {
        const pes = await db.pessoa.get(this.negocio.codpessoa);
        if (pes.codformapagamento) {
          const fp = await db.formaPagamento.get(pes.codformapagamento);
          pes.formapagamento = fp.formapagamento;
        }
        this.negocio.Pessoa = pes;
        fantasia = pes.fantasia;
      }

      // Vendedor
      if (this.negocio.codpessoavendedor) {
        const vnd = await db.pessoa.get(this.negocio.codpessoavendedor);
        fantasiavendedor = vnd.fantasia;
      }

      this.negocio.naturezaoperacao = naturezaoperacao;
      this.negocio.codoperacao = codoperacao;
      this.negocio.operacao = operacao;
      this.negocio.negociostatus = negociostatus;
      this.negocio.estoquelocal = estoquelocal;
      this.negocio.fantasia = fantasia;
      this.negocio.fantasiavendedor = fantasiavendedor;
    },

    async salvar() {
      // marca alteracao
      this.negocio.alteracao = new Date();
      this.negocio.lancamento = new Date();
      const ret = await db.negocio.put(toRaw(this.negocio));
      this.atualizarListagem();
      return ret;
    },

    async itemAdicionar(
      codprodutobarra,
      barras,
      codproduto,
      produto,
      codimagem,
      quantidade,
      preco
    ) {
      // busca versao do IndexedDB para
      // garantir que nao foi adicionado nada em outra aba
      await this.recarregar();

      //verifica se o item já existe no negocio
      const index = this.negocio.itens.findIndex(function (item) {
        return (
          item.inativo === null &&
          parseInt(item.codprodutobarra) === parseInt(codprodutobarra)
        );
      });

      // se ja existe adiciona a quantiade, se nao cria um novo item
      if (index >= 0) {
        var item = this.negocio.itens.splice(index, 1);
        item = item[0];
        item.quantidade = parseFloat(item.quantidade) + parseFloat(quantidade);
        item.alteracao = new Date();
      } else {
        var item = {
          codprodutobarra,
          barras,
          codproduto,
          produto,
          codimagem,
          quantidade: parseFloat(quantidade),
          preco: parseFloat(preco),
          valorprodutos: null,
          percentualdesconto: null,
          valordesconto: null,
          valorfrete: null,
          valorseguro: null,
          valoroutras: null,
          valortotal: null,
          criacao: new Date(),
          inativo: null,
        };
        if (this.negocio.Pessoa.desconto) {
          item.percentualdesconto = this.negocio.Pessoa.desconto;
        }
      }

      // adiciona o item no inicio do array
      this.negocio.itens.unshift(item);

      // recalcula os totais
      this.itemRecalcularValorProdutos(item);

      // salva no IndexedDB
      this.salvar();
    },

    async itemAdicionarQuantidade(codprodutobarra, quantidade) {
      await this.recarregar();
      const item = this.negocio.itens.find(function (item) {
        return (
          item.inativo === null &&
          parseInt(item.codprodutobarra) === parseInt(codprodutobarra)
        );
      });
      if (!item) {
        return false;
      }
      const total = parseFloat(item.quantidade) + parseFloat(quantidade);
      if (total <= 0) {
        return;
      }
      item.quantidade = total;
      this.itemRecalcularValorProdutos(item);
      // salva no IndexedDB
      this.salvar();
    },

    async itemSalvar(
      codprodutobarra,
      quantidade,
      preco,
      valorprodutos,
      percentualdesconto,
      valordesconto,
      valorfrete,
      valorseguro,
      valoroutras,
      valortotal
    ) {
      await this.recarregar();
      const item = this.negocio.itens.find(function (item) {
        return (
          item.inativo === null &&
          parseInt(item.codprodutobarra) === parseInt(codprodutobarra)
        );
      });
      if (!item) {
        return false;
      }
      item.quantidade = quantidade;
      item.preco = preco;
      item.valorprodutos = valorprodutos;
      item.percentualdesconto = percentualdesconto;
      item.valordesconto = valordesconto;
      item.valorfrete = valorfrete;
      item.valorseguro = valorseguro;
      item.valoroutras = valoroutras;
      item.valortotal = valortotal;
      this.recalcularValorTotal();
      this.salvar();
    },

    async itemInativar(codprodutobarra) {
      await this.recarregar();
      const inativar = this.negocio.itens.find(function (item) {
        return (
          item.inativo === null &&
          parseInt(item.codprodutobarra) == parseInt(codprodutobarra)
        );
      });
      if (inativar) {
        inativar.inativo = new Date();
        this.recalcularValorTotal();
        this.salvar();
      }
    },

    async itemRecalcularValorProdutos(item) {
      let total =
        Math.round(parseFloat(item.quantidade) * parseFloat(item.preco) * 100) /
        100;
      item.valorprodutos = total;
      this.itemRecalcularValorDesconto(item);
    },

    async itemRecalcularValorDesconto(item) {
      if (item.percentualdesconto <= 0) {
        item.valordesconto = null;
      } else {
        item.valordesconto =
          Math.round(item.valorprodutos * item.percentualdesconto) / 100;
      }
      this.itemRecalcularValorTotal(item);
    },

    async itemRecalcularValorTotal(item) {
      let total = parseFloat(item.valorprodutos);
      if (item.valordesconto) {
        total -= parseFloat(item.valordesconto);
      }
      if (item.valorfrete) {
        total += parseFloat(item.valorfrete);
      }
      if (item.valorseguro) {
        total += parseFloat(item.valorseguro);
      }
      if (item.valoroutras) {
        total += parseFloat(item.valoroutras);
      }
      item.valortotal = Math.round(total * 100) / 100;
      this.recalcularValorTotal();
    },

    async aplicarValores(valordesconto, valorfrete, valorseguro, valoroutras) {
      var percentualdesconto = null;
      var percentualfrete = null;
      var percentualseguro = null;
      var percentualoutras = null;

      var saldodesconto = valordesconto;
      var saldofrete = valorfrete;
      var saldoseguro = valorseguro;
      var saldooutras = valoroutras;

      await this.recarregar();

      if (valordesconto && this.negocio.valorprodutos) {
        percentualdesconto = valordesconto / this.negocio.valorprodutos;
      }
      if (valorfrete && this.negocio.valorprodutos) {
        percentualfrete = valorfrete / this.negocio.valorprodutos;
      }
      if (valorseguro && this.negocio.valorprodutos) {
        percentualseguro = valorseguro / this.negocio.valorprodutos;
      }
      if (valoroutras && this.negocio.valorprodutos) {
        percentualoutras = valoroutras / this.negocio.valorprodutos;
      }

      const ultimo = this.negocio.itens.length - 1;
      for (let index = 0; index <= ultimo; index++) {
        const item = this.negocio.itens[index];
        if (!percentualdesconto) {
          item.valordesconto = null;
        } else if (index == ultimo) {
          item.percentualdesconto = Math.round(percentualdesconto * 100);
          item.valordesconto = Math.round(saldodesconto * 100) / 100;
        } else {
          item.percentualdesconto = Math.round(percentualdesconto * 100);
          item.valordesconto =
            Math.round(item.valorprodutos * percentualdesconto * 100) / 100;
          saldodesconto -= item.valordesconto;
        }

        if (!percentualfrete) {
          item.valorfrete = null;
        } else if (index == ultimo) {
          item.valorfrete = Math.round(saldofrete * 100) / 100;
        } else {
          item.valorfrete =
            Math.round(item.valorprodutos * percentualfrete * 100) / 100;
          saldofrete -= item.valorfrete;
        }

        if (!percentualseguro) {
          item.valorseguro = null;
        } else if (index == ultimo) {
          item.valorseguro = Math.round(saldoseguro * 100) / 100;
        } else {
          item.valorseguro =
            Math.round(item.valorprodutos * percentualseguro * 100) / 100;
          saldoseguro -= item.valorseguro;
        }

        if (!percentualoutras) {
          item.valoroutras = null;
        } else if (index == ultimo) {
          item.valoroutras = Math.round(saldooutras * 100) / 100;
        } else {
          item.valoroutras =
            Math.round(item.valorprodutos * percentualoutras * 100) / 100;
          saldooutras -= item.valoroutras;
        }

        this.itemRecalcularValorTotal(item);
      }
      await this.recalcularValorTotal();
      this.salvar();
    },

    async informarPessoa(
      codestoquelocal,
      codnaturezaoperacao,
      codpessoa,
      cpf,
      observacoes
    ) {
      await this.recarregar();
      this.negocio.codestoquelocal = codestoquelocal;
      this.negocio.codnaturezaoperacao = codnaturezaoperacao;
      this.negocio.codpessoa = codpessoa;
      if (codpessoa == 1) {
        this.negocio.cpf = cpf;
      } else {
        this.negocio.cpf = null;
      }
      this.negocio.observacoes = observacoes;
      await this.carregarChavesEstrangeiras();

      // desconto
      const desconto = this.negocio.Pessoa.desconto;
      this.negocio.itens.forEach((item) => {
        item.percentualdesconto = desconto;
        this.itemRecalcularValorProdutos(item);
      });

      await this.salvar();
    },

    async informarVendedor(codpessoavendedor) {
      await this.recarregar();
      this.negocio.codpessoavendedor = codpessoavendedor;
      await this.carregarChavesEstrangeiras();
      await this.salvar();
    },
  },
});
