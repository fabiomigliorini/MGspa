import { defineStore } from 'pinia'
import cidadeService from '../services/cidadeService'

export const useCidadeStore = defineStore('cidade', {
  persist: {
    pick: ['filters', 'selectedPais', 'selectedEstado'],
  },
  state: () => ({
    // Paises e Estados
    paises: [],
    estados: [],

    // Lista de Cidades
    cidades: [],
    pagination: {
      page: 1,
      perPage: 20,
      hasMore: true,
      loading: false,
      total: 0,
    },

    // Selecoes
    selectedPais: 1, // Brasil por padrao
    selectedEstado: 8956, // Mato Grosso por padrao

    // Filtros
    filters: {
      cidade: null,
      codigooficial: null,
    },

    // Flags
    initialLoadDone: false,
    paisesLoaded: false,
    estadosLoaded: false,

    // Cidade atual (para edicao)
    currentCidade: null,

    // Loading states
    loading: {
      paises: false,
      estados: false,
      cidade: false,
    },
  }),

  getters: {
    // Verifica se ha filtros ativos
    hasActiveFilters: (state) => {
      return Object.values(state.filters).some((value) => value !== null && value !== '')
    },

    // Conta quantos filtros estao ativos
    activeFiltersCount: (state) => {
      return Object.values(state.filters).filter((value) => value !== null && value !== '').length
    },

    // Pais selecionado
    currentPais: (state) => {
      return state.paises.find((p) => p.codpais === state.selectedPais)
    },

    // Estado selecionado
    currentEstado: (state) => {
      return state.estados.find((e) => e.codestado === state.selectedEstado)
    },

    // Estados ordenados por sigla
    estadosOrdenados: (state) => {
      return [...state.estados].sort((a, b) => a.sigla.localeCompare(b.sigla))
    },
  },

  actions: {
    async fetchPaises(forceReload = false) {
      if (this.paisesLoaded && !forceReload) return this.paises

      this.loading.paises = true
      try {
        const response = await cidadeService.listPaises()
        this.paises = response.data || []
        this.paisesLoaded = true
        return this.paises
      } catch (error) {
        console.error('Erro ao buscar paises:', error)
        throw error
      } finally {
        this.loading.paises = false
      }
    },

    async createPais(data) {
      this.loading.paises = true
      try {
        const response = await cidadeService.createPais(data)
        // Recarrega lista de paises
        await this.fetchPaises(true)
        return response.data
      } catch (error) {
        console.error('Erro ao criar pais:', error)
        throw error
      } finally {
        this.loading.paises = false
      }
    },

    async deletePais(codpais) {
      this.loading.paises = true
      try {
        await cidadeService.deletePais(codpais)

        // Remove da lista
        this.paises = this.paises.filter((p) => p.codpais !== codpais)

        // Se o pais excluido era o selecionado, seleciona outro
        if (this.selectedPais === codpais) {
          if (this.paises.length > 0) {
            this.selectedPais = this.paises[0].codpais
          } else {
            this.selectedPais = null
          }
          // Limpa estados e cidades
          this.estados = []
          this.cidades = []
          this.estadosLoaded = false
          this.initialLoadDone = false
        }
      } catch (error) {
        console.error('Erro ao excluir pais:', error)
        throw error
      } finally {
        this.loading.paises = false
      }
    },

    async fetchEstados(codpais = null) {
      const pais = codpais || this.selectedPais
      if (!pais) return []

      this.loading.estados = true
      try {
        const response = await cidadeService.listEstados(pais)
        // O service ja retorna response.data, entao a lista pode estar em response.data ou diretamente em response
        this.estados = response.data || response || []
        this.estadosLoaded = true

        // Verifica se o estado selecionado existe na lista carregada
        const estadoExiste = this.estados.some((e) => e.codestado === this.selectedEstado)

        // Se nao tem estado selecionado ou o selecionado nao existe, seleciona MT ou o primeiro
        if (!this.selectedEstado || !estadoExiste) {
          // Tenta encontrar Mato Grosso (MT)
          const mt = this.estados.find((e) => e.sigla === 'MT')
          if (mt) {
            this.selectedEstado = mt.codestado
          } else if (this.estados.length > 0) {
            const ordenados = [...this.estados].sort((a, b) => a.sigla.localeCompare(b.sigla))
            this.selectedEstado = ordenados[0].codestado
          }
        }

        return this.estados
      } catch (error) {
        console.error('Erro ao buscar estados:', error)
        throw error
      } finally {
        this.loading.estados = false
      }
    },

    async createEstado(data) {
      this.loading.estados = true
      try {
        const response = await cidadeService.createEstado(this.selectedPais, data)
        // O service ja retorna response.data, entao a resposta do Laravel Resource vem em response.data
        const novoEstado = response.data || response
        if (novoEstado && novoEstado.codestado) {
          this.estados.push(novoEstado)
          // Seleciona o novo estado automaticamente
          this.selectedEstado = novoEstado.codestado
        }
        return novoEstado
      } catch (error) {
        console.error('Erro ao criar estado:', error)
        throw error
      } finally {
        this.loading.estados = false
      }
    },

    async updateEstado(codestado, data) {
      this.loading.estados = true
      try {
        const response = await cidadeService.updateEstado(this.selectedPais, codestado, data)
        // A resposta do Laravel Resource vem em response.data
        const estadoAtualizado = response.data || response

        // Atualiza na lista
        const index = this.estados.findIndex((e) => e.codestado === codestado)
        if (index !== -1) {
          this.estados[index] = { ...this.estados[index], ...estadoAtualizado }
        }

        return estadoAtualizado
      } catch (error) {
        console.error('Erro ao atualizar estado:', error)
        throw error
      } finally {
        this.loading.estados = false
      }
    },

    async deleteEstado(codestado) {
      this.loading.estados = true
      try {
        await cidadeService.deleteEstado(this.selectedPais, codestado)

        // Remove da lista
        this.estados = this.estados.filter((e) => e.codestado !== codestado)

        // Se o estado excluido era o selecionado, seleciona outro
        if (this.selectedEstado === codestado) {
          if (this.estados.length > 0) {
            const ordenados = [...this.estados].sort((a, b) => a.sigla.localeCompare(b.sigla))
            this.selectedEstado = ordenados[0].codestado
          } else {
            this.selectedEstado = null
          }
          // Limpa cidades
          this.cidades = []
          this.initialLoadDone = false
        }
      } catch (error) {
        console.error('Erro ao excluir estado:', error)
        throw error
      } finally {
        this.loading.estados = false
      }
    },

    async fetchCidades(reset = false) {
      // Se ja esta carregando ou nao tem mais dados, retorna
      if (this.pagination.loading || (!reset && !this.pagination.hasMore)) {
        return
      }

      // Precisa ter pais e estado selecionados
      if (!this.selectedPais || !this.selectedEstado) {
        return
      }

      // Se e reset, limpa os dados
      if (reset) {
        this.cidades = []
        this.pagination.page = 1
        this.pagination.hasMore = true
      }

      try {
        this.pagination.loading = true

        const params = {
          ...this.filters,
          page: this.pagination.page,
          per_page: this.pagination.perPage,
        }

        const response = await cidadeService.listCidades(
          this.selectedPais,
          this.selectedEstado,
          params
        )

        const newCidades = response.data || []

        // Adiciona novas cidades a lista existente
        if (reset) {
          this.cidades = newCidades
        } else {
          this.cidades.push(...newCidades)
        }

        // Atualiza paginacao
        this.pagination.total = response.meta?.total || newCidades.length
        this.pagination.hasMore = response.meta
          ? response.meta.current_page < response.meta.last_page
          : false

        // Incrementa pagina para proxima busca
        if (this.pagination.hasMore) {
          this.pagination.page++
        }

        this.initialLoadDone = true

        return response
      } catch (error) {
        console.error('Erro ao buscar cidades:', error)
        throw error
      } finally {
        this.pagination.loading = false
      }
    },

    async fetchCidade(codcidade) {
      const codcidadeNum = parseInt(codcidade)

      if (this.currentCidade && this.currentCidade.codcidade === codcidadeNum) {
        return this.currentCidade
      }

      this.loading.cidade = true
      try {
        const response = await cidadeService.getCidade(
          this.selectedPais,
          this.selectedEstado,
          codcidadeNum
        )
        this.currentCidade = response.data
        return response.data
      } catch (error) {
        console.error('Erro ao buscar cidade:', error)
        throw error
      } finally {
        this.loading.cidade = false
      }
    },

    async createCidade(data) {
      this.loading.cidade = true
      try {
        const response = await cidadeService.createCidade(
          this.selectedPais,
          this.selectedEstado,
          data
        )
        this.currentCidade = response.data

        // Recarrega lista
        await this.fetchCidades(true)

        return response.data
      } catch (error) {
        console.error('Erro ao criar cidade:', error)
        throw error
      } finally {
        this.loading.cidade = false
      }
    },

    async updateCidade(codcidade, data) {
      this.loading.cidade = true
      try {
        const response = await cidadeService.updateCidade(
          this.selectedPais,
          this.selectedEstado,
          codcidade,
          data
        )
        this.currentCidade = response.data

        // Atualiza na lista se existir
        const index = this.cidades.findIndex((c) => c.codcidade === codcidade)
        if (index !== -1) {
          this.cidades[index] = { ...response.data }
        }

        return response.data
      } catch (error) {
        console.error('Erro ao atualizar cidade:', error)
        throw error
      } finally {
        this.loading.cidade = false
      }
    },

    async deleteCidade(codcidade) {
      this.loading.cidade = true
      try {
        await cidadeService.deleteCidade(this.selectedPais, this.selectedEstado, codcidade)

        // Remove da lista
        this.cidades = this.cidades.filter((c) => c.codcidade !== codcidade)
        this.pagination.total--

        // Limpa a cidade atual se for a mesma
        if (this.currentCidade?.codcidade === codcidade) {
          this.clearCurrentCidade()
        }
      } catch (error) {
        console.error('Erro ao excluir cidade:', error)
        throw error
      } finally {
        this.loading.cidade = false
      }
    },

    selectPais(codpais) {
      this.selectedPais = codpais
      this.selectedEstado = null
      this.estadosLoaded = false
      this.estados = []
      this.cidades = []
      this.initialLoadDone = false
    },

    selectEstado(codestado) {
      this.selectedEstado = codestado
      this.cidades = []
      this.initialLoadDone = false
      this.pagination.page = 1
      this.pagination.hasMore = true
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
      this.initialLoadDone = false
    },

    clearFilters() {
      this.filters = {
        cidade: null,
        codigooficial: null,
      }
      this.initialLoadDone = false
    },

    clearCurrentCidade() {
      this.currentCidade = null
    },
  },
})
