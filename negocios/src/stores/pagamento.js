// Pagamentos avulsos do PDV, na tela Pagamentos: as formas do Receber Título / Pagar Vale (a
// tela é a MgBaixaTitulos, a mesma do contas), as do Vale / Adiantamento (M8, o
// MgAdiantamentoDialog, o mesmo do contas), o recibo e o vale de crédito na térmica.
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { negocioStore } from 'stores/negocio'

// formas que o caixa usa: recebe o que se confirma na hora; paga vale em dinheiro ou registrando
// a devolução no cartão/PIX
export const FORMAS_RECEBER = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque'],
  saida: ['dinheiro', 'estorno'],
}

// vale colaborador e adiantamentos: o que entra, como no Receber título; o que sai, só dinheiro
// da gaveta (a loja só baixa o que se confirma na hora)
export const FORMAS_ADIANTAMENTO = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque'],
  saida: ['dinheiro'],
}

const avisar = (error) => {
  console.log(error)
  Notify.create({
    type: 'negative',
    message: error?.response?.data?.message ?? error?.message ?? String(error),
    timeout: 5000,
    actions: [{ icon: 'close', color: 'white' }],
  })
}

export const pagamentoStore = defineStore('pagamento', {
  state: () => ({
    dialogAdiantamento: false,
  }),

  actions: {
    // o vale de crédito do título (Vale Compras / Contra Vale) na térmica do PDV
    async imprimirVale(codtitulo) {
      const impressora = negocioStore().padrao.codimpressora
      if (!impressora) {
        Notify.create({
          type: 'negative',
          message: 'Nenhuma impressora térmica selecionada!',
          timeout: 3000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        return
      }
      try {
        await api.post(`/v1/pdv/titulo/${codtitulo}/vale/${impressora}`)
        Notify.create({
          type: 'positive',
          message: 'Impressão Solicitada!',
          timeout: 1000,
          actions: [{ icon: 'close', color: 'white' }],
        })
      } catch (error) {
        avisar(error)
      }
    },

    async imprimirRecibo(codpagamentos) {
      const impressora = negocioStore().padrao.codimpressora
      if (!impressora) {
        return
      }
      try {
        await api.post('/v1/pdv/pagamento/recibo/' + impressora, {
          codpagamento: codpagamentos,
        })
      } catch (error) {
        avisar(error)
      }
    },
  },
})
