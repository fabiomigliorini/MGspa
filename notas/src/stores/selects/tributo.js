import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useSelectTributoStore = defineStore('selectTributo', {
  state: () => ({
    // Cache de tributos por busca
    tributoCache: {},
    // Cache acumulativo de tributos já carregados por código
    tributosById: {},
  }),

  getters: {},

  actions: {
    /**
     * Busca tributo por texto
     */
    async search(busca) {
      if (!busca || busca.length < 1) {
        return []
      }

      // Verifica cache
      const cacheKey = busca.toLowerCase()
      if (this.tributoCache[cacheKey]) {
        return this.tributoCache[cacheKey]
      }

      try {
        const response = await api.get('v1/select/tributo', {
          params: { busca },
        })
        const tributos = response.data.map((item) => ({
          label: `${item.sigla} - ${item.nome}`,
          value: item.codtributo,
          sigla: item.sigla,
          nome: item.nome,
          descricao: item.descricao,
          ente: item.ente,
        }))

        // Salva no cache de busca
        this.tributoCache[cacheKey] = tributos

        // Adiciona cada tributo ao cache por ID
        tributos.forEach((tributo) => {
          this.tributosById[tributo.value] = tributo
        })

        return tributos
      } catch (error) {
        console.error('Erro ao buscar tributo:', error)
        throw error
      }
    },

    /**
     * Busca um tributo específico por código
     */
    async fetch(codtributo) {
      // Verifica se já está no cache
      if (this.tributosById[codtributo]) {
        console.log('📦 Usando cache - Tributo:', codtributo)
        return this.tributosById[codtributo]
      }

      console.log('🌐 Buscando da API - Tributo:', codtributo)
      try {
        const response = await api.get('v1/select/tributo', {
          params: { codtributo },
        })
        if (response.data.length > 0) {
          const item = response.data[0]
          const tributo = {
            label: `${item.sigla} - ${item.nome}`,
            value: item.codtributo,
            sigla: item.sigla,
            nome: item.nome,
            descricao: item.descricao,
            ente: item.ente,
          }

          // Adiciona ao cache
          this.tributosById[codtributo] = tributo
          return tributo
        }
        return null
      } catch (error) {
        console.error('Erro ao buscar tributo:', error)
        throw error
      }
    },

    /**
     * Limpa o cache de tributos
     */
    clear() {
      this.tributoCache = {}
      this.tributosById = {}
    },
  },
})
