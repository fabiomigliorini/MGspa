import { defineStore } from 'pinia'
import notaFiscalService from '../services/notaFiscalService'
import notaFiscalItemService from '../services/notaFiscalItemService'
import notaFiscalPagamentoService from '../services/notaFiscalPagamentoService'
import notaFiscalDuplicataService from '../services/notaFiscalDuplicataService'
import notaFiscalReferenciadaService from '../services/notaFiscalReferenciadaService'
import notaFiscalCartaCorrecaoService from '../services/notaFiscalCartaCorrecaoService'

export const useNotaFiscalStore = defineStore('notaFiscal', {
  persist: {
    pick: ['filters'],
  },
  state: () => ({
    // Lista de notas fiscais
    notas: [],
    pagination: {
      page: 1,
      perPage: 20,
      hasMore: true,
      loading: false,
      total: 0,
    },
    filters: {
      // Filtros de busca
      numero: null,
      serie: null,
      nfechave: null,

      // Filtros de relacionamento
      codfilial: null,
      codpessoa: null,
      codgrupoeconomico: null,
      codnaturezaoperacao: null,
      codoperacao: null,

      // Filtros de tipo
      modelo: null,
      emitida: null,
      status: null,

      // Filtros de data
      emissao_inicio: null,
      emissao_fim: null,
      saida_inicio: null,
      saida_fim: null,

      // Filtros de valor
      valortotal_inicio: null,
      valortotal_fim: null,
    },
    // Flag para controlar se já foi carregado
    initialLoadDone: false,

    // Nota fiscal atual (já contém todos os dados relacionados)
    currentNota: null,

    // Item atual sendo editado (apenas para leitura da API)
    currentItem: null,

    // Item em edição (usado nos forms - mantém alterações enquanto navega entre abas)
    editingItem: null,

    // Loading states
    loading: {
      nota: false,
      itens: false,
      pagamentos: false,
      duplicatas: false,
      referenciadas: false,
      cartasCorrecao: false,
    },
  }),

  getters: {
    // Verifica se a nota está bloqueada para edição
    notaBloqueada: (state) => {
      if (!state.currentNota) return false
      return ['Autorizada', 'Cancelada', 'Inutilizada'].includes(state.currentNota.status)
    },

    // Acessa os itens da nota atual
    itens: (state) => state.currentNota?.itens || [],

    // Acessa os pagamentos da nota atual
    pagamentos: (state) => state.currentNota?.pagamentos || [],

    // Acessa as duplicatas da nota atual
    duplicatas: (state) => state.currentNota?.duplicatas || [],

    // Acessa as notas referenciadas da nota atual
    referenciadas: (state) => state.currentNota?.notasReferenciadas || [],

    // Acessa as cartas de correção da nota atual
    cartasCorrecao: (state) => state.currentNota?.cartasCorrecao || [],

    // Total de itens
    totalItens: (state) => state.currentNota?.itens?.length || 0,

    // Valor total dos itens
    valorTotalItens: (state) => {
      const itens = state.currentNota?.itens || []
      return itens.reduce((total, item) => {
        return total + (parseFloat(item.valortotal) || 0)
      }, 0)
    },

    // Valor total dos pagamentos
    valorTotalPagamentos: (state) => {
      const pagamentos = state.currentNota?.pagamentos || []
      return pagamentos.reduce((total, pag) => {
        return total + (parseFloat(pag.valorpagamento) || 0)
      }, 0)
    },

    // Valor total das duplicatas
    valorTotalDuplicatas: (state) => {
      const duplicatas = state.currentNota?.duplicatas || []
      return duplicatas.reduce((total, dup) => {
        return total + (parseFloat(dup.valor) || 0)
      }, 0)
    },

    // Verifica se há filtros ativos
    hasActiveFilters: (state) => {
      return Object.values(state.filters).some((value) => value !== null && value !== '')
    },

    // Conta quantos filtros estão ativos
    activeFiltersCount: (state) => {
      return Object.values(state.filters).filter((value) => value !== null && value !== '').length
    },
  },

  actions: {
    // ==================== NOTAS FISCAIS ====================

    async fetchNotas(reset = false) {
      // Se já está carregando ou não tem mais dados, retorna
      if (this.pagination.loading || (!reset && !this.pagination.hasMore)) {
        return
      }

      // Se é reset, limpa os dados
      if (reset) {
        this.notas = []
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

        const response = await notaFiscalService.list(params)

        const newNotas = response.data || []

        // Adiciona novas notas à lista existente
        if (reset) {
          this.notas = newNotas
        } else {
          this.notas.push(...newNotas)
        }

        // Atualiza paginação
        this.pagination.total = response.meta.total
        this.pagination.hasMore = response.meta.current_page < response.meta.last_page

        // Incrementa página para próxima busca
        if (this.pagination.hasMore) {
          this.pagination.page++
        }

        this.initialLoadDone = true

        return response
      } catch (error) {
        console.error('Erro ao buscar notas fiscais:', error)
        throw error
      } finally {
        this.pagination.loading = false
      }
    },

    async fetchNota(codnotafiscal) {
      // Converte para número para garantir comparação correta
      const codnotafiscalNum = parseInt(codnotafiscal)

      // Verifica se a nota já está carregada na store E é a mesma nota
      if (this.currentNota && this.currentNota.codnotafiscal === codnotafiscalNum) {
        // Nota já está carregada, retorna sem fazer nova requisição
        return this.currentNota
      }

      this.loading.nota = true
      try {
        const response = await notaFiscalService.get(codnotafiscalNum)
        // O backend já retorna todos os dados relacionados no objeto
        this.currentNota = response.data
        return response.data
      } catch (error) {
        console.error('Erro ao buscar nota fiscal:', error)
        throw error
      } finally {
        this.loading.nota = false
      }
    },

    async createNota(data) {
      this.loading.nota = true
      try {
        const response = await notaFiscalService.create(data)
        this.currentNota = response.data

        // Adiciona no início da lista
        this.notas.unshift(response.data)
        this.pagination.total++

        return response.data
      } catch (error) {
        console.error('Erro ao criar nota fiscal:', error)
        throw error
      } finally {
        this.loading.nota = false
      }
    },

    async updateNota(codnotafiscal, data) {
      this.loading.nota = true
      try {
        const response = await notaFiscalService.update(codnotafiscal, data)
        this.currentNota = response.data

        // Atualiza na lista se existir
        const index = this.notas.findIndex((n) => n.codnotafiscal === codnotafiscal)
        if (index !== -1) {
          this.notas[index] = response.data
        }

        return response.data
      } catch (error) {
        console.error('Erro ao atualizar nota fiscal:', error)
        throw error
      } finally {
        this.loading.nota = false
      }
    },

    async deleteNota(codnotafiscal) {
      this.loading.nota = true
      try {
        await notaFiscalService.delete(codnotafiscal)

        // Remove da lista
        this.notas = this.notas.filter((n) => n.codnotafiscal !== codnotafiscal)
        this.pagination.total--

        // Limpa a nota atual se for a mesma
        if (this.currentNota?.codnotafiscal === codnotafiscal) {
          this.clearCurrentNota()
        }
      } catch (error) {
        console.error('Erro ao excluir nota fiscal:', error)
        throw error
      } finally {
        this.loading.nota = false
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
      // Reset quando altera filtros
      this.initialLoadDone = false
    },

    clearFilters() {
      this.filters = {
        // Filtros de busca
        numero: null,
        serie: null,
        nfechave: null,

        // Filtros de relacionamento
        codfilial: null,
        codpessoa: null,
        codgrupoeconomico: null,
        codnaturezaoperacao: null,
        codoperacao: null,

        // Filtros de tipo
        modelo: null,
        emitida: null,
        status: null,

        // Filtros de data
        emissao_inicio: null,
        emissao_fim: null,
        saida_inicio: null,
        saida_fim: null,

        // Filtros de valor
        valortotal_inicio: null,
        valortotal_fim: null,
      }
      this.initialLoadDone = false
    },

    clearCurrentNota() {
      this.currentNota = null
    },

    // ==================== ITENS ====================

    async fetchItens(codnotafiscal) {
      this.loading.itens = true
      try {
        const response = await notaFiscalItemService.list(codnotafiscal)
        // Atualiza os itens no currentNota
        if (this.currentNota) {
          this.currentNota.itens = response.data
        }
        return response.data
      } catch (error) {
        console.error('Erro ao buscar itens:', error)
        throw error
      } finally {
        this.loading.itens = false
      }
    },

    async fetchItem(codnotafiscal, codnotafiscalprodutobarra) {
      this.loading.itens = true
      try {
        const response = await notaFiscalItemService.get(codnotafiscal, codnotafiscalprodutobarra)
        return response.data
      } catch (error) {
        console.error('Erro ao buscar item:', error)
        throw error
      } finally {
        this.loading.itens = false
      }
    },

    async createItem(codnotafiscal, data) {
      this.loading.itens = true
      try {
        const response = await notaFiscalItemService.create(codnotafiscal, data)
        // Adiciona o item no currentNota
        if (this.currentNota) {
          if (!this.currentNota.itens) {
            this.currentNota.itens = []
          }
          this.currentNota.itens.push(response.data)
        }
        return response.data
      } catch (error) {
        console.error('Erro ao criar item:', error)
        throw error
      } finally {
        this.loading.itens = false
      }
    },

    async updateItem(codnotafiscal, codnotafiscalprodutobarra, data) {
      this.loading.itens = true
      console.log(data)
      try {
        const response = await notaFiscalItemService.update(
          codnotafiscal,
          codnotafiscalprodutobarra,
          data
        )

        // Atualiza no currentNota
        if (this.currentNota?.itens) {
          const index = this.currentNota.itens.findIndex(
            (i) => i.codnotafiscalprodutobarra === codnotafiscalprodutobarra
          )
          if (index !== -1) {
            this.currentNota.itens[index] = response.data
          }
        }

        return response.data
      } catch (error) {
        console.error('Erro ao atualizar item:', error)
        throw error
      } finally {
        this.loading.itens = false
      }
    },

    async deleteItem(codnotafiscal, codnotafiscalprodutobarra) {
      this.loading.itens = true
      try {
        await notaFiscalItemService.delete(codnotafiscal, codnotafiscalprodutobarra)

        // Remove do currentNota
        if (this.currentNota?.itens) {
          this.currentNota.itens = this.currentNota.itens.filter(
            (i) => i.codnotafiscalprodutobarra !== codnotafiscalprodutobarra
          )
        }
      } catch (error) {
        console.error('Erro ao excluir item:', error)
        throw error
      } finally {
        this.loading.itens = false
      }
    },

    // Métodos específicos para atualização parcial de itens
    async fetchItemById(codnotafiscalprodutobarra) {
      this.loading.itens = true
      try {
        // Busca o item no array currentNota.itens
        const item = this.currentNota?.itens?.find(
          (i) => i.codnotafiscalprodutobarra === parseInt(codnotafiscalprodutobarra)
        )
        if (item) {
          this.currentItem = item
          return item
        }
        throw new Error('Item não encontrado')
      } catch (error) {
        console.error('Erro ao buscar item:', error)
        throw error
      } finally {
        this.loading.itens = false
      }
    },

    /**
     * Inicia a edição de um item (cria uma cópia para edição)
     */
    startEditingItem(codnotafiscalprodutobarra) {
      const item = this.currentNota?.itens?.find(
        (i) => i.codnotafiscalprodutobarra === parseInt(codnotafiscalprodutobarra)
      )
      if (item) {
        // Cria uma cópia profunda do item para edição
        this.editingItem = JSON.parse(JSON.stringify(item))
        return this.editingItem
      }
      return null
    },

    // ==================== PAGAMENTOS ====================
    async fetchPagamentos(codnotafiscal) {
      this.loading.pagamentos = true
      try {
        const response = await notaFiscalPagamentoService.list(codnotafiscal)
        this.pagamentos = response.data
        return response.data
      } catch (error) {
        console.error('Erro ao buscar pagamentos:', error)
        throw error
      } finally {
        this.loading.pagamentos = false
      }
    },

    async fetchPagamento(codnotafiscal, codnotafiscalpagamento) {
      this.loading.pagamentos = true
      try {
        const response = await notaFiscalPagamentoService.get(codnotafiscal, codnotafiscalpagamento)
        return response.data
      } catch (error) {
        console.error('Erro ao buscar pagamento:', error)
        throw error
      } finally {
        this.loading.pagamentos = false
      }
    },

    async createPagamento(codnotafiscal, data) {
      this.loading.pagamentos = true
      try {
        const response = await notaFiscalPagamentoService.create(codnotafiscal, data)
        // Adiciona na lista do currentNota
        if (this.currentNota) {
          if (!this.currentNota.pagamentos) {
            this.currentNota.pagamentos = []
          }
          this.currentNota.pagamentos.push(response.data)
        }
        return response.data
      } catch (error) {
        console.error('Erro ao criar pagamento:', error)
        throw error
      } finally {
        this.loading.pagamentos = false
      }
    },

    async updatePagamento(codnotafiscal, codnotafiscalpagamento, data) {
      this.loading.pagamentos = true
      try {
        const response = await notaFiscalPagamentoService.update(
          codnotafiscal,
          codnotafiscalpagamento,
          data
        )

        // Atualiza na lista do currentNota
        if (this.currentNota && this.currentNota.pagamentos) {
          const index = this.currentNota.pagamentos.findIndex(
            (p) => p.codnotafiscalpagamento === codnotafiscalpagamento
          )
          if (index !== -1) {
            this.currentNota.pagamentos[index] = response.data
          }
        }

        return response.data
      } catch (error) {
        console.error('Erro ao atualizar pagamento:', error)
        throw error
      } finally {
        this.loading.pagamentos = false
      }
    },

    async deletePagamento(codnotafiscal, codnotafiscalpagamento) {
      this.loading.pagamentos = true
      try {
        await notaFiscalPagamentoService.delete(codnotafiscal, codnotafiscalpagamento)

        // Remove da lista do currentNota
        if (this.currentNota && this.currentNota.pagamentos) {
          this.currentNota.pagamentos = this.currentNota.pagamentos.filter(
            (p) => p.codnotafiscalpagamento !== codnotafiscalpagamento
          )
        }
      } catch (error) {
        console.error('Erro ao excluir pagamento:', error)
        throw error
      } finally {
        this.loading.pagamentos = false
      }
    },

    // ==================== DUPLICATAS ====================

    async fetchDuplicatas(codnotafiscal) {
      this.loading.duplicatas = true
      try {
        const response = await notaFiscalDuplicataService.list(codnotafiscal)
        this.duplicatas = response.data
        return response.data
      } catch (error) {
        console.error('Erro ao buscar duplicatas:', error)
        throw error
      } finally {
        this.loading.duplicatas = false
      }
    },

    async createDuplicata(codnotafiscal, data) {
      this.loading.duplicatas = true
      try {
        const response = await notaFiscalDuplicataService.create(codnotafiscal, data)
        // Adiciona na lista do currentNota
        if (this.currentNota) {
          if (!this.currentNota.duplicatas) {
            this.currentNota.duplicatas = []
          }
          this.currentNota.duplicatas.push(response.data)
        }
        return response.data
      } catch (error) {
        console.error('Erro ao criar duplicata:', error)
        throw error
      } finally {
        this.loading.duplicatas = false
      }
    },

    async updateDuplicata(codnotafiscal, codnotafiscalduplicatas, data) {
      this.loading.duplicatas = true
      try {
        const response = await notaFiscalDuplicataService.update(
          codnotafiscal,
          codnotafiscalduplicatas,
          data
        )

        // Atualiza na lista do currentNota
        if (this.currentNota && this.currentNota.duplicatas) {
          const index = this.currentNota.duplicatas.findIndex(
            (d) => d.codnotafiscalduplicatas === codnotafiscalduplicatas
          )
          if (index !== -1) {
            this.currentNota.duplicatas[index] = response.data
          }
        }

        return response.data
      } catch (error) {
        console.error('Erro ao atualizar duplicata:', error)
        throw error
      } finally {
        this.loading.duplicatas = false
      }
    },

    async deleteDuplicata(codnotafiscal, codnotafiscalduplicatas) {
      this.loading.duplicatas = true
      try {
        await notaFiscalDuplicataService.delete(codnotafiscal, codnotafiscalduplicatas)

        // Remove da lista do currentNota
        if (this.currentNota && this.currentNota.duplicatas) {
          this.currentNota.duplicatas = this.currentNota.duplicatas.filter(
            (d) => d.codnotafiscalduplicatas !== codnotafiscalduplicatas
          )
        }
      } catch (error) {
        console.error('Erro ao excluir duplicata:', error)
        throw error
      } finally {
        this.loading.duplicatas = false
      }
    },

    // ==================== NOTAS REFERENCIADAS ====================

    async fetchReferenciadas(codnotafiscal) {
      this.loading.referenciadas = true
      try {
        const response = await notaFiscalReferenciadaService.list(codnotafiscal)
        this.referenciadas = response.data
        return response.data
      } catch (error) {
        console.error('Erro ao buscar notas referenciadas:', error)
        throw error
      } finally {
        this.loading.referenciadas = false
      }
    },

    async createReferenciada(codnotafiscal, data) {
      this.loading.referenciadas = true
      try {
        const response = await notaFiscalReferenciadaService.create(codnotafiscal, data)
        // Adiciona na lista do currentNota
        if (this.currentNota) {
          if (!this.currentNota.notasReferenciadas) {
            this.currentNota.notasReferenciadas = []
          }
          this.currentNota.notasReferenciadas.push(response.data)
        }
        return response.data
      } catch (error) {
        console.error('Erro ao criar nota referenciada:', error)
        throw error
      } finally {
        this.loading.referenciadas = false
      }
    },

    async deleteReferenciada(codnotafiscal, codnotafiscalreferenciada) {
      this.loading.referenciadas = true
      try {
        await notaFiscalReferenciadaService.delete(codnotafiscal, codnotafiscalreferenciada)

        // Remove da lista do currentNota
        if (this.currentNota && this.currentNota.notasReferenciadas) {
          this.currentNota.notasReferenciadas = this.currentNota.notasReferenciadas.filter(
            (r) => r.codnotafiscalreferenciada !== codnotafiscalreferenciada
          )
        }
      } catch (error) {
        console.error('Erro ao excluir nota referenciada:', error)
        throw error
      } finally {
        this.loading.referenciadas = false
      }
    },

    // ==================== CARTAS DE CORREÇÃO ====================

    async fetchCartasCorrecao(codnotafiscal) {
      this.loading.cartasCorrecao = true
      try {
        const response = await notaFiscalCartaCorrecaoService.list(codnotafiscal)
        this.cartasCorrecao = response.data
        return response.data
      } catch (error) {
        console.error('Erro ao buscar cartas de correção:', error)
        throw error
      } finally {
        this.loading.cartasCorrecao = false
      }
    },

    async createCartaCorrecao(codnotafiscal, data) {
      this.loading.cartasCorrecao = true
      try {
        const response = await notaFiscalCartaCorrecaoService.create(codnotafiscal, data)
        // Adiciona na lista do currentNota
        if (this.currentNota) {
          if (!this.currentNota.cartasCorrecao) {
            this.currentNota.cartasCorrecao = []
          }
          this.currentNota.cartasCorrecao.push(response.data)
        }
        return response.data
      } catch (error) {
        console.error('Erro ao criar carta de correção:', error)
        throw error
      } finally {
        this.loading.cartasCorrecao = false
      }
    },

    async updateCartaCorrecao(codnotafiscal, codnotafiscalcartacorrecao, data) {
      this.loading.cartasCorrecao = true
      try {
        const response = await notaFiscalCartaCorrecaoService.update(
          codnotafiscal,
          codnotafiscalcartacorrecao,
          data
        )

        // Atualiza na lista do currentNota
        if (this.currentNota && this.currentNota.cartasCorrecao) {
          const index = this.currentNota.cartasCorrecao.findIndex(
            (c) => c.codnotafiscalcartacorrecao === codnotafiscalcartacorrecao
          )
          if (index !== -1) {
            this.currentNota.cartasCorrecao[index] = response.data
          }
        }

        return response.data
      } catch (error) {
        console.error('Erro ao atualizar carta de correção:', error)
        throw error
      } finally {
        this.loading.cartasCorrecao = false
      }
    },

    async deleteCartaCorrecao(codnotafiscal, codnotafiscalcartacorrecao) {
      this.loading.cartasCorrecao = true
      try {
        await notaFiscalCartaCorrecaoService.delete(codnotafiscal, codnotafiscalcartacorrecao)

        // Remove da lista do currentNota
        if (this.currentNota && this.currentNota.cartasCorrecao) {
          this.currentNota.cartasCorrecao = this.currentNota.cartasCorrecao.filter(
            (c) => c.codnotafiscalcartacorrecao !== codnotafiscalcartacorrecao
          )
        }
      } catch (error) {
        console.error('Erro ao excluir carta de correção:', error)
        throw error
      } finally {
        this.loading.cartasCorrecao = false
      }
    },
  },
})
