// Baixa de títulos pelo wizard de cobrança, a mesma no PDV (Receber título / Pagar vale) e no
// contas (Receber ou Pagar Títulos) — M6.1 do plano doc-3.
//
// O app escolhe os títulos (capital, juros, multa, desconto e total de cada um); aqui ficam o
// líquido (o sentido sai dele: entra dinheiro ou sai), as formas lançadas no wizard (um pagamento
// por forma) e a finalização, que manda tudo de uma vez para o servidor. Cobrança integrada
// (PIX QR, Stone, SafraPay) entra como forma quando o banco/maquineta confirma: o servidor já
// criou o pagamento e a finalização só o amarra aos títulos.
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { cobrancaStore } from '@components/stores/cobrancaStore'
import { MEIOS } from '@components/cobranca/pagamento.js'
import { ouvir } from '@components/cobranca/eventos.js'
import { abrirCobrancaIntegrada } from '@components/cobranca/integrada.js'

const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

// id da cobrança integrada, para casar a confirmação com a forma que a criou
const ID_COBRANCA = { pix: 'codpixcob', pagarme: 'codpagarmepedido', saurus: 'codsauruspedido' }
const DESCRICAO_COBRANCA = {
  pix: 'PIX QR Code',
  pagarme: 'Cartão Stone',
  saurus: 'Cartão SafraPay',
}

let ouvindo = false

export const baixaTitulosStore = defineStore('baixaTitulos', {
  state: () => ({
    pessoa: null, // { codpessoa, fantasia }
    // { codtitulo, numero, vencimento, operacao ('DB' a receber, 'CR' a pagar), saldo, juros,
    //   multa, desconto, total }
    titulos: [],
    // formas lançadas no wizard, no formato da baixa
    pagamentos: [],
    // cobranças integradas esperando o banco/maquineta: { tipo, id, valor }
    pendentes: [],
    finalizando: false,
  }),

  getters: {
    // positivo = entra dinheiro (recebe mais do que paga)
    liquido() {
      return arredonda(
        this.titulos.reduce((s, t) => s + (t.operacao === 'DB' ? 1 : -1) * (t.total || 0), 0),
      )
    },
    entrada() {
      return this.liquido >= 0
    },
    totalLiquido() {
      return Math.abs(this.liquido)
    },
    pago() {
      return arredonda(this.pagamentos.reduce((s, p) => s + p.total, 0))
    },
    saldo() {
      return arredonda(this.totalLiquido - this.pago)
    },
    compensacao() {
      return this.titulos.length > 0 && this.totalLiquido < 0.005
    },
  },

  actions: {
    iniciar({ pessoa, titulos }) {
      this.pessoa = pessoa
      this.titulos = titulos
      this.pagamentos = []
      this.pendentes = []
      if (!ouvindo) {
        ouvindo = true
        ouvir('cobrancaConcluida', (dados) => this.cobrancaConcluida(dados))
      }
    },

    // abre o wizard com o que falta; formas = { entrada: [...], saida: [...] }
    abrirWizard({ contexto, formas, padrao = {} }) {
      if (this.saldo <= 0) {
        return
      }
      cobrancaStore().abrir({
        valor: this.saldo,
        total: this.totalLiquido,
        saldo: this.saldo,
        sentido: this.entrada ? 'entrada' : 'saida',
        pessoa: this.pessoa,
        formasPermitidas: this.entrada ? formas.entrada : formas.saida,
        documento: {
          tipo: 'titulos',
          sincronizado: true,
          aoPagamento: (pag) => this.adicionar(pag),
          aoCobranca: (cobranca) => {
            this.cobrancaCriada(cobranca)
            abrirCobrancaIntegrada(cobranca)
          },
        },
        contexto,
        padrao,
      })
    },

    // forma lançada no wizard: o total é o que andou (sem troco)
    adicionar(pag) {
      const total = arredonda((pag.principal || 0) + (pag.juros || 0) - (pag.desconto || 0))
      this.pagamentos.push({
        meio: pag.meio,
        total,
        valortroco: pag.valortroco ?? null,
        codportador: pag.codportador ?? null,
        codmaquineta: pag.codmaquineta ?? null,
        bandeira: pag.bandeira ?? null,
        autorizacao: pag.autorizacao ?? null,
        parcelas: pag.parcelas ?? null,
        cmc7: pag.cmc7 ?? null,
        chequevencimento: pag.chequevencimento ?? null,
        chequecnpj: pag.chequecnpj ?? null,
        chequeemitente: pag.chequeemitente ?? null,
        codpagamento: pag.codpagamento ?? null,
        codpagamentoorigem: pag.codpagamentoorigem ?? null,
        descricao:
          pag.descricao ??
          [MEIOS[pag.meio], pag.maquineta, pag.portador, pag.descricaoorigem]
            .filter(Boolean)
            .join(' · '),
      })
    },

    remover(indice) {
      this.pagamentos.splice(indice, 1)
    },

    // cobrança integrada criada pelo wizard: espera a confirmação
    cobrancaCriada({ tipo, dados }) {
      this.pendentes.push({
        tipo,
        id: dados[ID_COBRANCA[tipo]],
        valor: arredonda(dados.valororiginal ?? dados.valor),
      })
    },

    cobrancaConcluida({ tipo, dados }) {
      const i = this.pendentes.findIndex(
        (p) => p.tipo === tipo && p.id == dados?.[ID_COBRANCA[tipo]],
      )
      if (i < 0) {
        return
      }
      const pendente = this.pendentes.splice(i, 1)[0]
      if (!dados.codpagamento) {
        Notify.create({
          type: 'negative',
          message: 'O pagamento da cobrança ainda não foi registrado: consulte de novo!',
          timeout: 5000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        this.pendentes.push(pendente)
        return
      }
      this.adicionar({
        codpagamento: dados.codpagamento,
        principal: pendente.valor,
        descricao: DESCRICAO_COBRANCA[tipo],
      })
    },

    // pagou menos que o líquido: os títulos a receber ficam com o que foi pago, por vencimento
    // (juros e multa proporcionais); o resto continua aberto
    ajustarAoPago() {
      if (!this.entrada || this.saldo <= 0) {
        return
      }
      let resta =
        this.pago + this.titulos.filter((t) => t.operacao === 'CR').reduce((s, t) => s + t.total, 0)
      const receber = this.titulos
        .filter((t) => t.operacao === 'DB')
        .sort((a, b) => String(a.vencimento).localeCompare(String(b.vencimento)))
      const ficam = []
      for (const t of receber) {
        if (resta <= 0.005) break
        if (t.total <= resta + 0.005) {
          ficam.push(t)
          resta = arredonda(resta - t.total)
          continue
        }
        const fator = resta / t.total
        const juros = arredonda(t.juros * fator)
        const multa = arredonda(t.multa * fator)
        const desconto = arredonda(t.desconto * fator)
        ficam.push({
          ...t,
          juros,
          multa,
          desconto,
          total: resta,
          saldo: arredonda(resta - juros - multa + desconto),
        })
        resta = 0
      }
      this.titulos = [...this.titulos.filter((t) => t.operacao === 'CR'), ...ficam]
    },

    // manda títulos e formas; devolve os pagamentos criados
    async finalizar(url, extras = {}) {
      if (this.finalizando) {
        return false
      }
      if (!this.compensacao && Math.abs(this.saldo) > 0.005) {
        Notify.create({
          type: 'negative',
          message: 'Ainda falta lançar R$ ' + this.saldo.toFixed(2).replace('.', ',') + '!',
          timeout: 3000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      this.finalizando = true
      try {
        const { data } = await api.post(url, {
          ...extras,
          codpessoa: this.pessoa?.codpessoa,
          titulos: this.titulos.map((t) => ({
            codtitulo: t.codtitulo,
            saldo: t.saldo,
            juros: t.juros || 0,
            multa: t.multa || 0,
            desconto: t.desconto || 0,
            total: t.total,
          })),
          pagamentos: this.pagamentos.map((p) => {
            const forma = { ...p }
            delete forma.descricao
            return forma
          }),
        })
        return data.data
      } catch (error) {
        Notify.create({
          type: 'negative',
          message: error?.response?.data?.message ?? error?.message ?? 'Erro ao finalizar',
          timeout: 5000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      } finally {
        this.finalizando = false
      }
    },
  },
})
