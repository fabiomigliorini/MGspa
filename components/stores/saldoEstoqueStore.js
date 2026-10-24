import { defineStore } from 'pinia'
import { reactive } from 'vue'
import { api } from 'src/services/api'

// Saldo físico de estoque na pesquisa em grade (@components/MgGradeProduto, TASK-254), guardado
// por 5 minutos: quem anda pelas variações, rola a grade ou troca o local e volta não refaz a
// consulta. Cada entrada é { dados, em, carregando }, com `dados` sendo a resposta da rota.
//   variacoes[codprodutovariacao]: por local, na janela da variação
//     (GET v1/produto-variacao/{cod}/saldo)
//   produtos['codproduto|codestoquelocal']: somado nas variações, no chip do card; sem local,
//     soma todos (GET v1/produto/{cod}/saldo[/{codestoquelocal}])
const VALIDADE = 5 * 60 * 1000

export const useSaldoEstoqueStore = defineStore('saldoEstoque', () => {
  const variacoes = reactive({})
  const produtos = reactive({})

  function carregar(cache, chave, url) {
    // relido do reactive depois de criar: o objeto cru não avisaria a tela
    if (!cache[chave]) {
      cache[chave] = { dados: null, em: 0, carregando: false }
    }
    const s = cache[chave]
    if (s.carregando) return
    if (s.dados && Date.now() - s.em < VALIDADE) return
    s.carregando = true
    api
      .get(url)
      .then((ret) => {
        s.dados = ret.data
        s.em = Date.now()
      })
      // sem resposta (o PDV offline), fica o que já tinha ou "Saldo indisponível"
      .catch(() => {})
      .finally(() => {
        s.carregando = false
      })
  }

  function carregarVariacao(codprodutovariacao) {
    if (!codprodutovariacao) return
    carregar(variacoes, codprodutovariacao, `v1/produto-variacao/${codprodutovariacao}/saldo`)
  }

  const chaveProduto = (codproduto, codestoquelocal) => `${codproduto}|${codestoquelocal ?? ''}`

  function carregarProduto(codproduto, codestoquelocal) {
    if (!codproduto) return
    const url = codestoquelocal
      ? `v1/produto/${codproduto}/saldo/${codestoquelocal}`
      : `v1/produto/${codproduto}/saldo`
    carregar(produtos, chaveProduto(codproduto, codestoquelocal), url)
  }

  return { variacoes, produtos, chaveProduto, carregarVariacao, carregarProduto }
})
