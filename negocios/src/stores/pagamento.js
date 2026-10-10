// Pagamentos avulsos do PDV, na tela Pagamentos: as formas do Receber Título / Pagar Vale (a
// tela é a MgBaixaTitulos, a mesma do contas), as do Vale / Adiantamento (M8, o
// MgAdiantamentoDialog, o mesmo do contas) e o recibo na térmica.
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { negocioStore } from 'stores/negocio'

// formas que o caixa usa: recebe o que se confirma na hora (ou o que já entrou e está sem
// amarração); paga vale em dinheiro ou registrando a devolução no cartão/PIX
export const FORMAS_RECEBER = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque', 'recebido'],
  saida: ['dinheiro', 'estorno'],
}

// vale colaborador e adiantamentos: o que entra, como no Receber título; o que sai, só dinheiro
// da gaveta (a loja só baixa o que se confirma na hora)
export const FORMAS_ADIANTAMENTO = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque', 'recebido'],
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
    async imprimirRecibo(codpagamentos) {
      const impressora = negocioStore().padrao.impressora
      if (!impressora) {
        return
      }
      try {
        await api.post('/v1/pdv/pagamento/recibo/' + impressora, {
          pdv: sincronizacaoStore().pdv.uuid,
          codpagamento: codpagamentos,
        })
      } catch (error) {
        avisar(error)
      }
    },
  },
})
