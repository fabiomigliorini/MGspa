import { defineStore } from 'pinia'
import mdfeService from '../services/mdfeService'

export const TIPO_EMITENTE_OPTIONS = [
  { value: 1, label: 'Prestador de Serviço' },
  { value: 2, label: 'Transportador Carga Própria' },
  { value: 3, label: 'Prestador de Serviço - CTe Globalizado' },
]

export const TIPO_TRANSPORTADOR_OPTIONS = [
  { value: 1, label: 'ETC - Empresa' },
  { value: 2, label: 'TAC - Autônomo' },
  { value: 3, label: 'CTC - Cooperativa' },
]

export const MODAL_OPTIONS = [
  { value: 1, label: 'Rodoviário' },
  { value: 2, label: 'Aéreo' },
  { value: 3, label: 'Aquaviário' },
  { value: 4, label: 'Ferroviário' },
]

export const TIPO_EMISSAO_OPTIONS = [
  { value: 1, label: 'Normal' },
  { value: 2, label: 'Contingência' },
]

// Cores de status (paleta Quasar)
const STATUS_COLORS = {
  1: 'amber', // EM_DIGITACAO
  2: 'orange', // TRANSMITIDA
  3: 'teal', // AUTORIZADA
  4: 'red', // NAO_AUTORIZADA
  5: 'blue-grey', // ENCERRADA
  9: 'red-10', // CANCELADA
}

export function statusColor(codmdfestatus) {
  return STATUS_COLORS[codmdfestatus] || 'grey'
}

export const labelDe = (options, value) => options.find((o) => o.value === value)?.label ?? null

export const useMdfeStore = defineStore('mdfe', {
  state: () => ({
    mdfes: [],
    pagination: { page: 1, perPage: 100, hasMore: true, loading: false },
    initialLoadDone: false,
    currentMdfe: null,
  }),

  actions: {
    async fetchMdfes(reset = false) {
      if (this.pagination.loading || (!reset && !this.pagination.hasMore)) return
      if (reset) {
        this.mdfes = []
        this.pagination.page = 1
        this.pagination.hasMore = true
      }
      this.pagination.loading = true
      try {
        const response = await mdfeService.list({ page: this.pagination.page })
        const novos = response.data || []
        if (reset) this.mdfes = novos
        else this.mdfes.push(...novos)
        // simplePaginate: há mais se veio página cheia / existe link next
        this.pagination.hasMore = !!response.links?.next || novos.length === this.pagination.perPage
        if (this.pagination.hasMore) this.pagination.page++
        this.initialLoadDone = true
        return response
      } finally {
        this.pagination.loading = false
      }
    },

    async fetchMdfe(codmdfe) {
      this.currentMdfe = await mdfeService.get(codmdfe)
      return this.currentMdfe
    },

    upsertMdfe(mdfe) {
      const idx = this.mdfes.findIndex((m) => m.codmdfe === mdfe.codmdfe)
      if (idx === -1) this.mdfes.unshift(mdfe)
      else this.mdfes[idx] = mdfe
    },
  },
})
