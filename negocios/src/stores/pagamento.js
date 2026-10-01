// Pagamentos avulsos do PDV, na tela Pagamentos: as formas do Receber Título / Pagar Vale (a
// tela é a MgBaixaTitulos, a mesma do contas), o Vale / Adiantamento (M8, mesmo wizard e mesmas
// formas lançadas do baixaTitulosStore) e o recibo na térmica.
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { negocioStore } from 'stores/negocio'
import { baixaTitulosStore } from '@components/stores/baixaTitulosStore'
import { db } from 'boot/db'

const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

// formas que o caixa usa: recebe o que se confirma na hora; paga vale em dinheiro ou
// registrando a devolução no cartão/PIX (só Gerente, o servidor confere)
export const FORMAS_RECEBER = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque'],
  saida: ['dinheiro', 'estorno'],
}

// vale colaborador e adiantamentos (M8): título que já nasce com o dinheiro; vale e adiantamento a
// fornecedor saem da gaveta em dinheiro, adiantamento de cliente entra como no Receber título
export const TIPOS_LANCAMENTO = [
  { value: 2, label: 'Vale Colaborador', codcontacontabil: 42, entrada: false },
  { value: 120, label: 'Adiantamento a Fornecedor', codcontacontabil: 1, entrada: false },
  { value: 220, label: 'Adiantamento de Cliente', codcontacontabil: 2, entrada: true },
]
const FORMAS_LANCAMENTO = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque'],
  saida: ['dinheiro'],
}

const daquiA30Dias = () => {
  const d = new Date()
  d.setDate(d.getDate() + 30)
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const dia = String(d.getDate()).padStart(2, '0')
  return `${d.getFullYear()}-${mes}-${dia}`
}

const lancamentoVazio = () => ({
  codtipotitulo: 2,
  codpessoa: null,
  valor: null,
  codcontacontabil: 42,
  vencimento: daquiA30Dias(),
  observacao: null,
})

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
    // Vale / Adiantamento (M8)
    dialogLancamento: false,
    lancamento: lancamentoVazio(),
  }),

  getters: {
    tipoLancamento() {
      return TIPOS_LANCAMENTO.find((t) => t.value === this.lancamento.codtipotitulo)
    },
  },

  actions: {
    abrirLancamento() {
      this.lancamento = lancamentoVazio()
      baixaTitulosStore().iniciar({ pessoa: null, titulos: [] })
      this.dialogLancamento = true
    },

    trocarTipoLancamento(codtipotitulo) {
      this.lancamento.codtipotitulo = codtipotitulo
      this.lancamento.codcontacontabil = this.tipoLancamento.codcontacontabil
    },

    // o título ainda não existe: entra na baixa como uma linha só, no sentido do tipo, para o
    // wizard e as cobranças integradas funcionarem como no Receber título
    async cobrarLancamento() {
      const sBaixa = baixaTitulosStore()
      if (!sBaixa.pagamentos.length) {
        const pessoa = await db.pessoa.get(this.lancamento.codpessoa)
        const valor = arredonda(this.lancamento.valor)
        sBaixa.iniciar({
          pessoa: { codpessoa: this.lancamento.codpessoa, fantasia: pessoa?.fantasia },
          titulos: [
            {
              codtitulo: null,
              operacao: this.tipoLancamento.entrada ? 'DB' : 'CR',
              saldo: valor,
              juros: 0,
              multa: 0,
              desconto: 0,
              total: valor,
            },
          ],
        })
      }
      const sNegocio = negocioStore()
      sBaixa.abrirWizard({
        formas: FORMAS_LANCAMENTO,
        padrao: sNegocio.padrao,
        contexto: await sNegocio.contextoCobranca(sNegocio.padrao.codestoquelocal),
      })
    },

    // um título por forma lançada; pagou menos (cobrança integrada de parte), lança o que pagou
    async finalizarLancamento() {
      const sBaixa = baixaTitulosStore()
      if (sBaixa.finalizando || !sBaixa.pagamentos.length) {
        return false
      }
      sBaixa.finalizando = true
      try {
        const { data } = await api.post('/v1/pdv/titulo', {
          pdv: sincronizacaoStore().pdv.uuid,
          codtipotitulo: this.lancamento.codtipotitulo,
          codpessoa: this.lancamento.codpessoa,
          codcontacontabil: this.lancamento.codcontacontabil,
          vencimento: this.lancamento.vencimento,
          observacao: this.lancamento.observacao,
          pagamentos: sBaixa.pagamentos.map((p) => {
            const forma = { ...p }
            delete forma.descricao
            return forma
          }),
        })
        Notify.create({
          type: 'positive',
          message: this.tipoLancamento.label + ' lançado!',
          color: 'green-5',
          icon: 'done',
          timeout: 2000,
        })
        await this.imprimirRecibo(data.data.map((p) => p.codpagamento))
        this.dialogLancamento = false
        return data.data
      } catch (error) {
        avisar(error)
        return false
      } finally {
        sBaixa.finalizando = false
      }
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
