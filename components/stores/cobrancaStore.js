// Wizard de cobrança (MgCobrancaDialog), o mesmo no PDV e no contas (M5/M6.1 do plano doc-3).
//
// Quem abre informa { valor, total, saldo, sentido, pessoa, formasPermitidas, documento,
// contexto } e recebe de volta, pelos eventos do MgCobrancaDialog, um `pagamento` (meio,
// valores, troco, portador, maquineta, dados de cheque…), `parcelas` (condição, vencimento e
// valor) ou `cobranca` (integrada criada: PIX QR, Stone/PagarMe, SafraPay/Saurus). O pagamento
// da integrada nasce no servidor quando o banco/maquineta confirma.
//
// documento = o que está sendo pago:
//   { tipo: 'negocio', codnegocio, codestoquelocal, sincronizado, valesUsados, preparar(), atualizar() }
//   { tipo: 'titulos', sincronizado: true }
//   preparar(): garante o documento no servidor e devolve o codnegocio (ou true), false se não deu
//   atualizar(): recarrega o documento depois de criar a cobrança
//
// contexto = onde acontece (o app injeta o que é dele):
//   { pdv: uuid do PDV (null no contas), codfilial,
//     carregarMaquinetas: async (aoAtualizar) => maquinetas da filial (cadastro único, formato
//       paraPdv); aoAtualizar(lista) quando uma lista mais nova chega depois,
//     portadores: portadores que o contas pode usar (dinheiro, banco, cartão da empresa),
//     buscarVale: async (codtitulo) => vale (só no negócio) }
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'src/services/api'

const avisar = (error) => {
  console.log(error)
  Notify.create({
    type: 'negative',
    message: error?.response?.data?.message ?? error?.message ?? String(error),
    timeout: 3000, // 3 segundos
    actions: [{ icon: 'close', color: 'white' }],
  })
}

const hoje = () => {
  const d = new Date()
  return [
    d.getFullYear(),
    String(d.getMonth() + 1).padStart(2, '0'),
    String(d.getDate()).padStart(2, '0'),
  ].join('-')
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
    saldo: 0, // o que falta receber (ou pagar) do documento
    sentido: 'entrada', // entrada = receber; saida = pagar
    pessoa: null, // { codpessoa, fantasia }
    formasPermitidas: null, // null = as do negócio
    documento: null,
    contexto: {},
    padrao: {}, // configuração do PDV: maquineta, conta PIX e impressora padrão
    // forma já escolhida ao abrir (bipagem do vale)
    forma: null,
    codtituloVale: null,
    // codmaquineta das usadas em cartão manual neste aparelho, mais recente primeiro
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
    // juros do parcelamento, desconto por forma e prazo são coisas da venda
    ehNegocio() {
      return this.documento?.tipo === 'negocio'
    },
    entrada() {
      return this.sentido !== 'saida'
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
      contexto = {},
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
      this.contexto = contexto
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
      const dia = hoje()
      if (this.feriadosAtualizacao === dia) {
        return this.feriados
      }
      try {
        const { data } = await api.get('/v1/feriado')
        this.feriados = (data.data ?? [])
          .filter((f) => !f.inativo && f.data >= dia)
          .map((f) => String(f.data).substr(0, 10))
        this.feriadosAtualizacao = dia
      } catch (error) {
        console.log(error)
      }
      return this.feriados
    },

    // aoAtualizar: o PDV mostra as do aparelho e avisa quando chegam as do servidor
    async carregarMaquinetas(aoAtualizar = null) {
      try {
        return (await this.contexto.carregarMaquinetas?.(aoAtualizar)) ?? []
      } catch (error) {
        console.log(error)
        return []
      }
    },

    async buscarVale(codtitulo) {
      return this.contexto.buscarVale?.(codtitulo)
    },

    // documento já no servidor (o negócio sincroniza antes): { codnegocio } ou false
    async prepararDocumento() {
      const ret = await this.documento?.preparar?.()
      if (this.documento?.preparar && !ret) {
        Notify.create({
          type: 'negative',
          message: 'Impossível criar cobrança integrada para um negócio não sincronizado!',
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
      return { codnegocio: this.ehNegocio ? ret : null }
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

    // no PDV pelas rotas do dispositivo; no contas, sem PDV
    async criar(rotaPdv, rotaSemPdv, dados) {
      const doc = await this.prepararDocumento()
      if (!doc) {
        return false
      }
      const corpo = {
        ...dados,
        codnegocio: doc.codnegocio,
        codpessoa: this.pessoa?.codpessoa ?? null,
      }
      const pdv = this.contexto.pdv
      const { data } = pdv
        ? await api.post(rotaPdv, { ...corpo, pdv })
        : await api.post(rotaSemPdv, corpo)
      return data.data
    },

    descricao(codnegocio) {
      return codnegocio ? 'Negocio ' + codnegocio : 'Titulos ' + (this.pessoa?.fantasia ?? '')
    },

    async criarPixCob(valor, codportador) {
      try {
        const cob = await this.criar('/v1/pdv/pix/cob', '/v1/cobranca/pix', { valor, codportador })
        if (cob) {
          await this.depoisDeCriar('Cobrança PIX Criada!')
        }
        return cob
      } catch (error) {
        avisar(error)
        return false
      }
    },

    async criarPagarMePedido({ codpagarmepos, valor, valorparcela, valorjuros, tipo, parcelas }) {
      try {
        const ped = await this.criar('/v1/pdv/pagar-me/pedido', '/v1/cobranca/pagar-me', {
          codpagarmepos,
          valor,
          valorparcela,
          valorjuros,
          tipo,
          parcelas,
          jurosloja: true,
          descricao: this.descricao(this.documento?.codnegocio),
        })
        if (ped) {
          await this.depoisDeCriar('Cobrança Pagar Me/Stone Criada!')
        }
        return ped
      } catch (error) {
        avisar(error)
        return false
      }
    },

    async criarSaurusPedido({ codsauruspos, valor, valorparcela, valorjuros, tipo, parcelas }) {
      try {
        const ped = await this.criar('/v1/pdv/saurus/pedido', '/v1/cobranca/saurus', {
          codsauruspos,
          valor,
          valorparcela,
          valorjuros,
          tipo,
          parcelas,
          jurosloja: true,
          descricao: this.descricao(this.documento?.codnegocio),
        })
        if (ped) {
          await this.depoisDeCriar('Cobrança Saurus/Safra Pay Criada!')
        }
        return ped
      } catch (error) {
        avisar(error)
        return false
      }
    },
  },
})
