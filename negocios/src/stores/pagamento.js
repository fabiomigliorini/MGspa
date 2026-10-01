// Receber título / Pagar vale no PDV (M7 do plano doc-3, no M6.1): busca os títulos abertos e
// os créditos da pessoa, o caixa escolhe e paga pelo wizard de cobrança de @components; a baixa
// (formas, finalização) é a mesma do contas, no baixaTitulosStore.
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { negocioStore } from 'stores/negocio'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { calcularJurosMulta } from '@components/cobranca/juros.js'

const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

// formas que o caixa usa: recebe o que se confirma na hora; paga vale em dinheiro ou
// registrando a devolução no cartão/PIX (só Gerente, o servidor confere)
const FORMAS = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque'],
  saida: ['dinheiro', 'estorno'],
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
    dialog: false,
    etapa: 'busca', // busca → titulos
    codpessoa: null,
    numero: null,
    pessoa: null,
    titulos: [], // abertos da pessoa, com `selecionado`, juros e multa calculados
    buscando: false,
  }),

  getters: {
    selecionados() {
      return this.titulos.filter((t) => t.selecionado)
    },
  },

  actions: {
    abrir(codpessoa = null) {
      this.etapa = 'busca'
      this.codpessoa = codpessoa && codpessoa != 1 ? codpessoa : null
      this.numero = null
      this.pessoa = null
      this.titulos = []
      this.dialog = true
    },

    // pela pessoa ou pelo número de um título dela; os a receber já vêm marcados
    async buscar() {
      if (!this.codpessoa && !this.numero) {
        return
      }
      this.buscando = true
      try {
        const { data } = await api.get('/v1/pdv/pagamento/titulos', {
          params: {
            pdv: sincronizacaoStore().pdv.uuid,
            codpessoa: this.codpessoa || null,
            numero: this.codpessoa ? null : this.numero,
          },
        })
        this.pessoa = data.data.pessoa
        this.codpessoa = data.data.pessoa.codpessoa
        this.titulos = data.data.titulos.map((t) => {
          const { juros, multa } = calcularJurosMulta(t)
          return {
            ...t,
            juros,
            multa,
            desconto: 0,
            total: arredonda(t.saldo + juros + multa),
            selecionado: t.operacao === 'DB',
          }
        })
        this.etapa = 'titulos'
      } catch (error) {
        avisar(error)
      } finally {
        this.buscando = false
      }
    },

    // marcados vão para a baixa; o wizard abre com o que falta
    async receber() {
      const sBaixa = baixaTitulosStore()
      if (!sBaixa.pagamentos.length) {
        sBaixa.iniciar({ pessoa: this.pessoa, titulos: this.selecionados.map((t) => ({ ...t })) })
      }
      if (sBaixa.compensacao) {
        avisar('Os títulos se anulam: faça o encontro de contas pelo financeiro.')
        return
      }
      const sNegocio = negocioStore()
      sBaixa.abrirWizard({
        formas: FORMAS,
        padrao: sNegocio.padrao,
        // filial e maquinetas do estoque local configurado no PDV
        contexto: await sNegocio.contextoCobranca(sNegocio.padrao.codestoquelocal),
      })
    },

    // grava no servidor, imprime o recibo e recomeça
    async finalizar() {
      const sSinc = sincronizacaoStore()
      const pags = await baixaTitulosStore().finalizar('/v1/pdv/pagamento', { pdv: sSinc.pdv.uuid })
      if (!pags) {
        return false
      }
      Notify.create({
        type: 'positive',
        message: 'Pagamento registrado!',
        color: 'green-5',
        icon: 'done',
        timeout: 2000,
      })
      await this.imprimirRecibo(pags.map((p) => p.codpagamento))
      this.dialog = false
      return pags
    },

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
