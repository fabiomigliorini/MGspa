import { defineStore } from "pinia";
import { db } from "boot/db";
import { Notify, LoadingBar } from "quasar";

export const produtoStore = defineStore("produto", {
  persist: true,

  state: () => ({
    resultadoPesquisa: [],
    textoPesquisa: null,
    dialogPesquisa: false,
    sortPesquisa: "Alfabética",
  }),

  actions: {
    urlImagem(codimagem) {
      // return "https://fakeimg.pl/300x300";
      if (codimagem == null) {
        return "https://sistema.mgpapelaria.com.br/MGLara/public/imagens/semimagem.jpg";
      }
      return (
        "https://sistema.mgpapelaria.com.br/MGLara/public/imagens/" +
        codimagem +
        ".jpg"
      );
    },

    async pesquisar() {
      // verifica se tem texto de busca
      const texto = this.textoPesquisa.trim();
      if (texto.length == 0) {
        return;
      }

      // monta array de palavras pra buscas
      LoadingBar.start();
      const palavras = texto.split(" ");

      // Busca produto baseados na primeira palavra de pesquisa
      var colProdutos = await db.produto
        .where("buscaArr")
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
            if (!regexes[i].test(produto.busca)) {
              return false;
            }
          }
          return true;
        });
      }

      // transforma colecao de produto em array
      var arrProdutos = await colProdutos.toArray();
      switch (this.sortPesquisa) {
        case "Preço":
          arrProdutos = arrProdutos.sort((a, b) => {
            if (a.preco > b.preco) {
              return 1;
            } else if (a.preco == b.preco) {
              if (a.produto > b.produto) {
                return 1;
              } else {
                return -1;
              }
            } else {
              return -1;
            }
          });
          break;

        case "Código":
          arrProdutos = arrProdutos.sort((a, b) => {
            if (a.codproduto > b.codproduto) {
              return 1;
            } else if (a.codproduto == b.codproduto) {
              if (a.produto > b.produto) {
                return 1;
              } else {
                return -1;
              }
            } else {
              return -1;
            }
          });
          break;

        case "Barras":
          arrProdutos = arrProdutos.sort((a, b) => {
            if (a.barras > b.barras) {
              return 1;
            } else if (a.barras == b.barras) {
              if (a.produto > b.produto) {
                return 1;
              } else {
                return -1;
              }
            } else {
              return -1;
            }
          });
          break;

        case "Alfabética":
        default:
          arrProdutos = arrProdutos.sort((a, b) => {
            if (a.produto > b.produto) {
              return 1;
            } else {
              return -1;
            }
          });
          break;
      }
      LoadingBar.stop();
      if (arrProdutos.length > 200) {
        Notify.create({
          type: "negative",
          message:
            "Pesquisa encontrou " +
            arrProdutos.length +
            " itens. Mostrando apenas os 200 primeiros. Refine a sua pesquisa.",
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
        arrProdutos = arrProdutos.slice(0, 200);
      } else if (arrProdutos.length == 0) {
        Notify.create({
          type: "negative",
          message: "Nenhum item localizado. Melhore sua pesquisa.",
          timeout: 3000, // 3 segundos
          actions: [{ icon: "close", color: "white" }],
        });
      } else {
        Notify.create({
          type: "positive",
          message: "Pesquisa encontrou " + arrProdutos.length + " itens.",
          timeout: 1000, // 1 segundo
          actions: [{ icon: "close", color: "white" }],
        });
      }
      this.resultadoPesquisa = arrProdutos;
    },

    async buscarBarras(barras) {
      let ret = await db.produto.where({ barras }).toArray();
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
      ret = await db.produto
        .where({ codproduto: codproduto })
        .filter((produto) => produto.quantidade == null)
        .toArray();
      return ret;
    },
  },
});
