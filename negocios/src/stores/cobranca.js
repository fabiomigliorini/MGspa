// Wizard de cobrança (Receber), desacoplado da venda (M5 do plano doc-3).
//
// Quem abre informa { valor, total, sentido, pessoa, formasPermitidas, documento } e recebe de
// volta, pelos eventos do ReceberDialog, um `pagamento` (meio, valores, troco, maquineta, dados
// de cheque…) ou `parcelas` (condição, vencimento e valor). Cobrança integrada (PIX QR,
// Stone/PagarMe, SafraPay/Saurus) é criada aqui, para o documento; o pagamento dela nasce no
// servidor quando o banco/maquineta confirma.
//
// documento = { tipo: 'negocio', codnegocio, codfilial, codestoquelocal, sincronizado,
//               valesUsados, preparar(), atualizar() }
//   preparar(): garante o documento no servidor (sincroniza) e devolve o codnegocio ou false
//   atualizar(): recarrega o documento depois de criar a cobrança
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import moment from 'moment'
import { sincronizacaoStore } from 'stores/sincronizacao'

const sSinc = sincronizacaoStore()

const avisar = (error) => {
  console.log(error)
  Notify.create({
    type: 'negative',
    message: error?.response?.data?.message ?? error?.message ?? String(error),
    timeout: 3000, // 3 segundos
    actions: [{ icon: 'close', color: 'white' }],
  })
}

export const cobrancaStore = defineStore('cobranca', {
  persist: {
    pick: ['maquinetasRecentes', 'feriados', 'feriadosAtualizacao'],
  },

  state: () => ({
    dialog: false,
    // o que o documento informou ao abrir
    valor: null, // valor deste pagamento (editável no passo 2)
    total: 0, // total do documento
    saldo: 0, // o que falta receber do documento
    sentido: 'entrada', // entrada = receber; saida = pagar
    pessoa: null, // { codpessoa, fantasia }
    formasPermitidas: null, // null = todas
    documento: null,
    padrao: {}, // configuração do PDV: maquineta e conta PIX padrão
    // forma já escolhida ao abrir (bipagem do vale)
    forma: null,
    codtituloVale: null,
    // codmaquineta das usadas em cartão manual neste PDV, mais recente primeiro
    maquinetasRecentes: [],
    // feriados (YYYY-MM-DD) para o vencimento do fechamento mensal
    feriados: [],
    feriadosAtualizacao: null,
  }),

  getters: {
    consumidor() {
      return !this.pessoa?.codpessoa || this.pessoa.codpessoa == 1
    },
    sincronizado() {
      return !!this.documento?.sincronizado
    },
  },

  actions: {
    abrir({
      valor,
      total,
      saldo,
      sentido = 'entrada',
      pessoa = null,
      formasPermitidas = null,
      documento,
      padrao = {},
      forma = null,
      codtituloVale = null,
    }) {
      this.valor = valor
      this.total = total
      this.saldo = saldo
      this.sentido = sentido
      this.pessoa = pessoa
      this.formasPermitidas = formasPermitidas
      this.documento = documento
      this.padrao = { ...padrao }
      this.forma = forma
      this.codtituloVale = codtituloVale
      this.dialog = true
    },

    fechar() {
      this.dialog = false
    },

    permitida(forma) {
      return !this.formasPermitidas || this.formasPermitidas.includes(forma)
    },

    registrarMaquinetaRecente(codmaquineta) {
      const anteriores = this.maquinetasRecentes.filter(
        (m) => Number.isInteger(m) && m !== codmaquineta,
      )
      this.maquinetasRecentes = [codmaquineta, ...anteriores].slice(0, 10)
    },

    // feriados do servidor uma vez por dia; offline fica o que já tinha (segunda a sábado)
    async carregarFeriados() {
      const hoje = moment().format('YYYY-MM-DD')
      if (this.feriadosAtualizacao === hoje) {
        return this.feriados
      }
      try {
        const { data } = await api.get('/v1/feriado')
        this.feriados = (data.data ?? [])
          .filter((f) => !f.inativo && f.data >= hoje)
          .map((f) => String(f.data).substr(0, 10))
        this.feriadosAtualizacao = hoje
      } catch (error) {
        console.log(error)
      }
      return this.feriados
    },

    // codnegocio do documento já no servidor (sincroniza antes, se preciso)
    async prepararDocumento() {
      const cod = await this.documento?.preparar?.()
      if (!cod) {
        Notify.create({
          type: 'negative',
          message: 'Impossível criar cobrança integrada para um negócio não sincronizado!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      return cod
    },

    async depoisDeCriar(mensagem) {
      Notify.create({
        type: 'positive',
        message: mensagem,
        timeout: 1000, // 1 segundo
        actions: [{ icon: 'close', color: 'white' }],
      })
      await this.documento?.atualizar?.()
    },

    async criarPixCob(valor, codportador) {
      const codnegocio = await this.prepararDocumento()
      if (!codnegocio) {
        return false
      }
      try {
        const { data } = await api.post('/v1/pdv/pix/cob', {
          pdv: sSinc.pdv.uuid,
          valor,
          codnegocio,
          codpessoa: this.pessoa?.codpessoa ?? null,
          codportador,
        })
        await this.depoisDeCriar('Cobrança PIX Criada!')
        return data.data
      } catch (error) {
        avisar(error)
        return false
      }
    },

    async criarPagarMePedido({ codpagarmepos, valor, valorparcela, valorjuros, tipo, parcelas }) {
      const codnegocio = await this.prepararDocumento()
      if (!codnegocio) {
        return false
      }
      try {
        const { data } = await api.post('/v1/pdv/pagar-me/pedido', {
          pdv: sSinc.pdv.uuid,
          codnegocio,
          codpessoa: this.pessoa?.codpessoa ?? null,
          codpagarmepos,
          valor,
          valorparcela,
          valorjuros,
          tipo,
          parcelas,
          jurosloja: true,
          descricao: 'Negocio ' + codnegocio,
        })
        await this.depoisDeCriar('Cobrança Pagar Me/Stone Criada!')
        return data.data
      } catch (error) {
        avisar(error)
        return false
      }
    },

    async criarSaurusPedido({ codsauruspos, valor, valorparcela, valorjuros, tipo, parcelas }) {
      const codnegocio = await this.prepararDocumento()
      if (!codnegocio) {
        return false
      }
      try {
        const { data } = await api.post('/v1/pdv/saurus/pedido', {
          pdv: sSinc.pdv.uuid,
          codnegocio,
          codpessoa: this.pessoa?.codpessoa ?? null,
          codsauruspos,
          valor,
          valorparcela,
          valorjuros,
          tipo,
          parcelas,
          jurosloja: true,
          descricao: 'Negocio ' + codnegocio,
        })
        await this.depoisDeCriar('Cobrança Saurus/Safra Pay Criada!')
        return data.data
      } catch (error) {
        avisar(error)
        return false
      }
    },

    async buscarVale(codtitulo) {
      return sSinc.buscarVale(codtitulo)
    },
  },
})
