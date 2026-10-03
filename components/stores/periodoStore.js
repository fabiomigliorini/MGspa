// Período do portador (doc-4): o que entrou e saiu de um portador num período e quanto sobrou.
// Domínio da tela /portador/{cod}/{codperiodo} do contas e dos dialogs de movimento
// (@components/caixa: Transferir, Avulso, Item), que servem qualquer portador: gaveta, cofre,
// banco. Toda rota de movimento devolve os períodos que mexeu (R14); o store troca os que tem pelo
// que veio, sem calcular saldo.
//   contas:   carregar(codportador, codportadorperiodo) (tela do período)
//   caixa:    usar(portador, periodo, aoMudar) (MgCaixaSessao: a tela do caixa recarrega a sessão)
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { abrirPdf } from '@components/abrirPdf'

const avisar = (ok, message) =>
  Notify.create({
    type: ok ? 'positive' : 'negative',
    message,
    timeout: ok ? 2000 : 5000,
    actions: [{ icon: 'close', color: 'white' }],
  })

const erro = (error) => error?.response?.data?.message ?? error?.message ?? String(error)

export const periodoStore = defineStore('periodo', {
  state: () => ({
    // { codportador, portador, codfilial, tipo, ehCaixa (espécie), ehGaveta (com PDV), saldo, ... }
    portador: null,
    // o que o usuário pode fazer neste portador (cadastro, transferir, avulso, caixa...)
    pode: {},
    // todos os períodos do portador, sem lançamentos (as abas)
    periodos: [],
    // o período da tela, com lançamentos, resumo e (gaveta) contado × sistema e itens
    periodo: null,
    // origem escolhida no resumo (V, T, X, A, I): filtra a lista de lançamentos
    filtroOrigem: null,
    carregando: false,
    salvando: false,
    contexto: { codpdv: null, impressora: null },
    // chamado depois de cada movimento (a tela do caixa recarrega a sessão dela)
    aoMudar: null,
    // { codportador: motivo } das gavetas que não aceitam transferência agora
    bloqueios: {},
    dialogTransferir: false,
    dialogAvulso: false,
    dialogItem: false,
    // codcaixaitem já escolhido ao abrir o dialog do item
    item: null,
  }),

  actions: {
    async carregar(codportador, codportadorperiodo = null) {
      this.carregando = true
      try {
        const { data } = await api.get(
          `v1/portador/${codportador}/periodo` +
            (codportadorperiodo ? `/${codportadorperiodo}` : ''),
        )
        this.portador = {
          ...data.data.portador,
          ehGaveta: data.data.portador.gaveta,
          ehCaixa: data.data.portador.caixa,
        }
        this.pode = data.data.pode
        this.periodos = data.data.periodos
        this.periodo = data.data.periodo
        this.filtroOrigem = null
        this.aoMudar = null
      } catch (error) {
        avisar(false, erro(error))
      } finally {
        this.carregando = false
      }
    },

    // a tela do caixa (MgCaixaSessao) entrega a gaveta e a sessão para os dialogs
    usar(portador, periodo, aoMudar) {
      this.portador = { ...portador, ehGaveta: true, ehCaixa: true }
      this.periodo = periodo
      this.periodos = []
      this.aoMudar = aoMudar
    },

    // troca os períodos que vieram da rota (R14); o da tela também
    aplicar(periodos) {
      ;(periodos || []).forEach((p) => {
        if (p.codportador !== this.portador?.codportador) return
        const i = this.periodos.findIndex((x) => x.codportadorperiodo === p.codportadorperiodo)
        if (i >= 0) this.periodos.splice(i, 1, p)
        else this.periodos.push(p)
        if (p.codportadorperiodo === this.periodo?.codportadorperiodo) this.periodo = p
      })
      this.periodos.sort((a, b) => (a.inicio < b.inicio ? -1 : a.inicio > b.inicio ? 1 : 0))
      const ultimo = this.periodos[this.periodos.length - 1]
      // portador sem período: o primeiro movimento cria um, e a tela passa a mostrá-lo
      if (!this.periodo && ultimo?.lancamentos) this.periodo = ultimo
      if (this.portador && ultimo) this.portador.saldo = ultimo.saldofinal
    },

    async executar(fn, ok) {
      this.salvando = true
      try {
        const { data } = await fn()
        this.aplicar(data.periodos)
        if (ok) avisar(true, typeof ok === 'function' ? ok(data.data) : ok)
        if (this.aoMudar) await this.aoMudar()
        return data
      } catch (error) {
        avisar(false, erro(error))
        return null
      } finally {
        this.salvando = false
      }
    },

    // ==== transferências (M11): sentido E sai do portador, R chega nele ====

    async buscarBloqueios() {
      try {
        const { data } = await api.get('v1/portador/painel')
        this.bloqueios = Object.fromEntries(
          data.data.filter((p) => p.bloqueio).map((p) => [p.codportador, p.bloqueio]),
        )
      } catch {
        this.bloqueios = {}
      }
    },

    transferir({ sentido, codportador, valor, observacoes, transacao }) {
      const meu = this.portador.codportador
      return this.executar(
        () =>
          api.post('v1/pagamento/transferencia', {
            codportadororigem: sentido === 'E' ? meu : codportador,
            codportadordestino: sentido === 'E' ? codportador : meu,
            valor,
            observacoes,
            transacao: transacao || null,
          }),
        (pag) =>
          pag.estado === 'E'
            ? 'Transferência registrada'
            : `Transferência registrada: a confirmar por quem opera ${pag.portadordestino}`,
      )
    },

    confirmarTransferencia(codpagamento) {
      return this.executar(
        () => api.post(`v1/pagamento/transferencia/${codpagamento}/confirmar`),
        'Transferência confirmada',
      )
    },

    cancelarTransferencia(codpagamento, justificativa) {
      return this.executar(
        () => api.post(`v1/pagamento/transferencia/${codpagamento}/cancelar`, { justificativa }),
        'Transferência cancelada',
      )
    },

    // ==== avulso: no caixa (espécie) pela sessão; nos demais pelo período da data (M12) ====

    lancarAvulso({ sentido, motivo, valor, observacoes, transacao }) {
      if (this.portador.ehCaixa) {
        return this.executar(
          () =>
            api.post(`v1/caixa/sessao/${this.periodo.codportadorperiodo}/avulso`, {
              sentido,
              motivo,
              valor,
              observacoes,
              transacao: transacao || null,
              codpdv: this.contexto.codpdv,
            }),
          'Lançamento registrado',
        )
      }
      return this.executar(
        () =>
          api.post('v1/portador-periodo/lancamento', {
            codportador: this.portador.codportador,
            motivo,
            valor: sentido === 'E' ? valor : -valor,
            transacao: transacao || null,
            observacoes,
          }),
        'Lançamento registrado',
      )
    },

    cancelarAvulso(codpagamento, justificativa) {
      return this.executar(
        () =>
          this.portador.ehCaixa
            ? api.post(`v1/caixa/avulso/${codpagamento}/cancelar`)
            : api.post(`v1/portador-periodo/lancamento/${codpagamento}/cancelar`, {
                justificativa,
              }),
        'Lançamento cancelado',
      )
    },

    // ==== caixa (espécie): abrir, fechar e reabrir; itens só na gaveta (M13) ====

    abrirItem(codcaixaitem = null) {
      this.item = codcaixaitem
      this.dialogItem = true
    },

    salvarItem(codcaixaitem, payload) {
      return this.executar(
        () =>
          api.post(`v1/caixa/sessao/${this.periodo.codportadorperiodo}/item/${codcaixaitem}`, {
            ...payload,
            codpdv: this.contexto.codpdv,
          }),
        'Item salvo',
      )
    },

    // a gaveta antes de abrir: o envelope e os itens ativos da filial (para a contagem)
    async gaveta() {
      try {
        const { data } = await api.get(`v1/caixa/gaveta/${this.portador.codportador}`)
        return data.data
      } catch (error) {
        avisar(false, erro(error))
        return null
      }
    },

    // devolve o período novo (a tela vai para ele)
    async abrirCaixa(payload) {
      const data = await this.executar(
        () =>
          api.post(`v1/caixa/gaveta/${this.portador.codportador}/abrir`, {
            ...payload,
            codpdv: this.contexto.codpdv,
          }),
        'Caixa aberto',
      )
      return data?.data?.codportadorperiodo ?? null
    },

    fecharCaixa(payload) {
      return this.executar(
        () =>
          api.post(`v1/caixa/sessao/${this.periodo.codportadorperiodo}/fechar`, {
            ...payload,
            codpdv: this.contexto.codpdv,
            impressora: this.contexto.impressora || null,
          }),
        'Caixa fechado',
      )
    },

    // corrige o início e o fim da sessão não fechada
    editarDatas({ inicio, fim, observacoes }) {
      return this.executar(
        () =>
          api.post(`v1/caixa/sessao/${this.periodo.codportadorperiodo}/datas`, {
            inicio,
            fim,
            observacoes,
          }),
        'Salvo',
      )
    },

    reabrirCaixa() {
      return this.executar(
        () => api.post(`v1/caixa/sessao/${this.periodo.codportadorperiodo}/reabrir`),
        'Caixa reaberto',
      )
    },

    abrirBordero() {
      return abrirPdf(
        api,
        `v1/caixa/sessao/${this.periodo.codportadorperiodo}/bordero`,
        {},
        { title: 'Borderô do Caixa', size: 'cupom' },
      )
    },

    // ==== demais portadores: fechar com corte e reabrir (M12) ====

    fecharPeriodo(corte) {
      return this.executar(
        () =>
          api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/fechar`, {
            corte: corte || null,
          }),
        'Período fechado',
      )
    },

    // contagem do caixa na abertura ou no fechamento (só registra; a diferença aparece)
    contar(momento, { contagem, itens }) {
      return this.executar(
        () =>
          api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/contagem`, {
            momento,
            contagem,
            itens,
          }),
        'Contagem salva',
      )
    },

    reabrirPeriodo() {
      return this.executar(
        () => api.post(`v1/portador-periodo/${this.periodo.codportadorperiodo}/reabrir`),
        'Período reaberto',
      )
    },
  },
})
