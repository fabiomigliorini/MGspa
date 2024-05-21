import { defineStore } from "pinia";
import { api } from "boot/axios";
import { db } from "boot/db";
import { uid } from "quasar";
import { Platform } from "quasar";
import { Notify } from "quasar";
import { usuarioStore } from "./usuario";

const sUsuario = usuarioStore();

export const sincronizacaoStore = defineStore("sincronizacao", {
  persist: {
    paths: ["ultimaSincronizacao", "pdv"],
  },

  state: () => ({
    sincronizacao: {
      config: true,
      pessoa: true,
      produto: true,
      completa: true,
    },
    ultimaSincronizacao: {
      impressora: null,
      formaPagamento: null,
      estoqueLocal: null,
      naturezaOperacao: null,
      pessoa: null,
      produto: null,
      prancheta: null,
      completa: null,
    },
    labelSincronizacao: "",
    pdv: {
      // envia para api
      uuid: null,
      latitude: null,
      longitude: null,
      precisao: null,
      plataforma: null,
      navegador: null,
      versaonavegador: null,
      desktop: null,
      // backend retorna
      autorizado: false,
      apelido: null,
      codfilial: null,
      filial: null,
      codpdv: null,
    },
    importacao: {
      totalRegistros: null,
      totalSincronizados: null,
      progresso: 0,
      rodando: false,
      dialog: false,
      requisicoes: null,
      tempoTotal: null,
      maxRequisicoes: 1000,
      limiteRequisicao: 3000,
    },
  }),

  actions: {
    async dispositivo() {
      if (!this.pdv.uuid) {
        this.pdv.uuid = uid();
      }

      try {
        const pos = await new Promise((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject);
        });
        this.pdv.latitude = pos.coords.latitude;
        this.pdv.longitude = pos.coords.longitude;
        this.pdv.precisao = pos.coords.accuracy;
      } catch (error) {
        Notify.create({
          type: "negative",
          message: error.message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        var audio = new Audio("erro.mp3");
        audio.play();
      }

      const plat = Platform.is;
      this.pdv.plataforma = plat.platform;
      this.pdv.navegador = plat.name;
      this.pdv.versaonavegador = plat.version;
      this.pdv.desktop = plat.desktop ? 1 : 0;
      const params = {
        uuid: this.pdv.uuid,
        latitude: this.pdv.latitude,
        longitude: this.pdv.longitude,
        precisao: this.pdv.precisao,
        desktop: this.pdv.desktop,
        navegador: this.pdv.navegador,
        versaonavegador: this.pdv.versaonavegador,
        plataforma: this.pdv.plataforma,
      };
      let { data } = await api.put("/api/v1/pdv/dispositivo", params);
      this.pdv.autorizado = data.data.autorizado;
      this.pdv.apelido = data.data.apelido;
      this.pdv.codfilial = data.data.codfilial;
      this.pdv.filial = data.data.filial;
      this.pdv.codpdv = data.data.codpdv;
    },

    async sincronizar() {
      // verifica se PDV pode acessar API
      await this.dispositivo();
      if (!this.pdv.autorizado) {
        Notify.create({
          type: "negative",
          message:
            "Solicite autorização para o dispositivo UUID: " + this.pdv.uuid,
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
        var audio = new Audio("erro.mp3");
        audio.play();
        return;
      }

      // verifica se Está logado
      await sUsuario.getUsuario();
      if (!sUsuario.token.access_token) {
        Notify.create({
          type: "negative",
          message: "Antes de sincronizar você deve fazer Login!",
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        var audio = new Audio("erro.mp3");
        audio.play();
        return;
      }

      // mostra janela de progresso
      this.importacao.rodando = true;

      // roda as importacoes
      try {
        if (this.sincronizacao.config) {
          await this.sincronizarImpressora();
          await this.sincronizarFormaPagamento();
          await this.sincronizarEstoqueLocal();
          await this.sincronizarNaturezaOperacao();
        }
        if (this.sincronizacao.pessoa) {
          await this.sincronizarPessoa();
        }
        if (this.sincronizacao.produto) {
          await this.sincronizarProduto();
          await this.sincronizarPrancheta();
        }
      } catch (error) {
        console.log(error);
      }

      // esconde janela de progresso
      this.inicializaVars();
    },

    async inicializaVars() {
      this.inicializaProgresso(null);
      this.importacao.dialog = false;
      this.importacao.rodando = false;
      this.ultimaSincronizacao.completa = [
        this.ultimaSincronizacao.impressora,
        this.ultimaSincronizacao.formaPagamento,
        this.ultimaSincronizacao.estoqueLocal,
        this.ultimaSincronizacao.naturezaOperacao,
        this.ultimaSincronizacao.pessoa,
        this.ultimaSincronizacao.produto,
        this.ultimaSincronizacao.prancheta,
      ].sort()[0];
    },

    async abortarSincronizacao() {
      this.importacao.rodando = false;
      this.importacao.dialog = false;
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
      if (!this.importacao.rodando) {
        return;
      }

      // inicializa progresso
      this.inicializaProgresso("Impressoras");
      let sincronizado = null;

      try {
        // busca registros na ApI
        let { data } = await api.get("/api/v1/pdv/impressora", {
          params: { pdv: this.pdv.uuid },
        });

        // insere dados no banco local indexeddb
        await db.impressora.bulkPut(data);

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado;
        db.impressora.where("sincronizado").below(sincronizado).delete();

        //registra data de Sincronizacao
        this.ultimaSincronizacao.impressora = sincronizado;
      } catch (error) {
        console.log(error);
        console.log("Impossível sincronizar Impressoras");
        Notify.create({
          type: "negative",
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
      }
    },

    async sincronizarFormaPagamento() {
      if (!this.importacao.rodando) {
        return;
      }

      // inicializa progresso
      this.inicializaProgresso("Formas de Pagamento");
      let sincronizado = null;

      try {
        // busca registros na ApI
        let { data } = await api.get("/api/v1/pdv/forma-pagamento", {
          params: { pdv: this.pdv.uuid },
        });

        // insere dados no banco local indexeddb
        await db.formaPagamento.bulkPut(data);

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado;
        db.formaPagamento.where("sincronizado").below(sincronizado).delete();

        //registra data de Sincronizacao
        this.ultimaSincronizacao.formaPagamento = sincronizado;
      } catch (error) {
        console.log(error);
        console.log("Impossível sincronizar Formas de Pagamento");
        Notify.create({
          type: "negative",
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
      }
    },

    async sincronizarEstoqueLocal() {
      if (!this.importacao.rodando) {
        return;
      }

      // inicializa progresso
      this.inicializaProgresso("Locais de Estoque");
      let sincronizado = null;

      try {
        // busca registros na ApI
        let { data } = await api.get("/api/v1/pdv/estoque-local", {
          params: { pdv: this.pdv.uuid },
        });

        // insere dados no banco local indexeddb
        await db.estoqueLocal.bulkPut(data);

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado;
        db.estoqueLocal.where("sincronizado").below(sincronizado).delete();

        //registra data de Sincronizacao
        this.ultimaSincronizacao.estoqueLocal = sincronizado;
      } catch (error) {
        console.log(error);
        console.log("Impossível sincronizar Locais de Estoque");
        Notify.create({
          type: "negative",
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
      }
    },

    async sincronizarNaturezaOperacao() {
      if (!this.importacao.rodando) {
        return;
      }

      // inicializa progresso
      this.inicializaProgresso("Natureza De Operação");
      let sincronizado = null;

      try {
        // busca registros na ApI
        let { data } = await api.get("/api/v1/pdv/natureza-operacao", {
          params: { pdv: this.pdv.uuid },
        });

        // insere dados no banco local indexeddb
        await db.naturezaOperacao.bulkPut(data);

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado;
        db.naturezaOperacao.where("sincronizado").below(sincronizado).delete();

        //registra data de Sincronizacao
        this.ultimaSincronizacao.naturezaOperacao = sincronizado;
      } catch (error) {
        console.log(error);
        console.log("Impossível sincronizar Natureza Operacao");
        Notify.create({
          type: "negative",
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
      }
    },

    async sincronizarPessoa() {
      if (!this.importacao.rodando) {
        return;
      }

      // inicializa progresso
      this.inicializaProgresso("Pessoas");

      // descobre o total de registros pra sincronizar
      try {
        let { data } = await api.get("/api/v1/pdv/pessoa-count", {
          params: { pdv: this.pdv.uuid },
        });
        this.importacao.totalRegistros = data.count;
        this.importacao.limiteRequisicao = Math.round(
          this.importacao.totalRegistros / 10
        );
      } catch (error) {
        console.log(error);
        console.log("Impossível acessar API");
        Notify.create({
          type: "negative",
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
      }

      let sincronizado = null;
      let inicio = performance.now();
      let codpessoa = 0;

      do {
        // busca dados na api
        var { data } = await api.get("/api/v1/pdv/pessoa", {
          params: {
            pdv: this.pdv.uuid,
            codpessoa: codpessoa,
            limite: this.importacao.limiteRequisicao,
          },
        });

        // se nao veio nada interrompe o loop
        if (data.length == 0) {
          break;
        }

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
        this.importacao.progresso = Math.round(
          (this.importacao.totalSincronizados * 100) /
            this.importacao.totalRegistros
        );
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
      if (this.importacao.rodando) {
        db.pessoa.where("sincronizado").below(sincronizado).delete();
        this.ultimaSincronizacao.pessoa = sincronizado;
      }
    },

    async sincronizarProduto() {
      if (!this.importacao.rodando) {
        return;
      }

      // inicializa progresso
      this.inicializaProgresso("Produtos");

      // descobre o total de registros pra sincronizar
      try {
        let { data } = await api.get("/api/v1/pdv/produto-count", {
          params: { pdv: this.pdv.uuid },
        });
        this.importacao.totalRegistros = data.count;
        this.importacao.limiteRequisicao = Math.round(
          this.importacao.totalRegistros / 50
        );
      } catch (error) {
        console.log(error);
        console.log("Impossível acessar API");
        Notify.create({
          type: "negative",
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
      }

      let sincronizado = null;
      let inicio = performance.now();
      let codprodutobarra = 0;

      do {
        // busca dados na api
        var { data } = await api.get("/api/v1/pdv/produto", {
          params: {
            pdv: this.pdv.uuid,
            codprodutobarra: codprodutobarra,
            limite: this.importacao.limiteRequisicao,
          },
        });

        // se nao veio nada interrompe o loop
        if (data.length == 0) {
          break;
        }

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
        this.importacao.progresso = Math.round(
          (this.importacao.totalSincronizados * 100) /
            this.importacao.totalRegistros
        );
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
      if (this.importacao.rodando) {
        db.produto.where("sincronizado").below(sincronizado).delete();
        this.ultimaSincronizacao.produto = sincronizado;
      }
    },

    async sincronizarPrancheta() {
      if (!this.importacao.rodando) {
        return;
      }

      // inicializa progresso
      this.inicializaProgresso("Prancheta");
      let sincronizado = null;

      try {
        // busca registros na ApI
        let { data } = await api.get("/api/v1/pdv/prancheta", {
          params: { pdv: this.pdv.uuid },
        });

        // insere dados no banco local indexeddb
        await db.prancheta.bulkPut(data);

        // exclui registros que nao vieram na importacao
        sincronizado = data[0].sincronizado;
        db.prancheta.where("sincronizado").below(sincronizado).delete();

        //registra data de Sincronizacao
        this.ultimaSincronizacao.prancheta = sincronizado;
      } catch (error) {
        console.log(error);
        console.log("Impossível sincronizar Prancheta");
        Notify.create({
          type: "negative",
          message: error.response.data.message,
          timeout: 0, // 20 minutos
          actions: [{ icon: "close", color: "white" }],
        });
      }
    },

    async putNegocio(negocio) {
      const params = {
        pdv: this.pdv.uuid,
        negocio: negocio,
      };
      try {
        const { data } = await api.put("/api/v1/pdv/negocio", params);
        return data.data;
      } catch (error) {
        console.log(error);
        let message = "";
        switch (error.code) {
          case "ERR_NETWORK":
            message = "Erro ao comunicar com Servidor. Operando Offline!";
            break;

          default:
            message = error?.response?.data?.message;
            if (!message) {
              message = error?.message;
            }
            break;
        }
        Notify.create({
          type: "negative",
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },

    async getNegocio(codOrUuid) {
      try {
        const { data } = await api.get("/api/v1/pdv/negocio/" + codOrUuid, {
          params: {
            pdv: this.pdv.uuid,
          },
        });
        return data.data;
      } catch (error) {
        console.log(error);
        var message = error?.response?.data?.message;
        if (!message) {
          message = error?.message;
        }
        Notify.create({
          type: "negative",
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },

    async fecharNegocio(codnegocio) {
      try {
        const { data } = await api.post(
          "/api/v1/pdv/negocio/" + codnegocio + "/fechar",
          {
            pdv: this.pdv.uuid,
          }
        );
        return data.data;
      } catch (error) {
        console.log(error);
        var message = error?.response?.data?.message;
        if (!message) {
          message = error?.message;
        }
        Notify.create({
          type: "negative",
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },

    async cancelarNegocio(codnegocio, justificativa) {
      try {
        const { data } = await api.delete("/api/v1/pdv/negocio/" + codnegocio, {
          params: {
            pdv: this.pdv.uuid,
            justificativa: justificativa,
          },
        });
        return data.data;
      } catch (error) {
        console.log(error);
        var message = error?.response?.data?.message;
        if (!message) {
          message = error?.message;
        }
        Notify.create({
          type: "negative",
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },

    async criarPixCob(valor, codnegocio) {
      try {
        const { data } = await api.post("/api/v1/pdv/pix/cob/", {
          pdv: this.pdv.uuid,
          valor: valor,
          codnegocio: codnegocio,
        });
        return data.data;
      } catch (error) {
        console.log(error);
        var message = error?.response?.data?.message;
        if (!message) {
          message = error?.message;
        }
        Notify.create({
          type: "negative",
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },

    async criarPagarMePedido(
      codnegocio,
      codpessoa,
      codpagarmepos,
      valor,
      valorparcela,
      valorjuros,
      tipo,
      parcelas,
      jurosloja,
      descricao
    ) {
      try {
        const { data } = await api.post("/api/v1/pdv/pagar-me/pedido/", {
          pdv: this.pdv.uuid,
          codnegocio,
          codpessoa,
          codpagarmepos,
          valor,
          valorparcela,
          valorjuros,
          tipo,
          parcelas,
          jurosloja,
          descricao,
        });
        return data.data;
      } catch (error) {
        console.log(error);
        var message = error?.response?.data?.message;
        if (!message) {
          message = error?.message;
        }
        Notify.create({
          type: "negative",
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },

    async unificarComanda(codnegocio, codnegociocomanda) {
      try {
        const { data } = await api.post(
          `/api/v1/pdv/negocio/${codnegocio}/unificar/${codnegociocomanda}`,
          {
            pdv: this.pdv.uuid,
          }
        );
        return data;
      } catch (error) {
        console.log(error);
        var message = error?.response?.data?.message;
        if (!message) {
          message = error?.message;
        }
        Notify.create({
          type: "negative",
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return false;
      }
    },

    async negocioDevolucao(codnegocio, arrDevolucao) {
      try {
        const ret = await api.post(
          "/api/v1/pdv/negocio/" + codnegocio + "/devolucao",
          {
            pdv: this.pdv.uuid,
            devolucao: arrDevolucao,
          }
        );
        return ret;
      } catch (error) {
        console.log(error);
        Notify.create({
          type: "negative",
          message: error.response.data.message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
      }
    },

    async buscarVale(codtitulo) {
      try {
        const ret = await api.get("/api/v1/pdv/vale/" + codtitulo);
        return ret;
      } catch (error) {
        console.log(error);
        Notify.create({
          type: "negative",
          message: error.response.data.message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        return null;
      }
    },
  },
});
