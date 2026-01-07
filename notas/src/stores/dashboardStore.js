import { defineStore } from 'pinia'
import dashboardService from '../services/dashboardService'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    dados: null,
    loading: false,
    error: null,
    lastUpdate: null,
  }),

  getters: {},

  actions: {
    async fetchDashboard() {
      this.loading = true
      this.error = null

      try {
        const response = await dashboardService.getDashboard()
        this.dados = response.data
        this.lastUpdate = new Date()
      } catch (error) {
        console.error('Erro ao buscar dados do dashboard:', error)
        this.error = error.message || 'Erro ao carregar dashboard'
        throw error
      } finally {
        this.loading = false
      }
    },

    clearData() {
      this.dados = null
      this.error = null
      this.lastUpdate = null
    },
  },
})
