import { defineStore } from 'pinia'
import { reactive } from 'vue'
import { api } from 'src/services/api'

// Saldo físico por local de estoque de uma variação, na janela da variação da pesquisa em
// grade (@components/MgGradeProduto, TASK-254). Guarda a resposta por codprodutovariacao por
// 5 minutos: quem anda pelas variações e volta não refaz a consulta. Por variação:
// { dados, em, carregando }; dados é o que veio de GET v1/produto-variacao/{cod}/saldo.
const VALIDADE = 5 * 60 * 1000

export const useSaldoVariacaoStore = defineStore('saldoVariacao', () => {
  const saldos = reactive({})

  function carregar(codprodutovariacao) {
    if (!codprodutovariacao) return
    // relido do reactive depois de criar: o objeto cru não avisaria a tela
    if (!saldos[codprodutovariacao]) {
      saldos[codprodutovariacao] = { dados: null, em: 0, carregando: false }
    }
    const s = saldos[codprodutovariacao]
    if (s.carregando) return
    if (s.dados && Date.now() - s.em < VALIDADE) return
    s.carregando = true
    api
      .get(`v1/produto-variacao/${codprodutovariacao}/saldo`)
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

  return { saldos, carregar }
})
