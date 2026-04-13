import { defineStore } from 'pinia'
import api from '../services/api'

const BASE_URL = '/v1/nota-fiscal/dashboard'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    loading: false,
    loadingVolumeMensal: false,
    period: 7,
    modelo: 'ambos', // 'nfe', 'nfce', 'ambos'
    sefazStatus: null,
    kpisGerais: null,
    kpisPorNatureza: [],
    volumeMensal: [],
    erroPorFilial: [],
    kpisPorFilial: [],
    errors: {},
  }),

  getters: {
    // KPIs calculados
    totalNotas: (state) => state.kpisGerais?.total_notas || 0,
    percentAutorizadas: (state) => state.kpisGerais?.autorizadas?.percentual || 0,
    percentErro: (state) => state.kpisGerais?.erro?.percentual || 0,
    percentDigitacao: (state) => state.kpisGerais?.digitacao?.percentual || 0,
    percentCanceladas: (state) => state.kpisGerais?.canceladas?.percentual || 0,
    percentInutilizadas: (state) => state.kpisGerais?.inutilizadas?.percentual || 0,

    // Status SEFAZ
    sefazOnline: (state) => state.sefazStatus?.status === 'online',
    sefazInstavel: (state) => state.sefazStatus?.status === 'instavel',
    sefazOffline: (state) => state.sefazStatus?.status === 'offline',

    // Dados filtrados por modelo
    volumeMensalFiltrado: (state) => {
      if (state.modelo === 'ambos') return state.volumeMensal
      return state.volumeMensal.map((item) => ({
        ...item,
        nfe: state.modelo === 'nfe' ? item.nfe : 0,
        nfce: state.modelo === 'nfce' ? item.nfce : 0,
      }))
    },
  },

  actions: {
    setPeriod(period) {
      this.period = period
    },

    setModelo(modelo) {
      this.modelo = modelo
    },

    async fetchAll() {
      this.loading = true
      this.errors = {}

      try {
        await Promise.all([
          this.fetchSefazStatus(),
          this.fetchKpisGerais(),
          this.fetchVolumeMensal(),
          this.fetchErroPorFilial(),
          this.fetchKpisPorFilial(),
        ])
      } catch (error) {
        console.error('Erro ao carregar dashboard:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchByPeriod() {
      this.loading = true
      this.errors = {}

      try {
        await Promise.all([
          this.fetchKpisGerais(),
          this.fetchErroPorFilial(),
          this.fetchKpisPorFilial(),
        ])
      } catch (error) {
        console.error('Erro ao carregar dashboard:', error)
      } finally {
        this.loading = false
      }
    },

    async fetchSefazStatus(codFilial = 101) {
      try {
        const response = await api.get(`${BASE_URL}/sefaz/status/${codFilial}`)
        this.sefazStatus = response.data.data || response.data
      } catch (error) {
        console.error('Erro ao buscar status SEFAZ:', error)
        this.errors.sefaz = error.message
        this.sefazStatus = { status: 'offline', mensagem: 'Erro ao consultar' }
      }
    },

    async fetchKpisGerais() {
      try {
        const params = { period: this.period }
        if (this.modelo !== 'ambos') params.modelo = this.modelo
        const response = await api.get(`${BASE_URL}/kpis/gerais`, { params })
        this.kpisGerais = response.data.data || response.data
      } catch (error) {
        console.error('Erro ao buscar KPIs gerais:', error)
        this.errors.kpisGerais = error.message
        this.kpisGerais = {
          total_notas: 0,
          autorizadas: { quantidade: 0, percentual: 0 },
          erro: { quantidade: 0, percentual: 0 },
          digitacao: { quantidade: 0, percentual: 0 },
          canceladas: { quantidade: 0, percentual: 0 },
          inutilizadas: { quantidade: 0, percentual: 0 },
        }
      }
    },

    async fetchKpisPorNatureza() {
      try {
        const params = { period: this.period }
        if (this.modelo !== 'ambos') params.modelo = this.modelo
        const response = await api.get(`${BASE_URL}/kpis/por-natureza`, { params })
        this.kpisPorNatureza = response.data.data || response.data || []
      } catch (error) {
        console.error('Erro ao buscar KPIs por natureza:', error)
        this.errors.kpisPorNatureza = error.message
        this.kpisPorNatureza = []
      }
    },

    async fetchVolumeMensal() {
      this.loadingVolumeMensal = true
      try {
        const response = await api.get(`${BASE_URL}/graficos/volume-mensal`)
        this.volumeMensal = response.data.data || response.data || []
      } catch (error) {
        console.error('Erro ao buscar volume mensal:', error)
        this.errors.volumeMensal = error.message
        this.volumeMensal = []
      } finally {
        this.loadingVolumeMensal = false
      }
    },

    async fetchErroPorFilial() {
      try {
        const params = { period: this.period }
        const response = await api.get(`${BASE_URL}/graficos/erro-por-filial`, { params })
        this.erroPorFilial = response.data.data || response.data || []
      } catch (error) {
        console.error('Erro ao buscar erro por filial:', error)
        this.errors.erroPorFilial = error.message
        this.erroPorFilial = []
      }
    },

    async fetchKpisPorFilial() {
      try {
        const params = { period: this.period }
        if (this.modelo !== 'ambos') params.modelo = this.modelo
        const response = await api.get(`${BASE_URL}/kpis/por-filial`, { params })
        this.kpisPorFilial = response.data.data || response.data || []
      } catch (error) {
        console.error('Erro ao buscar KPIs por filial:', error)
        this.errors.kpisPorFilial = error.message
        this.kpisPorFilial = []
      }
    },

    clearData() {
      this.sefazStatus = null
      this.kpisGerais = null
      this.kpisPorNatureza = []
      this.volumeMensal = []
      this.erroPorFilial = []
      this.kpisPorFilial = []
      this.errors = {}
    },
  },
})
