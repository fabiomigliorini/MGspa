import { defineStore } from 'pinia'
import { db } from 'boot/db'
import { Notify, LoadingBar } from 'quasar'
import { sincronizacaoStore } from './sincronizacao'
import { api } from 'src/services/api'

const sSinc = sincronizacaoStore()

export const produtoStore = defineStore('produto', {
  persist: true,

  state: () => ({
    resultadoPesquisa: [],
    textoPesquisa: null,
    dialogPesquisa: false,
    sortPesquisa: 'Alfabética',
  }),

  actions: {
    urlImagem(codimagem) {
      // return "https://fakeimg.pl/300x300";
      if (codimagem == null) {
        return 'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/semimagem.jpg'
      }
      return 'https://sistema.mgpapelaria.com.br/MGLara/public/imagens/' + codimagem + '.jpg'
    },

    async pesquisar() {
      // verifica se tem texto de busca
      const texto = this.textoPesquisa.trim()
      if (texto.length == 0) {
        return
      }

      // monta array de palavras pra buscas
      LoadingBar.start()
      const palavras = texto.split(' ')

      // Busca produto baseados na primeira palavra de pesquisa
      // distinct() é obrigatório: buscaArr é indice multiEntry, entao um produto com duas
      // variacoes da mesma palavra casa duas vezes com o startsWithIgnoreCase e sairia repetido
      var colProdutos = await db.produto
        .where('buscaArr')
        .startsWithIgnoreCase(palavras[0])
        .distinct()
        .and((p) => p.inativo == null)

      // se estiver buscando por mais de uma palavra
      if (palavras.length > 1) {
        // monta expressoes regulares
        var regexes = []
        for (let i = 1; i < palavras.length; i++) {
          regexes.push(new RegExp('.*' + palavras[i] + '.*', 'i'))
        }

        // percorre todos registros filtrando pelas expressoes regulares
        const iMax = regexes.length
        colProdutos = await colProdutos.and(function (produto) {
          for (let i = 0; i < iMax; i++) {
            if (!regexes[i].test(produto.busca)) {
              return false
            }
          }
          return true
        })
      }

      // transforma colecao de produto em array
      var arrProdutos = await colProdutos.toArray()
      switch (this.sortPesquisa) {
        case 'Preço':
          arrProdutos = arrProdutos.sort((a, b) => {
            if (a.preco > b.preco) {
              return 1
            } else if (a.preco == b.preco) {
              if (a.produto > b.produto) {
                return 1
              } else {
                return -1
              }
            } else {
              return -1
            }
          })
          break

        case 'Código':
          arrProdutos = arrProdutos.sort((a, b) => {
            if (a.codproduto > b.codproduto) {
              return 1
            } else if (a.codproduto == b.codproduto) {
              if (a.produto > b.produto) {
                return 1
              } else {
                return -1
              }
            } else {
              return -1
            }
          })
          break

        case 'Barras':
          arrProdutos = arrProdutos.sort((a, b) => {
            if (a.barras > b.barras) {
              return 1
            } else if (a.barras == b.barras) {
              if (a.produto > b.produto) {
                return 1
              } else {
                return -1
              }
            } else {
              return -1
            }
          })
          break

        case 'Alfabética':
        default:
          arrProdutos = arrProdutos.sort((a, b) => {
            if (a.produto > b.produto) {
              return 1
            } else {
              return -1
            }
          })
          break
      }
      LoadingBar.stop()

      // a grade mostra um card por produto (TASK-173): o teto de 200 é de
      // produtos, com todas as barras de cada um que casaram -- cortar nas
      // barras deixaria o ultimo produto com parte das embalagens
      const codprodutos = [...new Set(arrProdutos.map((p) => p.codproduto))]
      const total = codprodutos.length
      const primeiros = new Set(codprodutos.slice(0, 200))
      arrProdutos = arrProdutos.filter((p) => primeiros.has(p.codproduto))
      if (total > 200) {
        Notify.create({
          type: 'negative',
          message:
            'Pesquisa encontrou ' +
            total +
            ' produtos. Mostrando apenas os 200 primeiros. Refine a sua pesquisa.',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
      } else if (total == 0) {
        Notify.create({
          type: 'negative',
          message: 'Nenhum item localizado. Melhore sua pesquisa.',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
      } else {
        Notify.create({
          type: 'positive',
          message: 'Pesquisa encontrou ' + total + (total == 1 ? ' produto.' : ' produtos.'),
          timeout: 1000, // 1 segundo
          actions: [{ icon: 'close', color: 'white' }],
        })
      }
      this.resultadoPesquisa = arrProdutos
    },

    // a barra no cache do PDV, com a sigla e as casas decimais da unidade (TASK-53)
    async barra(codprodutobarra) {
      return await db.produto.get(parseInt(codprodutobarra))
    },

    async buscarBarras(barras) {
      // pergunta ao backend ja de inicio: se achar offline nao espera (a resposta so'
      // atualiza o cache), se nao achar e' a resposta do backend que vale antes de dar erro
      const online = this.sincroniza(barras)
      let ret = await db.produto.where({ barras }).toArray()
      if (ret.length == 0 && barras.length == 6 && !isNaN(parseInt(barras))) {
        ret = await db.produto
          .where({ codproduto: parseInt(barras) })
          .filter((produto) => produto.quantidade == null)
          .toArray()
      }
      if (ret.length >= 1) {
        return ret
      }
      return await online
    },

    async sincroniza(barras) {
      try {
        var { data } = await api.get('/v1/pdv/produto/' + barras, {
          // backend pendurado nao pode segurar o "Nao encontrei" do caixa
          timeout: 3000,
        })
        if (data.length > 0) {
          await db.produto.bulkPut(sSinc.carimbar('produto', data))
        }
        return data
      } catch (error) {
        console.log(error)
        console.log('Impossível buscar barras ' + barras + ' no Backend!')
        return []
      }
    },
  },
})
