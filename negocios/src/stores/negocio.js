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
      pagamentoVale: false,
      pagamentoPix: false,
      pagamentoPagarMe: false,
      pagamentoSaurus: false,
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
      codsauruspos: null,
      maquineta: null,
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
      return this.negocio.itens
        .filter((item) => item.inativo == null)
        .sort((a, b) => b.ordenacao.localeCompare(a.ordenacao));
    },
    itensInativos() {
      if (!this.negocio) {
        return [];
      }
      return this.negocio.itens
        .filter((item) => item.inativo != null)
        .sort((a, b) => b.inativo.localeCompare(a.inativo));
    },
    podeEditar() {
      return (
        this.negocio?.codnegociostatus == 1 &&
        this.negocio?.codpdv == sSinc.pdv?.codpdv
      );
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
      // busca todos negocios abertos do PDV
      let negs = await db.negocio
        .where("[codnegociostatus+codpdv]")
        .equals([1, sSinc.pdv.codpdv])
        .reverse()
        .sortBy("criacao");
      this.negocios = negs;

      // verifica se tem negocio aberto
      if (this.negocio) {
        // verifica se o negocio está na listagem de abertos
        const iNegocio = negs.findIndex((neg) => {
          return neg.codnegocio == this.negocio.codnegocio;
        });

        // verifica se o negocio está na listagem dos ultimos
        var ultimos = this.ultimos;
        const iUltimo = ultimos.findIndex((u) => {
          return u.codnegocio == this.negocio.codnegocio;
        });

        if (iUltimo != -1) {
          if (iNegocio != -1) {
            // se esta nos abertos remove dos ultimos
            ultimos.splice(iUltimo, 1);
          } else {
            // senao atualiza ele na listagem de ultimos
            ultimos[iUltimo] = { ...this.negocio };
          }
        } else if (iNegocio == -1) {
          // se nao esta nem nos ultimos nem nos abertos, adiciona nos ultimos
          ultimos.unshift({ ...this.negocio });
        }

        //se listagem de ultimos maior que 10 registros filtra os 10 primeiros
        if (ultimos.length > 10) {
          ultimos = ultimos.slice(0, 10);
        }

        // atualiza listagem dos ultimos
        this.ultimos = ultimos;
      }
    },

    async reconsultarAbertos() {
      // mostra notificacao
      const dismiss = Notify.create({
        type: "ongoing",
        message: "Reconsultando meus negócios abertos no Servidor!",
        timeout: 0,
      });

      // percorre todos negocios abertos do pdv
      var iNeg = 1;
      for (const neg of this.negocios) {
        // se nao está sincronizado, não reconsulta no servidor
        if (!neg.sincronizado) {
          continue;
        }

        try {
          // log pra saber se deu algum erro
          console.log(
            "reconsultnado " +
              iNeg +
              "/" +
              this.negocios.length +
              " - " +
              neg.codnegocio
          );
          iNeg++;

          // consulta no servidor
          const ret = await sSinc.getNegocio(neg.uuid);

          // salva no banco local
          let retDb = await db.negocio.put(ret);

          // se for o negocio aberto, atualiza o objeto da tela
          if (this.negocio.uuid == ret.uuid) {
            this.negocio = { ...ret };
          }
        } catch (error) {
          console.log("Erro ao buscar " + neg.uuid);
          console.log(error);
        }
      }

      // atualiza listagem de negocios abertos (drawer)
      this.atualizarListagem();

      // fecha notificacao
      dismiss();
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
            timeout: 3000, // 3 segundos
            actions: [{ icon: "close", color: "white" }],
          });
          return false;
        }
      }

      // se negocio esta sincronizado busca da API para
      // caso o negocio tenha sido alterado em outro computador
      if (!negocio.sincronizado) {
        this.negocio = { ...negocio };
        await this.carregarChavesEstrangeiras();
      } else {
        try {
          await this.recarregarDaApi(codnegocio);
        } catch (error) {
          this.negocio = { ...negocio };
          await this.carregarChavesEstrangeiras();
          console.log(error);
        }
      }
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
            timeout: 3000, // 3 segundos
            actions: [{ icon: "close", color: "white" }],
          });
          return false;
        }
      }

      // se negocio esta sincronizado busca da API para
      // caso o negocio tenha sido alterado em outro computador
      if (!negocio.sincronizado) {
        this.negocio = { ...negocio };
        await this.carregarChavesEstrangeiras();
      } else {
        try {
          await this.recarregarDaApi(uuid);
        } catch (error) {
          this.negocio = { ...negocio };
          await this.carregarChavesEstrangeiras();
          console.log(error);
        }
      }
      return this.negocio;
    },

    async recarregar() {
      const neg = await db.negocio.get(this.negocio.uuid);
      this.negocio = { ...neg };
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
        codpdv: sSinc.pdv.codpdv,
        Pdv: { ...sSinc.pdv },
      };
      db.negocio.add(negocio, uuid);
      this.negocio = { ...negocio };
      await this.atualizarListagem();
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

      await this.recalcularTroco();
    },

    async recalcularTroco() {
      const pagar = this.valorapagar;
      let troco = pagar < 0 ? Math.abs(pagar) : null;
      this.negocio.pagamentos
        .filter(
          (pag) =>
            pag.codformapagamento == process.env.CODFORMAPAGAMENTO_DINHEIRO
        )
        .sort((a, b) => b.valorpagamento - a.valorpagamento) // ordem decrescente
        .forEach((pag) => {
          pag.valortroco = Math.min(troco, pag.valorpagamento);
          troco -= pag.valortroco;
        });
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
          if (neg.codpdv != sSinc.pdv.codpdv) {
            return false;
          }
          return true;
        })
        .sortBy("lancamento");
      if (negocios.length > 0) {
        this.negocio = { ...negocios[0] };
        return negocios[0];
      }
      return false;
    },

    async carregarPrimeiroVazioOuCriar() {
      if (!sSinc.pdv.codpdv) {
        Notify.create({
          type: "negative",
          message: "Registro de PDV ainda não criado no servidor!",
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
      if (!sSinc.ultimaSincronizacao.completa) {
        Notify.create({
          type: "negative",
          message: "Ainda não foi feita nenhuma sincronização!",
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
      let negocio = await this.carregarPrimeiroVazio();
      if (negocio != false) {
        await this.carregarChavesEstrangeiras();
        return negocio;
      }
      negocio = await this.criar();
      if (negocio != false) {
        await this.carregarChavesEstrangeiras();
        await this.salvar();
        return negocio;
      }
      return false;
    },

    async duplicar() {
      const uuid = uid();
      // const negocio = { ...this.negocio };
      // let negocio = Object.assign({}, this.negocio);
      let negocio = JSON.parse(JSON.stringify(this.negocio));
      negocio.codnegocio = null;
      negocio.uuid = uuid;
      negocio.codnegociostatus = 1;
      negocio.justificativa = null;
      negocio.lancamento = moment().format("YYYY-MM-DD HH:mm:ss");
      negocio.criacao = moment().format("YYYY-MM-DD HH:mm:ss");
      negocio.alteracao = moment().format("YYYY-MM-DD HH:mm:ss");
      negocio.sincronizado = false;
      negocio.codpdv = sSinc.pdv.codpdv;
      negocio.Pdv = { ...sSinc.pdv };
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
      this.negocio = { ...negocio };
      await this.carregarChavesEstrangeiras();
      await this.salvar();
      await this.atualizarListagem();
      return negocio;
    },

    async isNegocioIntegro() {
      try {
        const neg = this.negocio;
        if (neg.codoperacao == null) {
          return false;
        }
        if (neg.codestoquelocal == null) {
          return false;
        }
        if (neg.codnaturezaoperacao == null) {
          return false;
        }
        if (neg.codnegociostatus == null) {
          return false;
        }
        if (neg.codpessoa == null) {
          return false;
        }
        if (neg.Pessoa == null) {
          return false;
        }
        return true;
      } catch (error) {
        return false;
      }
    },

    async consertarNegocioCorrompido() {
      if (this.negocio.codestoquelocal == null) {
        this.negocio.codestoquelocal = this.padrao.codestoquelocal;
      }
      if (this.negocio.codnaturezaoperacao == null) {
        this.negocio.codnaturezaoperacao = this.padrao.codnaturezaoperacao;
      }
      if (this.negocio.codoperacao == null) {
        this.negocio.codoperacao = this.padrao.codoperacao;
      }
      if (this.negocio.venda == null) {
        this.negocio.venda = this.padrao.venda;
      }
      if (this.negocio.financeiro == null) {
        this.negocio.financeiro = false;
      }
      if (this.negocio.codnegociostatus == null) {
        this.negocio.codnegociostatus = 1;
      }
      if (this.negocio.codpessoa == null) {
        this.negocio.codpessoa = this.padrao.codpessoa;
      }
      if (this.negocio.itens == null) {
        this.negocio.itens = [];
      }
      if (this.negocio.pagamentos == null) {
        this.negocio.pagamentos = [];
      }
      if (this.negocio.titulos == null) {
        this.negocio.titulos = [];
      }
      if (this.negocio.notas == null) {
        this.negocio.notas = [];
      }
      if (this.negocio.codpdv == null) {
        this.negocio.codpdv = sSinc.pdv.codpdv;
      }
      await this.carregarChavesEstrangeiras();
    },

    async carregarChavesEstrangeiras() {
      var naturezaoperacao = "Natureza Indefinida";
      var codoperacao = this.padrao.codoperacao;
      var venda = true;
      var operacao = "Saída";
      var negociostatus = "Aberto";
      var estoquelocal = "Local Indefinido";
      var fantasia = "Pessoa Indefinida";
      var fantasiavendedor = "";
      var financeiro = false;

      if (!this.negocio) {
        return false;
      }

      // natureza
      if (this.negocio.codnaturezaoperacao) {
        const nat = await db.naturezaOperacao.get(
          this.negocio.codnaturezaoperacao
        );
        if (nat) {
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
        if (loc) {
          estoquelocal = loc.estoquelocal;
        }
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
        if (vnd) {
          fantasiavendedor = vnd.fantasia;
        }
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
      } else {
        await this.atualizarListagem();
      }
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
        item.ordenacao = item.alteracao;
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
          alteracao: moment().format("YYYY-MM-DD HH:mm:ss"),
          ordenacao: moment().format("YYYY-MM-DD HH:mm:ss"),
          inativo: null,
        };
        try {
          if (this.negocio.Pessoa.desconto) {
            item.percentualdesconto = this.negocio.Pessoa.desconto;
          }
        } catch (error) {
          item.percentualdesconto = null;
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

      // confirma se tem alguma coisa pra consertar
      if (!(await this.isNegocioIntegro())) {
        await this.consertarNegocioCorrompido();
      }

      // salva no IndexedDB
      await this.salvar();
    },

    async itemAdicionarQuantidade(uuid, quantidade) {
      await this.recarregar();
      const item = this.negocio.itens.find(function (item) {
        return item.inativo === null && item.uuid == uuid;
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
      uuid,
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
        return item.inativo === null && item.uuid == uuid;
      });
      if (!item) {
        return false;
      }
      item.codprodutobarra = codprodutobarra;
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

    async itemInativar(uuid) {
      await this.recarregar();
      const inativar = this.negocio.itens.find(function (item) {
        return item.inativo === null && item.uuid == uuid;
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
      // Função auxiliar para garantir arredondamento preciso de ponto flutuante
      const roundToTwoDecimals = (num) => {
        if (num === null || num === undefined) return 0;
        // Utiliza a técnica de multiplicar/arredondar/dividir para precisão
        return parseFloat((Math.round(num * 100) / 100).toFixed(2));
      };

      await this.recarregar();

      const totalProdutos = this.negocio.valorprodutos;

      // 1. ORDENAÇÃO: Cria uma cópia e ordena os itens do menor para o maior valor do produto.
      // O maior item (ou um dos maiores) irá absorver o erro de arredondamento.
      const itensOrdenados = [...this.itensAtivos].sort(
        (a, b) => a.valorprodutos - b.valorprodutos
      );
      const ultimoIndex = itensOrdenados.length - 1;

      // 2. Inicialização de Valores e Saldos (Garantindo Absoluto e Arredondamento)
      const valoresTotais = {
        desconto: roundToTwoDecimals(Math.abs(valordesconto || 0)),
        frete: roundToTwoDecimals(Math.abs(valorfrete || 0)),
        seguro: roundToTwoDecimals(Math.abs(valorseguro || 0)),
        outras: roundToTwoDecimals(Math.abs(valoroutras || 0)),
      };

      const saldos = { ...valoresTotais };

      // 3. Cálculo dos Percentuais
      const percentuais = {
        desconto:
          totalProdutos > 0 ? valoresTotais.desconto / totalProdutos : 0,
        frete: totalProdutos > 0 ? valoresTotais.frete / totalProdutos : 0,
        seguro: totalProdutos > 0 ? valoresTotais.seguro / totalProdutos : 0,
        outras: totalProdutos > 0 ? valoresTotais.outras / totalProdutos : 0,
      };

      // --- Função Auxiliar para Rateio Robusto ---
      const aplicarRateio = (
        item,
        index,
        percentual,
        saldoKey,
        valorKey,
        percentualKey = null
      ) => {
        const saldoAtual = saldos[saldoKey];
        const totalRateavel = valoresTotais[saldoKey];

        if (totalRateavel === 0 || totalProdutos === 0) {
          item[valorKey] = 0;
          if (percentualKey) item[percentualKey] = 0;
          return;
        }

        if (index === ultimoIndex) {
          // NO ÚLTIMO ITEM: Atribui o saldo restante (o valor final exato).
          // Arredonda para garantir 0.00 se o saldo for um número negativo de precisão flutuante.
          item[valorKey] = roundToTwoDecimals(saldoAtual);
        } else {
          // Nos itens anteriores:
          // 1. Calcula o valor proporcional.
          let valorRateadoBruto = item.valorprodutos * percentual;

          // 2. Controla o Saldo: O valor rateado nunca pode ser maior que o saldo restante.
          let valorRateadoControlado = Math.min(valorRateadoBruto, saldoAtual);

          // 3. Arredonda o valor a ser atribuído.
          let valorAtribuido = roundToTwoDecimals(valorRateadoControlado);

          item[valorKey] = valorAtribuido;

          // 4. Subtrai o valor ARREDONDADO do saldo e arredonda o próprio saldo.
          saldos[saldoKey] -= valorAtribuido;
          saldos[saldoKey] = roundToTwoDecimals(saldos[saldoKey]);
        }

        if (percentualKey) {
          item[percentualKey] = roundToTwoDecimals(percentual * 100);
        }
      };

      // 4. Loop Principal (usando itens ordenados)
      for (let index = 0; index <= ultimoIndex; index++) {
        const item = itensOrdenados[index]; // Usa o item ORDENADO

        aplicarRateio(
          item,
          index,
          percentuais.desconto,
          "desconto",
          "valordesconto",
          "percentualdesconto"
        );
        aplicarRateio(item, index, percentuais.frete, "frete", "valorfrete");
        aplicarRateio(item, index, percentuais.seguro, "seguro", "valorseguro");
        aplicarRateio(item, index, percentuais.outras, "outras", "valoroutras");

        // Note: Se o 'item' é uma referência ao objeto original, ele será atualizado.
        // Se a referência foi perdida (dependendo da sua implementação de this.itensAtivos),
        // pode ser necessário atualizar a lista original após o loop.
        // Assumo que a atualização do 'item' altera a referência dentro de 'this.itensAtivos'.
        this.itemRecalcularValorTotal(item);
      }

      // 5. Opcional: Se 'itemRecalcularValorTotal' for o método que atualiza a lista principal,
      // esta etapa final de recálculo e salvamento é crucial.
      await this.recalcularValorTotal();
      this.salvar();
    },

    async informarPessoa(codpessoa, cpf) {
      // atribui o cliente/cpf no negocio
      this.negocio.codpessoa = codpessoa;
      if (codpessoa == 1) {
        this.negocio.cpf = cpf;
      } else {
        this.negocio.cpf = null;
      }

      // pega desconto do cliente antigo e do novo
      const descontoClienteAntigo = parseFloat(this.negocio.Pessoa.desconto);
      await this.carregarChavesEstrangeiras();
      const descontoClienteNovo = parseFloat(this.negocio.Pessoa.desconto);

      // se negocio nao estiver aberto retorna
      if (this.negocio.codnegociostatus != 1) {
        await this.salvar();
        return;
      }

      // se novo cliente tem desconto, aplica o desconto dele
      if (descontoClienteNovo > 0) {
        this.negocio.itens.forEach((item) => {
          item.percentualdesconto = descontoClienteNovo;
          this.itemRecalcularValorProdutos(item);
        });
      } else if (descontoClienteAntigo > 0) {
        this.negocio.itens.forEach((item) => {
          // se o desconto dos itens era o desconto do cliente antigo, retira o desconto
          if (item.percentualdesconto == descontoClienteAntigo) {
            item.percentualdesconto = null;
            this.itemRecalcularValorProdutos(item);
          }
        });
      }
      await this.salvar();
    },

    async informarNatureza(codestoquelocal, codnaturezaoperacao, observacoes) {
      await this.recarregar();
      this.negocio.codestoquelocal = codestoquelocal;
      this.negocio.codnaturezaoperacao = codnaturezaoperacao;
      this.negocio.observacoes = observacoes;
      await this.carregarChavesEstrangeiras();
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
      let retorno = false;
      try {
        const ret = await sSinc.putNegocio(negocio);
        if (ret) {
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
          retorno = true;
        }
      } catch (error) {
        console.log(error);
      }
      await this.atualizarListagem();
      return retorno;
    },

    async recarregarDaApi(codOrUuid) {
      try {
        const ret = await sSinc.getNegocio(codOrUuid);
        if (!ret.codnegocio) {
          return false;
        }
        await this.atualizarNegocioPeloObjeto(ret);
        return true;
      } catch (error) {
        console.log(error);
        return false;
      }
    },

    async apropriar(codOrUuid) {
      try {
        const ret = await sSinc.postApropriar(codOrUuid);
        if (!ret.codnegocio) {
          return false;
        }
        await this.atualizarNegocioPeloObjeto(ret);
        return true;
      } catch (error) {
        console.log(error);
        return false;
      }
    },

    async atualizarNegocioPeloObjeto(neg) {
      this.negocio = { ...neg };
      db.negocio.put(neg);
      await this.atualizarListagem();
    },

    async adicionarPagamento(
      codformapagamento,
      tipo,
      codtitulo,
      valorpagamento,
      valorjuros,
      valortroco,
      codpessoa,
      bandeira,
      autorizacao,
      parcelas,
      valorparcela,
      dias
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
        codtitulo: codtitulo,
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
        dias: dias,
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
          timeout: 3000, // 3 segundos
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
            timeout: 1000, // 1 segundo
            actions: [{ icon: "close", color: "white" }],
          });
          await this.atualizarNegocioPeloObjeto(ret);
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
          timeout: 3000, // 3 segundos
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
            timeout: 1000, // 1 segundo
            actions: [{ icon: "close", color: "white" }],
          });
          await this.atualizarNegocioPeloObjeto(ret);
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
          timeout: 3000, // 3 segundos
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
            timeout: 1000, // 1 segundo
            actions: [{ icon: "close", color: "white" }],
          });
          await this.atualizarNegocioPeloObjeto(ret);
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
          timeout: 3000, // 3 segundos
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
            timeout: 1000, // 1 segundo
            actions: [{ icon: "close", color: "white" }],
          });
          await this.atualizarNegocioPeloObjeto(ret);
          return ret.PagarMePedidoS[0];
        }
      } catch (error) {
        console.log(error);
      }
    },

    async criarSaurusPedido(
      codsauruspos,
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
            "Impossível criar Cobrança Saurus/Safra Pay em um negócio não sincronizado com o servidor!",
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
      try {
        const descricao = "Negocio " + this.negocio.codnegocio;
        const ret = await sSinc.criarSaurusPedido(
          this.negocio.codnegocio,
          this.negocio.codpessoa,
          codsauruspos,
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
            message: "Cobrança Saurus/Safra Pay Criada!",
            timeout: 1000, // 1 segundo
            actions: [{ icon: "close", color: "white" }],
          });
          await this.atualizarNegocioPeloObjeto(ret);
          return ret.SaurusPedidoS[0];
        }
      } catch (error) {
        console.log(error);
      }
      return false;
    },

    async uploadAnexo(pasta, ratio, anexoBase64) {
      try {
        // asdasd();
        const ret = await sSinc.uploadAnexo(
          this.negocio.codnegocio,
          pasta,
          ratio,
          anexoBase64
        );
        if (!ret) {
          return false;
        }
        Notify.create({
          type: "positive",
          message: "Anexo Adicionado!",
          timeout: 1000, // 1 segundo
          actions: [{ icon: "close", color: "white" }],
        });
        this.negocio.anexos = ret.data;
        return true;
      } catch (error) {
        console.log(error);
        return false;
      }
    },

    async deleteAnexo(pasta, anexo) {
      try {
        // asdasd();
        const data = await sSinc.deleteAnexo(
          this.negocio.codnegocio,
          pasta,
          anexo
        );
        if (!data) {
          return false;
        }
        Notify.create({
          type: "positive",
          message: "Anexo Excluído!",
          timeout: 1000, // 1 segundo
          actions: [{ icon: "close", color: "white" }],
        });
        this.negocio.anexos = data;
        return true;
      } catch (error) {
        console.log(error);
        return false;
      }
    },

    async unificarComanda(codnegociocomanda) {
      if (!this.negocio.sincronizado) {
        Notify.create({
          type: "negative",
          message:
            "Impossível ler Comandas em um negócio não sincronizado com o servidor!",
          timeout: 3000, // 3 segundos
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
          timeout: 1000, // 1 segundo
          actions: [{ icon: "close", color: "white" }],
        });
        if (ret.negocio.codnegocio) {
          await this.atualizarNegocioPeloObjeto(ret.negocio);
        }
        if (ret.comanda.codnegocio) {
          db.negocio.put(ret.comanda);
        }
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

    async buscarVale(codtitulo) {
      const vale = await sSinc.buscarVale(codtitulo);
      return vale;
    },
  },
});
