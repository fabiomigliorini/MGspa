// Tela do caixa (MgCaixaSessao, M13 do plano doc-3): a mesma no PDV e no contas. Abre e fecha
// a sessão da gaveta contando cédulas, moedas e o estoque dos itens (chips, ingressos), mantém os
// itens dos parceiros, os avulsos e as transferências. Fechar é a conferência: um ajuste se o
// contado difere do sistema e os títulos de repasse dos itens. Os dialogs de movimento
// (transferir, avulso, item) são do periodoStore (doc-4), que serve qualquer portador.
//   negocios: carregarGaveta(codportador da gaveta do PDV), contexto { codpdv, impressora }
//   contas:   carregarSessao(codportadorperiodo) (Fechamentos)
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

// cédulas e moedas da contagem (as mesmas chaves do servidor, CaixaService::CEDULAS/MOEDAS)
export const CEDULAS = ['200', '100', '50', '20', '10', '5', '2']
export const MOEDAS = ['1', '0.50', '0.25', '0.10', '0.05', '0.01']

// taxa, tarifa e rendimento: só banco (pagamento sem pessoa); o ajuste é movimento do portador
export const MOTIVOS = [
  { value: 'T', label: 'Taxa' },
  { value: 'F', label: 'Tarifa' },
  { value: 'R', label: 'Rendimento' },
]

export const DOCUMENTOS = {
  V: 'Vendas',
  I: 'Itens do caixa',
  J: 'Ajustes de caixa',
  T: 'Títulos (notinhas, vales, adiantamentos)',
  X: 'Transferências (sangria, reforço)',
  A: 'Taxas e tarifas',
}

export const caixaSessaoStore = defineStore('caixaSessao', {
  state: () => ({
    // a gaveta (modo PDV) ou a da sessão aberta pelo contas
    gaveta: null,
    sessao: null,
    envelope: 0,
    // itens ativos da filial, para a contagem da abertura
    itensAtivos: [],
    carregando: false,
    salvando: false,
    contexto: { codpdv: null, impressora: null },
  }),

  getters: {
    aberta: (state) => !!state.sessao?.aberta,
  },

  actions: {
    async executar(fn, ok) {
      this.salvando = true
      try {
        const { data } = await fn()
        this.sessao = data.data
        if (ok) avisar(true, ok)
        return true
      } catch (error) {
        avisar(false, erro(error))
        return false
      } finally {
        this.salvando = false
      }
    },

    async carregarGaveta(codportador) {
      this.carregando = true
      try {
        const { data } = await api.get(`v1/caixa/gaveta/${codportador}`, {
          params: { codpdv: this.contexto.codpdv || undefined },
        })
        this.gaveta = data.data.gaveta
        this.sessao = data.data.sessao
        this.envelope = data.data.envelope
        this.itensAtivos = data.data.itens
      } catch (error) {
        avisar(false, erro(error))
      } finally {
        this.carregando = false
      }
    },

    async carregarSessao(id) {
      this.carregando = true
      this.sessao = null
      try {
        const { data } = await api.get(`v1/caixa/sessao/${id}`, {
          params: { codpdv: this.contexto.codpdv || undefined },
        })
        this.sessao = data.data
        this.gaveta = {
          codportador: data.data.codportador,
          portador: data.data.portador,
          codfilial: data.data.codfilial,
        }
      } catch (error) {
        avisar(false, erro(error))
      } finally {
        this.carregando = false
      }
    },

    recarregar() {
      return this.sessao?.aberta || !this.sessao
        ? this.carregarGaveta(this.gaveta.codportador)
        : this.carregarSessao(this.sessao.codportadorperiodo)
    },

    abrir(payload) {
      return this.executar(
        () =>
          api.post(`v1/caixa/gaveta/${this.gaveta.codportador}/abrir`, {
            ...payload,
            codpdv: this.contexto.codpdv,
          }),
        'Caixa aberto',
      )
    },

    fechar(payload) {
      return this.executar(
        () =>
          api.post(`v1/caixa/sessao/${this.sessao.codportadorperiodo}/fechar`, {
            ...payload,
            codpdv: this.contexto.codpdv,
            impressora: this.contexto.impressora || null,
          }),
        'Caixa fechado',
      )
    },

    reabrir() {
      return this.executar(
        () => api.post(`v1/caixa/sessao/${this.sessao.codportadorperiodo}/reabrir`),
        'Caixa reaberto',
      )
    },

    // o ajuste (movimento do portador) se cancela, não se apaga
    cancelarAvulso(codportadormovimento) {
      return this.executar(
        () =>
          api.post(`v1/caixa/avulso/${codportadormovimento}/cancelar`, {
            codpdv: this.contexto.codpdv,
          }),
        'Ajuste cancelado',
      )
    },

    // ==== transferências da gaveta (M11): confirmar e cancelar pela lista da sessão; registrar é
    // o TransferirCaixaDialog (periodoStore) ====

    async acaoTransferencia(codportadormovimento, acao, payload = {}) {
      try {
        await api.post(`v1/portador-movimento/${codportadormovimento}/${acao}`, {
          ...payload,
          codpdv: this.contexto.codpdv,
        })
        avisar(true, acao === 'confirmar' ? 'Transferência confirmada' : 'Transferência cancelada')
        await this.recarregar()
      } catch (error) {
        avisar(false, erro(error))
      }
    },

    // ==== borderô ====

    abrirBordero(codportadorperiodo) {
      return abrirPdf(
        api,
        `v1/caixa/sessao/${codportadorperiodo}/bordero`,
        {},
        { title: 'Borderô do Caixa', size: 'cupom' },
      )
    },

    async imprimirBordero(codportadorperiodo) {
      if (!this.contexto.impressora) {
        return this.abrirBordero(codportadorperiodo)
      }
      try {
        await api.post(`v1/caixa/sessao/${codportadorperiodo}/bordero/${this.contexto.impressora}`)
        avisar(true, 'Borderô enviado para a impressora')
      } catch (error) {
        avisar(false, erro(error))
      }
    },
  },
})
