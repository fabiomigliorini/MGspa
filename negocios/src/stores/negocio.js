import { defineStore } from "pinia";
import { toRaw } from "vue";
import { db } from "boot/db";
import { Notify, uid } from "quasar";
import { sincronizacaoStore } from "stores/sincronizacao";
import moment from "moment";
import tiposPagamento from "../data/tipos-pagamento.json";
import bandeirasCartao from "../data/bandeiras-cartao.json";
import { falar } from "../utils/falar.js";

const sSinc = sincronizacaoStore();

export const negocioStore = defineStore("negocio", {
  persist: {
    paths: ["padrao", "paginaAtual", "ultimos"],
  },

  state: () => ({
    negocio: null,
    negocios: [],
    ultimos: [],
    dialog: {
      valores: false,
      pagamentoDinheiro: false,
      pagamentoPix: false,
      pagamentoPagarMe: false,
      pagamentoCartaoManual: false,
      pagamentoPrazo: false,
    },
    padrao: {
      codestoquelocal: 101001, //Deposito
      codpessoa: 1, //Consumidor
      codnaturezaoperacao: 1, //Venda
      codoperacao: 2, //Saída
      venda: true, //Saída
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
    podeEditar() {
      return this.negocio?.codnegociostatus == 1;
    },
    valorapagar() {
      var pagamentos = 0;
      if (this.negocio.pagamentos) {
        pagamentos = this.negocio.pagamentos
          .map((item) => item.valortotal)
          .reduce((prev, curr) => prev + curr, 0);
      }
      return Math.round((this.negocio.valortotal - pagamentos) * 100) / 100;
    },
  },

  actions: {
    async salvarPadrao(padrao) {
      this.padrao = { ...padrao };
      const nat = await db.naturezaOperacao.get(padrao.codnaturezaoperacao);
      this.padrao.codoperacao = nat.codoperacao;
      this.padrao.venda = nat.venda;
    },

    async atualizarListagem() {
      // em aberto
      this.negocios = await db.negocio
        .where("codnegociostatus")
        .equals(1)
        .reverse()
        .sortBy("criacao");

      // ultimos 10 fechados/cancelados
      if (this.negocio.codnegociostatus == 1) {
        return;
      }
      var ultimos = this.ultimos;
      const i = ultimos.findIndex((u) => {
        return u.codnegocio == this.negocio.codnegocio;
      });
      if (i > -1) {
        ultimos[i] = this.negocio;
      } else {
        ultimos.unshift(this.negocio);
      }
      if (ultimos.length > 10) {
        ultimos = ultimos.slice(0, 10);
      }
      this.ultimos = ultimos;
    },

    async carregarPeloCodnegocio(codnegocio) {
      // busca no indexedDB
      const negocio = await db.negocio
        .where("codnegocio")
        .equals(codnegocio)
        .first();

      // se nao tem offline busca na api
      if (negocio == undefined) {
        try {
          await this.recarregarDaApi(codnegocio);
          return this.negocio;
        } catch (error) {
          console.log(error);
          Notify.create({
            type: "negative",
            message: "Falha ao buscar dados no Servidor!",
            actions: [{ icon: "close", color: "white" }],
          });
          return false;
        }
      }

      // se negocio esta sincronizado busca da API para
      // caso o negocio tenha sido alterado em outro computador
      this.negocio = negocio;
      if (negocio.sincronizado) {
        try {
          this.recarregarDaApi(codnegocio);
        } catch (error) {
          console.log(error);
        }
      }
      await this.carregarChavesEstrangeiras();
      await this.atualizarListagem();
      return this.negocio;
    },

    async carregarPeloUuid(uuid) {
      const negocio = await db.negocio.get(uuid);

      // verifica se deve recarregar da api
      if (negocio == undefined) {
        try {
          await this.recarregarDaApi(uuid);
          return this.negocio;
        } catch (error) {
          console.log(error);
          Notify.create({
            type: "negative",
            message: "Falha ao buscar dados no Servidor!",
            actions: [{ icon: "close", color: "white" }],
          });
          return false;
        }
      }

      // se negocio esta sincronizado busca da API para
      // caso o negocio tenha sido alterado em outro computador
      this.negocio = negocio;
      if (negocio.sincronizado) {
        try {
          this.recarregarDaApi(uuid);
        } catch (error) {
          console.log(error);
        }
      }
      await this.carregarChavesEstrangeiras();
      await this.atualizarListagem();
      return this.negocio;
    },

    async recarregar() {
      this.negocio = await db.negocio.get(this.negocio.uuid);
      return true;
    },

    async criar() {
      const uuid = uid();
      const negocio = {
        uuid: uuid,
        codnegocio: null,
        codestoquelocal: this.padrao.codestoquelocal,
        codestoquelocaldestino: null,
        codnaturezaoperacao: this.padrao.codnaturezaoperacao,
        codoperacao: this.padrao.codoperacao,
        venda: this.padrao.venda,
        naturezaoperacao: null,
        financeiro: false,
        codnegociostatus: 1,
        codpessoa: this.padrao.codpessoa,
        pessoa: null,
        codpessoatransportador: null,
        codpessoavendedor: null,
        codusuario: null,
        codusuarioacertoentrega: null,
        codusuariorecebimento: null,
        cpf: null,
        entrega: false,
        lancamento: moment().format("YYYY-MM-DD HH:mm:ss"),
        observacoes: null,
        recebimento: null,
        valorprodutos: 0,
        percentualdesconto: null,
        valordesconto: null,
        valorfrete: null,
        valorseguro: null,
        valoroutras: null,
        valorjuros: null,
        valortotal: 0,
        criacao: moment().format("YYYY-MM-DD HH:mm:ss"),
        alteracao: moment().format("YYYY-MM-DD HH:mm:ss"),
        codusuarioalteracao: null,
        codusuariocriacao: null,
        sincronizado: false,
        codnegociostatus: 1, //aberto
        itens: [],
        pagamentos: [],
        titulos: [],
        notas: [],
      };
      db.negocio.add(negocio, uuid);
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

      // soma totais dos itens
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

      // soma os juros dos pagamentos
      const valorjuros = this.negocio.pagamentos.reduce((acumulador, pag) => {
        return acumulador + pag.valorjuros;
      }, 0);

      let valortotal =
        valorprodutos -
        valordesconto +
        valorfrete +
        valorseguro +
        valoroutras +
        valorjuros;

      this.negocio.valorprodutos = Math.round(valorprodutos * 100) / 100;
      this.negocio.valordesconto = Math.round(valordesconto * 100) / 100;
      this.negocio.valorfrete = Math.round(valorfrete * 100) / 100;
      this.negocio.valorseguro = Math.round(valorseguro * 100) / 100;
      this.negocio.valoroutras = Math.round(valoroutras * 100) / 100;
      this.negocio.valorjuros = Math.round(valorjuros * 100) / 100;
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
        negocio = await this.criar();
        await this.carregarChavesEstrangeiras();
        await this.salvar();
      } else {
        await this.carregarChavesEstrangeiras();
      }
      return negocio;
    },

    async duplicar() {
      const uuid = uid();
      // const negocio = { ...this.negocio };
      // let negocio = Object.assign({}, this.negocio);
      let negocio = JSON.parse(JSON.stringify(this.negocio));
      negocio.codnegocio = null;
      negocio.uuid = uuid;
      negocio.codnegociostatus = 1;
      negocio.lancamento = moment().format("YYYY-MM-DD HH:mm:ss");
      negocio.criacao = moment().format("YYYY-MM-DD HH:mm:ss");
      negocio.alteracao = moment().format("YYYY-MM-DD HH:mm:ss");
      negocio.sincronizado = false;
      negocio.codpdv = null;
      negocio.pagamentos = [];
      negocio.titulos = [];
      negocio.notas = [];
      negocio.PagarMePedidoS = [];
      negocio.pixCob = [];
      negocio.itens = negocio.itens.filter((i) => {
        return i.inativo == null;
      });
      negocio.itens.forEach((i) => {
        i.codnegocioprodutobarra = null;
        i.codnegocio = null;
        i.uuid = uid();
      });
      db.negocio.add(negocio, uuid);
      this.atualizarListagem();
      this.negocio = negocio;
      await this.carregarChavesEstrangeiras();
      await this.salvar();
      this.atualizarListagem();
      return negocio;
    },

    async carregarChavesEstrangeiras() {
      var naturezaoperacao = null;
      var codoperacao = null;
      var venda = null;
      var operacao = null;
      var negociostatus = null;
      var estoquelocal = null;
      var fantasia = null;
      var fantasiavendedor = null;
      var financeiro = false;

      // natureza
      if (this.negocio.codnaturezaoperacao) {
        const nat = await db.naturezaOperacao.get(
          this.negocio.codnaturezaoperacao
        );
        naturezaoperacao = nat.naturezaoperacao;
        financeiro = nat.financeiro;
        codoperacao = nat.codoperacao;
        venda = nat.venda;
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

      // Pessoa
      if (this.negocio.codpessoa) {
        const pes = await db.pessoa.get(this.negocio.codpessoa);
        if (pes) {
          if (pes.codformapagamento) {
            const fp = await db.formaPagamento.get(pes.codformapagamento);
            pes.formapagamento = fp.formapagamento;
          }
          this.negocio.Pessoa = pes;
          fantasia = pes.fantasia;
        }
      }

      // Vendedor
      if (this.negocio.codpessoavendedor) {
        const vnd = await db.pessoa.get(this.negocio.codpessoavendedor);
        fantasiavendedor = vnd.fantasia;
      }

      this.negocio.naturezaoperacao = naturezaoperacao;
      this.negocio.financeiro = financeiro;
      this.negocio.codoperacao = codoperacao;
      this.negocio.venda = venda;
      this.negocio.operacao = operacao;
      this.negocio.negociostatus = negociostatus;
      this.negocio.estoquelocal = estoquelocal;
      this.negocio.fantasia = fantasia;
      this.negocio.fantasiavendedor = fantasiavendedor;
    },

    async salvar(sincronizar = true) {
      // marca alteracao
      if (sincronizar) {
        if (this.negocio.codnegociostatus == 1) {
          this.negocio.alteracao = moment().format("YYYY-MM-DD HH:mm:ss");
          this.negocio.lancamento = moment().format("YYYY-MM-DD HH:mm:ss");
        }
        this.negocio.sincronizado = false;
      }
      const ret = await db.negocio.put(toRaw(this.negocio));
      if (sincronizar) {
        this.sincronizar(this.negocio.uuid);
      }
      await this.atualizarListagem();
      return ret;
    },

    async itemAdicionar(
      codprodutobarra,
      barras,
      codproduto,
      produto,
      codimagem,
      quantidade,
      valorunitario
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
        item.alteracao = moment().format("YYYY-MM-DD HH:mm:ss");
      } else {
        var item = {
          uuid: uid(),
          codprodutobarra,
          barras,
          codproduto,
          produto,
          codimagem,
          quantidade: parseFloat(quantidade),
          valorunitario: parseFloat(valorunitario),
          valorprodutos: 0,
          percentualdesconto: null,
          valordesconto: null,
          valorfrete: null,
          valorseguro: null,
          valoroutras: null,
          valortotal: null,
          criacao: moment().format("YYYY-MM-DD HH:mm:ss"),
          inativo: null,
        };
        if (this.negocio.Pessoa.desconto) {
          item.percentualdesconto = this.negocio.Pessoa.desconto;
        }
      }

      const palavras = item.produto.split(" ");
      var texto = "";
      if (palavras.length >= 2) {
        texto = palavras[0] + " " + palavras[1];
      } else {
        texto = palavras[0];
      }
      falar(texto);

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
      valorunitario,
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
      item.valorunitario = valorunitario;
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
        inativar.inativo = moment().format("YYYY-MM-DD HH:mm:ss");
        this.recalcularValorTotal();
        this.salvar();
      }
    },

    async itemRecalcularValorProdutos(item) {
      let total =
        Math.round(
          parseFloat(item.quantidade) * parseFloat(item.valorunitario) * 100
        ) / 100;
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

      const ultimo = this.itensAtivos.length - 1;
      for (let index = 0; index <= ultimo; index++) {
        const item = this.itensAtivos[index];
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

      // se tiver desconto e o negócio ainda estiver aberto
      const desconto = parseFloat(this.negocio.Pessoa.desconto);
      if (desconto > 0 && this.negocio.codnegociostatus == 1) {
        this.negocio.itens.forEach((item) => {
          item.percentualdesconto = desconto;
          this.itemRecalcularValorProdutos(item);
        });
      }

      await this.salvar();
    },

    async informarVendedor(codpessoavendedor) {
      await this.recarregar();
      this.negocio.codpessoavendedor = codpessoavendedor;
      await this.carregarChavesEstrangeiras();
      await this.salvar();
    },

    async sincronizar(uuid) {
      const negocio = await db.negocio.get(uuid);
      try {
        const ret = await sSinc.putNegocio(negocio);
        if (!ret) {
          return false;
        }
        db.negocio.update(ret.uuid, {
          codnegocio: ret.codnegocio,
          codnegociostatus: ret.codnegociostatus,
        });
        if (this.negocio.uuid == ret.uuid) {
          this.negocio.codnegocio = ret.codnegocio;
          this.negocio.codnegociostatus = ret.codnegociostatus;
          if (
            this.negocio.valortotal == ret.valortotal &&
            this.negocio.valordesconto == ret.valordesconto &&
            this.negocio.valorprodutos == ret.valorprodutos &&
            this.negocio.valorjuros == ret.valorjuros &&
            this.negocio.valoroutras == ret.valoroutras
          ) {
            this.negocio.sincronizado = true;
            db.negocio.update(ret.uuid, {
              sincronizado: true,
            });
          }
        }
      } catch (error) {
        console.log(error);
        return false;
      }
      await this.atualizarListagem();
      return true;
    },

    async recarregarDaApi(codOrUuid) {
      try {
        const ret = await sSinc.getNegocio(codOrUuid);
        if (!ret.codnegocio) {
          return false;
        }
        this.negocio = ret;
        db.negocio.put(ret);
        return true;
      } catch (error) {
        console.log(error);
        return false;
      }
    },

    async adicionarPagamento(
      codformapagamento,
      tipo,
      valorpagamento,
      valorjuros,
      valortroco,
      codpessoa,
      bandeira,
      autorizacao,
      parcelas,
      valorparcela
    ) {
      await this.recarregar();

      // descricao forma de pagamento
      const fp = await db.formaPagamento.get(codformapagamento);

      // nome parceiro
      let parceiro = null;
      if (codpessoa) {
        const pes = await db.pessoa.get(codpessoa);
        parceiro = pes.fantasia;
      }

      // nome bandeira
      let nomebandeira = null;
      if (bandeira) {
        const band = bandeirasCartao.find((el) => {
          return el.bandeira == bandeira;
        });
        nomebandeira = band.nome;
      }

      // nome Tipo
      let nometipo = null;
      if (tipo) {
        const tp = tiposPagamento.find((el) => {
          return el.tipo == tipo;
        });
        nometipo = tp.nome;
      }

      // objeto do pagamento
      const pagamento = {
        codnegocioformapagamento: null,
        uuid: uid(),
        codformapagamento: codformapagamento,
        formapagamento: fp.formapagamento,
        alteracao: moment().format("YYYY-MM-DD HH:mm:ss"),
        criacao: moment().format("YYYY-MM-DD HH:mm:ss"),
        valorpagamento: valorpagamento,
        valorjuros: valorjuros,
        valortotal: valorpagamento + valorjuros,
        valortroco: valortroco,
        avista: fp.avista,
        tipo: tipo,
        nometipo: nometipo,
        integracao: false,
        codpessoa: codpessoa,
        parceiro: parceiro,
        bandeira: bandeira,
        nomebandeira: nomebandeira,
        autorizacao: autorizacao,
        parcelas: parcelas,
        valorparcela: valorparcela,
      };
      this.negocio.pagamentos.push(pagamento);

      // recalcula total por causa dos juros
      this.recalcularValorTotal();

      // salva
      this.salvar();
    },

    async excluirPagamento(uuid) {
      await this.recarregar();
      const index = this.negocio.pagamentos.findIndex(function (item) {
        return item.uuid == uuid;
      });
      if (index > -1) {
        this.negocio.pagamentos.splice(index, 1);
        // recalcula total por causa dos juros
        this.recalcularValorTotal();
      }
      this.salvar();
    },

    async fechar() {
      if (!this.negocio.sincronizado) {
        Notify.create({
          type: "negative",
          message:
            "Impossível fechar um negócio não sincronizado com o servidor!",
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
      try {
        const ret = await sSinc.fecharNegocio(this.negocio.codnegocio);
        if (ret.codnegocio) {
          Notify.create({
            type: "positive",
            message: "Negócio Fechado!",
          });
          this.negocio = ret;
          db.negocio.put(ret);
          await this.atualizarListagem();
        }
      } catch (error) {
        console.log(error);
      }
    },

    async cancelar(justificativa) {
      if (!this.negocio.sincronizado) {
        Notify.create({
          type: "negative",
          message:
            "Impossível cancelar um negócio não sincronizado com o servidor!",
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
      try {
        const ret = await sSinc.cancelarNegocio(
          this.negocio.codnegocio,
          justificativa
        );
        if (ret.codnegocio) {
          Notify.create({
            type: "positive",
            message: "Negócio cancelado!",
          });
          this.negocio = ret;
          db.negocio.put(ret);
          await this.atualizarListagem();
        }
      } catch (error) {
        console.log(error);
      }
    },

    async criarPixCob(valor) {
      if (!this.negocio.sincronizado) {
        Notify.create({
          type: "negative",
          message:
            "Impossível criar Cobrança PIX em um negócio não sincronizado com o servidor!",
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
      try {
        const ret = await sSinc.criarPixCob(valor, this.negocio.codnegocio);
        if (ret.codnegocio) {
          Notify.create({
            type: "positive",
            message: "Cobrança PIX Criada!",
          });
          this.negocio = ret;
          db.negocio.put(ret);
          await this.atualizarListagem();
          return ret.pixCob[0];
        }
      } catch (error) {
        console.log(error);
      }
    },

    async criarPagarMePedido(
      codpagarmepos,
      valor,
      valorparcela,
      valorjuros,
      tipo,
      parcelas,
      jurosloja
    ) {
      if (!this.negocio.sincronizado) {
        Notify.create({
          type: "negative",
          message:
            "Impossível criar Cobrança Stone/PagarMe em um negócio não sincronizado com o servidor!",
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
      try {
        const descricao = "Negocio " + this.negocio.codnegocio;
        const ret = await sSinc.criarPagarMePedido(
          this.negocio.codnegocio,
          this.negocio.codpessoa,
          codpagarmepos,
          valor,
          valorparcela,
          valorjuros,
          tipo,
          parcelas,
          jurosloja,
          descricao
        );
        if (ret.codnegocio) {
          Notify.create({
            type: "positive",
            message: "Cobrança Pagar Me/Stone Criada!",
          });
          this.negocio = ret;
          db.negocio.put(ret);
          await this.atualizarListagem();
          return ret.PagarMePedidoS[0];
        }
      } catch (error) {
        console.log(error);
      }
    },

    async unificarComanda(codnegociocomanda) {
      if (!this.negocio.sincronizado) {
        Notify.create({
          type: "negative",
          message:
            "Impossível ler Comandas em um negócio não sincronizado com o servidor!",
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
      try {
        const ret = await sSinc.unificarComanda(
          this.negocio.codnegocio,
          codnegociocomanda
        );
        if (!ret) {
          return false;
        }
        Notify.create({
          type: "positive",
          message: "Comanda Lida!",
        });
        if (ret.negocio.codnegocio) {
          db.negocio.put(ret.negocio);
        }
        if (ret.comanda.codnegocio) {
          db.negocio.put(ret.comanda);
        }
        this.negocio = ret.negocio;
        await this.atualizarListagem();
        return true;
      } catch (error) {
        console.log(error);
        return false;
      }
    },

    async Devolucao(arrDevolucao) {
      const postDevolucao = await sSinc.negocioDevolucao(
        this.negocio.codnegocio,
        arrDevolucao
      );

      return postDevolucao;
    },
  },
});
