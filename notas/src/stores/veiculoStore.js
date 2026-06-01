import { defineStore } from 'pinia'
import veiculoService from '../services/veiculoService'

export const TIPO_RODADO_OPTIONS = [
  { value: 1, label: 'Truck' },
  { value: 2, label: 'Toco' },
  { value: 3, label: 'Cavalo Mecânico' },
  { value: 4, label: 'VAN' },
  { value: 5, label: 'Utilitário' },
  { value: 6, label: 'Outros' },
]

export const TIPO_CARROCERIA_OPTIONS = [
  { value: 0, label: 'Não Aplicável' },
  { value: 1, label: 'Aberto' },
  { value: 2, label: 'Fechada / Baú' },
  { value: 3, label: 'Graneleira' },
  { value: 4, label: 'Porta Container' },
  { value: 5, label: 'Sider' },
]

export const TIPO_PROPRIETARIO_OPTIONS = [
  { value: 0, label: 'TAC Agregado' },
  { value: 1, label: 'TAC Independente' },
  { value: 2, label: 'Outros' },
]

export const useVeiculoStore = defineStore('veiculo', {
  state: () => ({
    tab: 'veiculo',
    veiculos: [],
    conjuntos: [],
    tipos: [],
    loaded: false,
    loading: false,
  }),

  getters: {
    tipoLabel: (state) => (codveiculotipo) => {
      const tipo = state.tipos.find((t) => t.codveiculotipo === codveiculotipo)
      return tipo ? tipo.veiculotipo : null
    },
    tipoById: (state) => (codveiculotipo) =>
      state.tipos.find((t) => t.codveiculotipo === codveiculotipo) || null,
  },

  actions: {
    async fetchAll(force = false) {
      if (this.loaded && !force) return
      this.loading = true
      try {
        const [veiculos, conjuntos, tipos] = await Promise.all([
          veiculoService.listVeiculos(),
          veiculoService.listConjuntos(),
          veiculoService.listTipos(),
        ])
        this.veiculos = veiculos
        this.conjuntos = conjuntos
        this.tipos = tipos
        this.loaded = true
      } finally {
        this.loading = false
      }
    },

    // ----- Veículo -----
    upsertVeiculo(veiculo) {
      const idx = this.veiculos.findIndex((v) => v.codveiculo === veiculo.codveiculo)
      if (idx === -1) this.veiculos.push(veiculo)
      else this.veiculos[idx] = veiculo
    },
    removeVeiculo(codveiculo) {
      this.veiculos = this.veiculos.filter((v) => v.codveiculo !== codveiculo)
    },

    // ----- Conjunto -----
    upsertConjunto(conjunto) {
      const idx = this.conjuntos.findIndex(
        (c) => c.codveiculoconjunto === conjunto.codveiculoconjunto,
      )
      if (idx === -1) this.conjuntos.push(conjunto)
      else this.conjuntos[idx] = conjunto
    },
    removeConjunto(codveiculoconjunto) {
      this.conjuntos = this.conjuntos.filter((c) => c.codveiculoconjunto !== codveiculoconjunto)
    },

    // ----- Tipo -----
    upsertTipo(tipo) {
      const idx = this.tipos.findIndex((t) => t.codveiculotipo === tipo.codveiculotipo)
      if (idx === -1) this.tipos.push(tipo)
      else this.tipos[idx] = tipo
    },
    removeTipo(codveiculotipo) {
      this.tipos = this.tipos.filter((t) => t.codveiculotipo !== codveiculotipo)
    },
  },
})
