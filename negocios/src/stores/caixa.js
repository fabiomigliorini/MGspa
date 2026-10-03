// Caixa do PDV: qual é a gaveta deste PDV e se o caixa está aberto (M9 doc-3). A tela do caixa é
// a mesma do contas desde o M13 (@components/MgCaixaSessao).
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'boot/axios'
import { sincronizacaoStore } from 'stores/sincronizacao'

export const caixaStore = defineStore('caixa', {
  state: () => ({
    gaveta: null,
    carregando: false,
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
        return data.data
      } catch (error) {
        Notify.create({
          type: 'negative',
          message: error?.response?.data?.message ?? error?.message ?? String(error),
          timeout: 5000,
          actions: [{ icon: 'close', color: 'white' }],
        })
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
  },
})
