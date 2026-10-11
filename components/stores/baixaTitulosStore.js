// Baixa de títulos pelo wizard de cobrança, a mesma no PDV (Receber título / Pagar vale) e no
// contas (Receber ou Pagar Títulos); também o vale/adiantamento (MgAdiantamentoDialog).
//
// Uma baixa = um pagamento (conceito do Fábio, 09/10/2026): o pagamento é o fato (o dinheiro que
// andou) e a baixa é a amarração dele com os títulos. O app escolhe os títulos (capital, juros,
// multa, desconto e total de cada um); aqui ficam o líquido (o sentido sai dele: entra dinheiro
// ou sai), a forma escolhida no wizard (uma só, com o valor do líquido) e a finalização, que
// manda tudo de uma vez para o servidor. Para pagar com duas formas, faz duas baixas.
//
// Cobrança integrada (PIX QR, Stone, SafraPay) confirmada ainda não é pagamento: vira a forma pelo
// PIX que pagou (codpix, TASK-219) ou pelo pedido da maquininha (codpagarmepedido,
// codsauruspedido, TASK-220), e o pagamento nasce na finalização. Fechado antes, o PIX fica sem uso
// na tela PIX e o cartão se lança à mão.
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
    // a forma escolhida no wizard, no formato da baixa (uma só)
    forma: null,
    // cobrança integrada esperando o banco/maquineta: { tipo, id, valor }
    pendente: null,
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
    // títulos que se anulam, ou quitados só com desconto (100%): baixa sem dinheiro, no
    // Encontro de Contas. Todos zerados, sem desconto, não é nada a gravar
    compensacao() {
      return (
        this.titulos.some((t) => (t.total || 0) > 0 || (t.desconto || 0) > 0) &&
        this.totalLiquido < 0.005
      )
    },
    // pronto para gravar: a forma escolhida (ou o encontro de contas, que não tem forma)
    pronto() {
      return this.compensacao || !!this.forma
    },
    // a forma é dinheiro que já entrou (o PIX, o cartão confirmado na maquininha): não gravou, ela
    // fica para o Gravar em vez de voltar ao wizard
    formaConfirmada() {
      return !!(this.forma?.codpix || this.forma?.codpagarmepedido || this.forma?.codsauruspedido)
    },
  },

  actions: {
    iniciar({ pessoa, titulos }) {
      this.pessoa = pessoa
      this.titulos = titulos
      this.forma = null
      this.pendente = null
      if (!ouvindo) {
        ouvindo = true
        ouvir('cobrancaConcluida', (dados) => this.cobrancaConcluida(dados))
      }
    },

    // abre o wizard com o valor do líquido, travado; formas = { entrada: [...], saida: [...] }
    abrirWizard({ contexto, formas, padrao = {} }) {
      if (this.compensacao) {
        return
      }
      if (this.totalLiquido <= 0) {
        Notify.create({
          type: 'negative',
          message: 'Informe o valor dos títulos!',
          timeout: 3000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        return
      }
      cobrancaStore().abrir({
        valor: this.totalLiquido,
        total: this.totalLiquido,
        saldo: this.totalLiquido,
        valorFixo: true,
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

    // forma escolhida no wizard: o total é o que andou (sem troco)
    adicionar(pag) {
      const total =
        pag.codpix || pag.codpagarmepedido || pag.codsauruspedido
          ? arredonda(pag.total ?? pag.principal)
          : arredonda((pag.principal || 0) + (pag.juros || 0) - (pag.desconto || 0))
      this.forma = {
        meio: pag.meio ?? null,
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
        codpix: pag.codpix ?? null,
        codpagarmepedido: pag.codpagarmepedido ?? null,
        codsauruspedido: pag.codsauruspedido ?? null,
        codpagamentoorigem: pag.codpagamentoorigem ?? null,
        descricao:
          pag.descricao ??
          [MEIOS[pag.meio], pag.maquineta, pag.portador, pag.descricaoorigem]
            .filter(Boolean)
            .join(' · '),
      }
    },

    // tira a forma escolhida (a integrada já confirmada não se tira: o dinheiro entrou)
    remover() {
      this.forma = null
    },

    // cobrança integrada criada pelo wizard: espera a confirmação
    cobrancaCriada({ tipo, dados }) {
      this.pendente = {
        tipo,
        id: dados[ID_COBRANCA[tipo]],
        valor: arredonda(dados.valororiginal ?? dados.valor),
      }
    },

    cobrancaConcluida({ tipo, dados }) {
      const p = this.pendente
      if (!p || p.tipo !== tipo || p.id != dados?.[ID_COBRANCA[tipo]]) {
        return
      }
      // o PIX QR vem pelo PIX que pagou; Stone e SafraPay, pelo pedido
      const pix = tipo === 'pix'
      if (pix && !dados.codpix) {
        Notify.create({
          type: 'negative',
          message: 'O pagamento da cobrança ainda não foi registrado: consulte de novo!',
          timeout: 5000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        return
      }
      this.pendente = null
      this.adicionar({
        codpix: pix ? dados.codpix : null,
        codpagarmepedido: tipo === 'pagarme' ? p.id : null,
        codsauruspedido: tipo === 'saurus' ? p.id : null,
        total: p.valor,
        descricao: DESCRICAO_COBRANCA[tipo],
      })
    },

    // manda títulos e a forma; devolve os pagamentos (um)
    async finalizar(url, extras = {}) {
      if (this.finalizando) {
        return false
      }
      if (!this.pronto) {
        Notify.create({
          type: 'negative',
          message: 'Escolha como foi pago!',
          timeout: 3000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      this.finalizando = true
      try {
        const forma = this.forma ? { ...this.forma } : null
        if (forma) {
          delete forma.descricao
        }
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
          pagamentos: forma && !this.compensacao ? [forma] : [],
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
