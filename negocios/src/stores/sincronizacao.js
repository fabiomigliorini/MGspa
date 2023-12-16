import { defineStore } from "pinia";
import { api } from "boot/axios";
import { db } from "boot/db";

export const sincronizacaoStore = defineStore("sincronizacao", {
  persist: true,

  state: () => ({
    resultadoPesquisa: [],
    textoPesquisa: null,
    dialogPesquisa: false,
    ultimaSincronizacaoFormaPagamento: null,
    ultimaSincronizacaoEstoqueLocal: null,
    ultimaSincronizacaoNaturezaOperacao: null,
    ultimaSincronizacaoPessoa: null,
    ultimaSincronizacaoProduto: null,
    labelSincronizacao: "",
    importacao: {
      totalRegistros: null,
      totalSincronizados: null,
      progresso: 0,
      rodando: false,
      requisicoes: null,
      maxRequisicoes: 1000,
      limiteRequisicao: 3000,
      tempoTotal: null,
    },
  }),

  actions: {
    async sincronizar() {
      // mostra janela de progresso
      this.importacao.rodando = true;

      // roda as importacoes
      await this.sincronizarImpressora();
      await this.sincronizarFormaPagamento();
      await this.sincronizarEstoqueLocal();
      await this.sincronizarNaturezaOperacao();
      await this.sincronizarPessoa();
      await this.sincronizarProduto();

      // esconde janela de progresso
      this.importacao.rodando = false;
    },

    async abortarSincronizacao() {
      this.importacao.rodando = false;
    },

    inicializaProgresso(label) {
      this.importacao.progresso = 0;
      this.importacao.totalRegistros = 0;
      this.importacao.totalSincronizados = 0;
      this.importacao.requisicoes = 0;
      this.importacao.tempoTotal = 0;
      this.labelSincronizacao = label;
    },

    async sincronizarImpressora() {
      // inicializa progresso
      this.inicializaProgresso("Impressoras");
      let sincronizado = null;

      try {
        // busca registros na ApI
        let { data } = await api.get("/api/v1/pdv/impressora");

        // insere dados no banco local indexeddb
        await db.impressora.bulkPut(data);

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado;
        db.impressora.where("sincronizado").below(sincronizado).delete();

        //registra data de Sincronizacao
        this.impressora = sincronizado;
      } catch (error) {
        console.log(error);
        console.log("Impossível sincronizar Impressoras");
      }
    },

    async sincronizarFormaPagamento() {
      // inicializa progresso
      this.inicializaProgresso("Formas de Pagamento");
      let sincronizado = null;

      try {
        // busca registros na ApI
        let { data } = await api.get("/api/v1/pdv/forma-pagamento");

        // insere dados no banco local indexeddb
        await db.formaPagamento.bulkPut(data);

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado;
        db.formaPagamento.where("sincronizado").below(sincronizado).delete();

        //registra data de Sincronizacao
        this.ultimaSincronizacaoFormaPagamento = sincronizado;
      } catch (error) {
        console.log(error);
        console.log("Impossível sincronizar Formas de Pagamento");
      }
    },

    async sincronizarEstoqueLocal() {
      // inicializa progresso
      this.inicializaProgresso("Locais de Estoque");
      let sincronizado = null;

      try {
        // busca registros na ApI
        let { data } = await api.get("/api/v1/pdv/estoque-local");

        // insere dados no banco local indexeddb
        await db.estoqueLocal.bulkPut(data);

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado;
        db.estoqueLocal.where("sincronizado").below(sincronizado).delete();

        //registra data de Sincronizacao
        this.ultimaSincronizacaoEstoqueLocal = sincronizado;
      } catch (error) {
        console.log(error);
        console.log("Impossível sincronizar Locais de Estoque");
      }
    },

    async sincronizarNaturezaOperacao() {
      // inicializa progresso
      this.inicializaProgresso("Natureza De Operação");
      let sincronizado = null;

      try {
        // busca registros na ApI
        let { data } = await api.get("/api/v1/pdv/natureza-operacao");

        // insere dados no banco local indexeddb
        await db.naturezaOperacao.bulkPut(data);

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado;
        db.naturezaOperacao.where("sincronizado").below(sincronizado).delete();

        //registra data de Sincronizacao
        this.ultimaSincronizacaoNaturezaOperacao = sincronizado;
      } catch (error) {
        console.log(error);
        console.log("Impossível sincronizar Natureza Operacao");
      }
    },

    async sincronizarPessoa() {
      // inicializa progresso
      this.inicializaProgresso("Pessoas");

      // descobre o total de registros pra sincronizar
      try {
        let { data } = await api.get("/api/v1/pdv/pessoa-count");
        this.importacao.totalRegistros = data.count;
        this.importacao.limiteRequisicao = Math.round(
          this.importacao.totalRegistros / 10
        );
      } catch (error) {
        console.log(error);
        console.log("Impossível acessar API");
      }

      let sincronizado = null;
      let inicio = performance.now();
      let codpessoa = 0;

      do {
        // busca dados na api
        var { data } = await api.get("/api/v1/pdv/pessoa", {
          params: {
            codpessoa: codpessoa,
            limite: this.importacao.limiteRequisicao,
          },
        });
        // incrementa numero de requisicoes
        this.importacao.requisicoes++;

        // insere dados no banco local indexeddb
        try {
          await db.pessoa.bulkPut(data);
        } catch (error) {
          console.log(error.stack || error);
        }

        if (sincronizado == null) {
          sincronizado = data[0].sincronizado;
        }

        // busca codigo do ultimo registro
        codpessoa = data.slice(-1)[0].codpessoa;

        //monta status de progresso
        this.importacao.totalSincronizados += data.length;
        this.importacao.progresso =
          this.importacao.totalSincronizados / this.importacao.totalRegistros;
        this.importacao.tempoTotal = Math.round(
          (performance.now() - inicio) / 1000
        );

        // loop enquanto nao tiver buscado menos registros que o limite
      } while (
        data.length >= this.importacao.limiteRequisicao &&
        this.importacao.requisicoes <= this.importacao.maxRequisicoes &&
        this.importacao.rodando
      );

      // exclui registros que nao vieram na importacao
      db.pessoa.where("sincronizado").below(sincronizado).delete();
      this.ultimaSincronizacaoPessoa = sincronizado;
    },

    async sincronizarProduto() {
      // inicializa progresso
      this.inicializaProgresso("Produtos");

      // descobre o total de registros pra sincronizar
      try {
        let { data } = await api.get("/api/v1/pdv/produto-count");
        this.importacao.totalRegistros = data.count;
        this.importacao.limiteRequisicao = Math.round(
          this.importacao.totalRegistros / 50
        );
      } catch (error) {
        console.log(error);
        console.log("Impossível acessar API");
      }

      let sincronizado = null;
      let inicio = performance.now();
      let codprodutobarra = 0;

      do {
        // busca dados na api
        var { data } = await api.get("/api/v1/pdv/produto", {
          params: {
            codprodutobarra: codprodutobarra,
            limite: this.importacao.limiteRequisicao,
          },
        });
        // incrementa numero de requisicoes
        this.importacao.requisicoes++;

        // insere dados no banco local indexeddb
        try {
          await db.produto.bulkPut(data);
        } catch (error) {
          console.log(error.stack || error);
        }

        if (sincronizado == null) {
          sincronizado = data[0].sincronizado;
        }

        // busca codigo do ultimo registro
        codprodutobarra = data.slice(-1)[0].codprodutobarra;

        //monta status de progresso
        this.importacao.totalSincronizados += data.length;
        this.importacao.progresso =
          this.importacao.totalSincronizados / this.importacao.totalRegistros;
        this.importacao.tempoTotal = Math.round(
          (performance.now() - inicio) / 1000
        );

        // loop enquanto nao tiver buscado menos registros que o limite
      } while (
        data.length >= this.importacao.limiteRequisicao &&
        this.importacao.requisicoes <= this.importacao.maxRequisicoes &&
        this.importacao.rodando
      );

      // exclui registros que nao vieram na importacao
      db.produto.where("sincronizado").below(sincronizado).delete();
      this.ultimaSincronizacaoProduto = sincronizado;
    },
  },
});
