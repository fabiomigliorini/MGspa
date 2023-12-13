import { defineStore } from "pinia";
import { api } from "boot/axios";
import { db } from "boot/db";
import { Notify, LoadingBar } from "quasar";

export const produtoStore = defineStore("produtos", {
  state: () => ({
    resultadoPesquisa: [],
    textoPesquisa: null,
    ultimaSincronizacaoProdutos: null,
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
      // inicializa progresso
      this.importacao.progresso = 0;
      this.importacao.totalRegistros = 0;
      this.importacao.totalSincronizados = 0;
      this.importacao.requisicoes = 0;
      this.importacao.tempoTotal = 0;
      this.importacao.rodando = true;

      // descobre o total de registros pra sincronizar
      try {
        let { data } = await api.get("/api/v1/produto/listagem-pdv-count");
        this.importacao.totalRegistros = data.count;
        this.importacao.limiteRequisicao = Math.round(
          this.importacao.totalRegistros / 100
        );
      } catch (error) {
        console.log(error);
        console.log("Impossível acessar API");
      }

      // mostra janela de progresso
      this.importacao.sincronizacao = true;

      let sincronizado = null;
      let inicio = performance.now();
      let codprodutobarra = 0;

      do {
        // busca dados na api
        var { data } = await api.get("/api/v1/produto/listagem-pdv", {
          params: {
            codprodutobarra: codprodutobarra,
            limite: this.importacao.limiteRequisicao,
          },
        });
        // incrementa numero de requisicoes
        this.importacao.requisicoes++;

        // insere dados no banco local indexeddb
        try {
          await db.produtos.bulkPut(data);
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
      db.produtos.where("sincronizado").below(sincronizado).delete();
      this.ultimaSincronizacaoProdutos = sincronizado;

      // esconde janela de progresso
      this.importacao.rodando = false;
    },
    async pesquisar() {
      LoadingBar.start();

      // verifica se tem texto de busca
      const palavras = this.textoPesquisa.trim().split(" ");
      if (palavras.length == 0) {
        return;
      }
      if (palavras[0].length == 0) {
        return;
      }

      // Busca produtos baseados na primeira palavra de pesquisa
      var colProdutos = await db.produtos
        .where("palavras")
        .startsWithIgnoreCase(palavras[0])
        .and((p) => p.inativo == null);

      // se estiver buscando por mais de uma palavra
      if (palavras.length > 1) {
        // monta expressoes regulares
        var regexes = [];
        for (let i = 1; i < palavras.length; i++) {
          regexes.push(new RegExp(".*" + palavras[i] + ".*", "i"));
        }

        // percorre todos registros filtrando pelas expressoes regulares
        const iMax = regexes.length;
        colProdutos = await colProdutos.and(function (produto) {
          for (let i = 0; i < iMax; i++) {
            if (!regexes[i].test(produto.produto)) {
              return false;
            }
          }
          return true;
        });
      }

      // transforma colecao de produtos em array
      // var arrProdutos = await colProdutos.sortBy("[quantidade]");
      var arrProdutos = await colProdutos.toArray();
      arrProdutos = arrProdutos.sort((a, b) => {
        if (a.produto > b.produto) {
          return 1;
        } else {
          return -1;
        }
      });
      LoadingBar.stop();
      if (arrProdutos.length > 500) {
        Notify.create({
          type: "negative",
          message:
            "Pesquisa encontrou " +
            arrProdutos.length +
            " itens. Mostrando apenas os 500 primeiros. Refine a sua pesquisa.",
        });
        arrProdutos = arrProdutos.slice(0, 1000);
      } else if (arrProdutos.length == 0) {
        Notify.create({
          type: "negative",
          message: "Nenhum item localizado. Melhore sua pesquisa.",
        });
      } else {
        Notify.create({
          type: "positive",
          message: "Pesquisa encontrou " + arrProdutos.length + " itens.",
        });
      }
      this.resultadoPesquisa = arrProdutos;
      // this.resultadoPesquisa = arrProdutos;
    },
    async buscarBarras(barras) {
      let ret = await db.produtos.where({ barras }).toArray();
      if (ret.length >= 1) {
        return ret;
      }
      if (barras.length != 6) {
        return ret;
      }
      const codproduto = parseInt(barras);
      if (isNaN(codproduto)) {
        return ret;
      }
      ret = await db.produtos
        .where({ codproduto: codproduto })
        .filter((produto) => produto.quantidade == null)
        .toArray();
      return ret;
    },
  },
  persist: true,
});
