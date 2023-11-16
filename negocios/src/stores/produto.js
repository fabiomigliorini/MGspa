import { defineStore } from "pinia";
import { api } from "boot/axios";
import { db } from "boot/db";

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
        .startsWithIgnoreCase(palavras[0]);

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
      var arrProdutos = await colProdutos.sortBy("preco");
      this.resultadoPesquisa = arrProdutos.slice(0, 50);
    },
    async buscarBarras(barras) {
      let ret = await db.produtos.where({ barras }).toArray();
      if (ret.length >= 1) {
        return ret;
      }
      console.log(barras.length);
      if (barras.length != 6) {
        return ret;
      }
      const codproduto = parseInt(barras);
      console.log(codproduto);
      if (isNaN(codproduto)) {
        return ret;
      }
      ret = await db.produtos
        .where({ codproduto: codproduto })
        .filter((produto) => produto.quantidade == null)
        .toArray();
      console.log(ret);
      return ret;
    },
  },
  persist: true,
});
