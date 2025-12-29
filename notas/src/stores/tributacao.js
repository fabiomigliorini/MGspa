import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useTributacaoStore = defineStore('tributacao', {
  state: () => ({
    // Lista de tributos disponíveis (vem da API)
    tributos: [],

    // Tabs
    activeTab: null, // será definido dinamicamente após carregar tributos

    // Dados das regras por tributo (dinâmico)
    regras: {},

    // Controle de paginação/scroll infinito (dinâmico)
    pagination: {},

    // Filtros
    filters: {
      codnaturezaoperacao: null,
      codtipoproduto: null,
      ncm: null,
      codcidadedestino: null,
      codestadodestino: null,
      tipocliente: null, // 'PFC' | 'PFN' | 'PJC' | 'PJN' | null
      basepercentual: null,
      aliquota: null,
      cst: null,
      cclasstrib: null,
      geracredito: null, // null | true | false
      beneficiocodigo: null,
      vigencia: null, // 'vigente' | 'futuro' | 'expirado' | null
    },

    // Controle das drawers
    leftDrawerOpen: false,
    rightDrawerOpen: false,

    // Loading geral
    isLoading: false,
    tributosLoading: false,

    // Erro
    error: null,
  }),

  getters: {
    // Retorna tributo ativo
    currentTributo: (state) => {
      return state.tributos.find((t) => t.codtributo === state.activeTab)
    },

    // Retorna as regras da aba ativa
    currentRegras: (state) => {
      return state.regras[state.activeTab] || []
    },

    // Retorna a paginação da aba ativa
    currentPagination: (state) => {
      return state.pagination[state.activeTab]
    },

    // Verifica se há filtros ativos
    hasActiveFilters: (state) => {
      return Object.values(state.filters).some((value) => value !== null && value !== '')
    },

    // Conta quantos filtros estão ativos
    activeFiltersCount: (state) => {
      return Object.values(state.filters).filter((value) => value !== null && value !== '').length
    },

    // Retorna filtros como query params
    filtersAsParams: (state) => {
      const params = {}
      Object.entries(state.filters).forEach(([key, value]) => {
        if (value !== null && value !== '') {
          // Converte o filtro de vigência para o formato esperado pelo backend
          if (key === 'vigencia') {
            const hoje = new Date().toISOString().split('T')[0] // YYYY-MM-DD

            if (value === 'vigente') {
              // Vigentes hoje
              params.vigencia_em = hoje
            } else if (value === 'futuro') {
              // Futuras: vigenciainicio > hoje
              params.vigenciainicio_maior_que = hoje
            } else if (value === 'expirado') {
              // Expiradas: vigenciafim < hoje
              params.vigenciafim_menor_que = hoje
            }
          } else {
            params[key] = value
          }
        }
      })
      return params
    },
  },

  actions: {
    /**
     * Inicializa a paginação para um tributo
     */
    initPaginationForTributo(codigoTributo) {
      if (!this.pagination[codigoTributo]) {
        this.pagination[codigoTributo] = {
          page: 1,
          perPage: 20,
          hasMore: true,
          loading: false,
        }
      }

      if (!this.regras[codigoTributo]) {
        this.regras[codigoTributo] = []
      }
    },

    /**
     * Busca os tributos disponíveis
     */
    async fetchTributos() {
      try {
        this.tributosLoading = true
        this.error = null

        const response = await api.get('v1/tributacao/tributo')
        this.tributos = response.data.data || response.data || []

        // Inicializa paginação para cada tributo
        this.tributos.forEach((tributo) => {
          this.initPaginationForTributo(tributo.codtributo)
        })

        // Define primeira aba como ativa se ainda não houver uma
        if (!this.activeTab && this.tributos.length > 0) {
          this.activeTab = this.tributos[0].codtributo
        }

        return this.tributos
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao carregar tributos'
        console.error('Erro ao buscar tributos:', error)
        throw error
      } finally {
        this.tributosLoading = false
      }
    },

    /**
     * Busca regras de tributação
     */
    async fetchRegras(reset = false) {
      const tributo = this.activeTab

      // Se não tem tributo ativo, retorna
      if (!tributo) {
        return
      }

      // Garante que a paginação existe
      this.initPaginationForTributo(tributo)

      const pag = this.pagination[tributo]

      // Se já está carregando ou não tem mais dados, retorna
      if (pag.loading || (!reset && !pag.hasMore)) {
        return
      }

      // Se é reset, limpa os dados
      if (reset) {
        this.regras[tributo] = []
        this.pagination[tributo].page = 1
        this.pagination[tributo].hasMore = true
      }

      try {
        this.pagination[tributo].loading = true
        this.error = null

        // Busca o tributo pelo codtributo (que é a chave primária)
        const tributoObj = this.tributos.find((t) => t.codtributo === tributo)
        if (!tributoObj) {
          throw new Error(`Tributo ${tributo} não encontrado`)
        }

        const params = {
          ...this.filtersAsParams,
          codtributo: tributoObj.codtributo,
          page: this.pagination[tributo].page,
          per_page: this.pagination[tributo].perPage,
        }

        const response = await api.get('v1/tributacao/regra', { params })

        const newRegras = response.data.data || []

        // Adiciona as novas regras
        this.regras[tributo] = [...this.regras[tributo], ...newRegras]

        // Atualiza controle de paginação
        const hasMore = newRegras.length === this.pagination[tributo].perPage
        this.pagination[tributo].hasMore = hasMore

        if (hasMore) {
          this.pagination[tributo].page++
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao carregar regras'
        console.error('Erro ao buscar regras:', error)
      } finally {
        this.pagination[tributo].loading = false
      }
    },

    /**
     * Carrega mais regras (scroll infinito)
     */
    async loadMore() {
      await this.fetchRegras(false)
    },

    /**
     * Altera a aba ativa
     */
    setActiveTab(tab) {
      // Verifica se o tributo existe
      const tributoExists = this.tributos.some((t) => t.codtributo === tab)
      if (!tributoExists) {
        console.warn(`Tributo ${tab} não encontrado`)
        return
      }

      this.activeTab = tab

      // Garante que a paginação existe
      this.initPaginationForTributo(tab)

      // Se ainda não carregou dados dessa aba, carrega
      if (this.regras[tab].length === 0) {
        this.fetchRegras(true)
      }
    },

    /**
     * Aplica filtros
     */
    async applyFilters() {
      // Reseta todas as abas quando aplica filtro
      Object.keys(this.regras).forEach((tributo) => {
        this.regras[tributo] = []
        this.pagination[tributo].page = 1
        this.pagination[tributo].hasMore = true
      })

      // Recarrega a aba atual
      await this.fetchRegras(true)
    },

    /**
     * Limpa filtros
     */
    async clearFilters() {
      this.filters = {
        codnaturezaoperacao: null,
        codtipoproduto: null,
        ncm: null,
        codcidadedestino: null,
        codestadodestino: null,
        tipocliente: null,
        basepercentual: null,
        aliquota: null,
        cst: null,
        cclasstrib: null,
        geracredito: null,
        beneficiocodigo: null,
        vigencia: null,
      }

      await this.applyFilters()
    },

    /**
     * Define um filtro específico
     */
    setFilter(key, value) {
      if (Object.prototype.hasOwnProperty.call(this.filters, key)) {
        this.filters[key] = value
      }
    },

    /**
     * Toggle das drawers
     */
    toggleLeftDrawer() {
      this.leftDrawerOpen = !this.leftDrawerOpen
    },

    toggleRightDrawer() {
      this.rightDrawerOpen = !this.rightDrawerOpen
    },

    /**
     * Adiciona nova regra
     */
    async createRegra(data) {
      try {
        this.isLoading = true
        const response = await api.post('v1/tributacao/regra', data)

        // Pega a regra do response (pode ser data.data ou só data)
        const regra = response.data.data || response.data

        // Adiciona a nova regra na lista correspondente
        const tributo = regra.tributo?.codtributo || data.codtributo || this.activeTab
        this.regras[tributo].unshift(regra)

        return regra
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao criar regra'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Atualiza regra existente
     */
    async updateRegra(codtributacaoregra, data) {
      try {
        this.isLoading = true
        const response = await api.put(`v1/tributacao/regra/${codtributacaoregra}`, data)

        // Pega a regra do response (pode ser data.data ou só data)
        const regra = response.data.data || response.data

        // Atualiza a regra em todas as abas
        Object.keys(this.regras).forEach((tributo) => {
          const index = this.regras[tributo].findIndex(
            (r) => r.codtributacaoregra === codtributacaoregra,
          )
          if (index !== -1) {
            this.regras[tributo][index] = regra
          }
        })

        return regra
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao atualizar regra'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Deleta regra
     */
    async deleteRegra(codtributacaoregra) {
      try {
        this.isLoading = true
        await api.delete(`v1/tributacao/regra/${codtributacaoregra}`)

        // Remove a regra de todas as abas
        Object.keys(this.regras).forEach((tributo) => {
          this.regras[tributo] = this.regras[tributo].filter(
            (r) => r.codtributacaoregra !== codtributacaoregra,
          )
        })
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao deletar regra'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    // ========== CRUD DE TRIBUTOS ==========

    /**
     * Cria novo tributo
     */
    async createTributo(data) {
      try {
        this.isLoading = true
        this.error = null

        const response = await api.post('v1/tributacao/tributo', data)
        const novoTributo = response.data.data || response.data

        // Adiciona o novo tributo na lista
        this.tributos.push(novoTributo)

        // Inicializa paginação para o novo tributo
        this.initPaginationForTributo(novoTributo.codtributo)

        // Define o novo tributo como aba ativa
        this.activeTab = novoTributo.codtributo

        return novoTributo
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao criar tributo'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Atualiza tributo existente
     */
    async updateTributo(codtributo, data) {
      try {
        this.isLoading = true
        this.error = null

        const response = await api.put(`v1/tributacao/tributo/${codtributo}`, data)
        const tributoAtualizado = response.data.data || response.data

        // Encontra o índice do tributo na lista
        const index = this.tributos.findIndex((t) => t.codtributo === codtributo)

        if (index !== -1) {
          const codtributoAntigo = this.tributos[index].codtributo
          const codtributoNovo = tributoAtualizado.codtributo

          // Atualiza o tributo na lista
          this.tributos[index] = tributoAtualizado

          // Se o codtributo mudou, precisa reorganizar os dados
          if (codtributoAntigo !== codtributoNovo) {
            // Move os dados da aba antiga pra nova
            this.regras[codtributoNovo] = this.regras[codtributoAntigo] || []
            this.pagination[codtributoNovo] = this.pagination[codtributoAntigo] || {
              page: 1,
              perPage: 20,
              hasMore: true,
              loading: false,
            }

            // Remove dados da aba antiga
            delete this.regras[codtributoAntigo]
            delete this.pagination[codtributoAntigo]

            // Se a aba ativa era a que mudou, atualiza
            if (this.activeTab === codtributoAntigo) {
              this.activeTab = codtributoNovo
            }
          }
        }

        return tributoAtualizado
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao atualizar tributo'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Deleta tributo
     */
    async deleteTributo(codtributo) {
      try {
        this.isLoading = true
        this.error = null

        // Encontra o tributo antes de deletar
        const tributo = this.tributos.find((t) => t.codtributo === codtributo)
        if (!tributo) {
          throw new Error('Tributo não encontrado')
        }

        await api.delete(`v1/tributacao/tributo/${codtributo}`)

        // Remove o tributo da lista
        this.tributos = this.tributos.filter((t) => t.codtributo !== codtributo)

        // Remove dados associados
        delete this.regras[tributo.codtributo]
        delete this.pagination[tributo.codtributo]

        // Se a aba deletada era a ativa, muda pra primeira disponível
        if (this.activeTab === tributo.codtributo) {
          if (this.tributos.length > 0) {
            this.activeTab = this.tributos[0].codtributo
            // Carrega dados da nova aba se necessário
            if (this.regras[this.activeTab].length === 0) {
              await this.fetchRegras(true)
            }
          } else {
            this.activeTab = null
          }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao deletar tributo'
        throw error
      } finally {
        this.isLoading = false
      }
    },
  },
})
