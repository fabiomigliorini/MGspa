// Caixa do PDV (M9 doc-3): a sessão da gaveta — abrir e fechar o dinheiro com contagem e o
// borderô do caixa, que sobe ao escritório junto com o dinheiro. O caixa não vê o dinheiro do
// sistema (o gerente confere às cegas, no contas → Fechamentos).
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import { abrirPdf } from '@components/abrirPdf'
import { sincronizacaoStore } from 'stores/sincronizacao'

const avisar = (ok, message) =>
  Notify.create({
    type: ok ? 'positive' : 'negative',
    message,
    timeout: ok ? 2000 : 5000,
    actions: [{ icon: 'close', color: 'white' }],
  })

const erro = (error) => error?.response?.data?.message ?? error?.message ?? String(error)

export const caixaStore = defineStore('caixa', {
  state: () => ({
    gaveta: null,
    sessao: null,
    ultima: null,
    carregando: false,
    salvando: false,
    // transferências da gaveta (M11): as da sessão e as a confirmar
    transferencias: [],
    dialogTransferir: false,
    // { codportador: motivo } das gavetas que não aceitam transferência agora
    bloqueios: {},
  }),

  actions: {
    pdv() {
      return sincronizacaoStore().pdv.uuid
    },

    async status() {
      this.carregando = true
      try {
        const { data } = await api.get('/v1/pdv/caixa', { params: { pdv: this.pdv() } })
        this.gaveta = data.data.gaveta
        this.sessao = data.data.sessao
        this.ultima = data.data.ultima
        return data.data
      } catch (error) {
        avisar(false, erro(error))
        return null
      } finally {
        this.carregando = false
      }
    },

    // motivo de o Dinheiro estar bloqueado no wizard (nulo = liberado). Offline não bloqueia: o
    // servidor recusa no fechar.
    async bloqueioDinheiro() {
      try {
        const { data } = await api.get('/v1/pdv/caixa', {
          params: { pdv: this.pdv() },
          timeout: 3000,
          skipLoading: true,
        })
        if (!data.data.gaveta) {
          return 'PDV sem gaveta: peça ao administrador para vincular'
        }
        if (!data.data.sessao) {
          return 'Caixa fechado: abra o caixa (menu Caixa)'
        }
        return null
      } catch {
        return null
      }
    },

    async abrir(contagem) {
      this.salvando = true
      try {
        const { data } = await api.post('/v1/pdv/caixa/abrir', { pdv: this.pdv(), ...contagem })
        this.sessao = data.data
        this.ultima = null
        avisar(true, 'Caixa aberto')
        return true
      } catch (error) {
        avisar(false, erro(error))
        return false
      } finally {
        this.salvando = false
      }
    },

    async fechar(contagem, impressora) {
      this.salvando = true
      try {
        const { data } = await api.post('/v1/pdv/caixa/fechar', {
          pdv: this.pdv(),
          impressora: impressora || null,
          ...contagem,
        })
        this.ultima = data.data
        this.sessao = null
        avisar(true, 'Caixa fechado: leve o dinheiro e o borderô ao escritório')
        return true
      } catch (error) {
        avisar(false, erro(error))
        return false
      } finally {
        this.salvando = false
      }
    },

    async imprimirBordero(codportadorperiodo, impressora) {
      if (!impressora) {
        return this.abrirBordero(codportadorperiodo)
      }
      try {
        await api.post(`/v1/pdv/caixa/${codportadorperiodo}/bordero/${impressora}`, {
          pdv: this.pdv(),
        })
        avisar(true, 'Borderô enviado para a impressora')
      } catch (error) {
        avisar(false, erro(error))
      }
    },

    // ==== transferências (M11 doc-3) ====

    async buscarTransferencias() {
      try {
        const { data } = await api.get('/v1/pdv/caixa/transferencia', {
          params: { pdv: this.pdv() },
        })
        this.transferencias = data.data
      } catch (error) {
        avisar(false, erro(error))
      }
    },

    // gavetas fechadas aparecem desabilitadas no destino, com o motivo
    async buscarBloqueios() {
      try {
        const { data } = await api.get('/v1/portador/caixas')
        this.bloqueios = Object.fromEntries(
          data.data.filter((c) => c.bloqueio).map((c) => [c.codportador, c.bloqueio]),
        )
      } catch {
        this.bloqueios = {}
      }
    },

    // sentido E = sai da gaveta, R = chega nela
    async transferir(dados) {
      this.salvando = true
      try {
        const { data } = await api.post('/v1/pdv/caixa/transferencia', {
          pdv: this.pdv(),
          ...dados,
        })
        avisar(
          true,
          data.data.estado === 'E'
            ? 'Transferência registrada'
            : `Transferência registrada: a confirmar por quem opera ${data.data.portadordestino}`,
        )
        await this.buscarTransferencias()
        return true
      } catch (error) {
        avisar(false, erro(error))
        return false
      } finally {
        this.salvando = false
      }
    },

    async acaoTransferencia(codpagamento, acao, payload = {}) {
      try {
        await api.post(`/v1/pdv/caixa/transferencia/${codpagamento}/${acao}`, {
          pdv: this.pdv(),
          ...payload,
        })
        avisar(true, acao === 'confirmar' ? 'Transferência confirmada' : 'Transferência cancelada')
        await this.buscarTransferencias()
      } catch (error) {
        avisar(false, erro(error))
      }
    },

    abrirBordero(codportadorperiodo) {
      return abrirPdf(
        api,
        `/v1/pdv/caixa/${codportadorperiodo}/bordero`,
        {},
        {
          title: 'Borderô do Caixa',
          size: 'cupom',
        },
      )
    },
  },
})
