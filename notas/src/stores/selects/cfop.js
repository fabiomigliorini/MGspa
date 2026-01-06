import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const useSelectCfopStore = defineStore('selectCfop', {
  state: () => ({
    // Cache de CFOPs por busca
    cfopCache: {},
    // Cache acumulativo de CFOPs já carregados por código
    cfopsById: {},
  }),

  getters: {},

  actions: {
    /**
     * Busca CFOP por texto
     */
    async search(busca) {
      if (!busca || busca.length < 1) {
        return []
      }

      // Verifica cache
      const cacheKey = busca.toLowerCase()
      if (this.cfopCache[cacheKey]) {
        return this.cfopCache[cacheKey]
      }

      try {
        const response = await api.get('v1/select/cfop', {
          params: { busca },
        })
        const cfops = response.data.map((item) => ({
          label: `${item.codigo} - ${item.nome}`,
          value: item.codcfop,
          codigo: item.codigo,
          nome: item.nome,
          descricao: item.descricao,
        }))

        // Salva no cache de busca
        this.cfopCache[cacheKey] = cfops

        // Adiciona cada CFOP ao cache por ID
        cfops.forEach((cfop) => {
          this.cfopsById[cfop.value] = cfop
        })

        return cfops
      } catch (error) {
        console.error('Erro ao buscar CFOP:', error)
        throw error
      }
    },

    /**
     * Busca um CFOP específico por código
     */
    async fetch(codcfop) {
      // Verifica se já está no cache
      if (this.cfopsById[codcfop]) {
        console.log('📦 Usando cache - CFOP:', codcfop)
        return this.cfopsById[codcfop]
      }

      console.log('🌐 Buscando da API - CFOP:', codcfop)
      try {
        const response = await api.get('v1/select/cfop', {
          params: { codcfop },
        })
        if (response.data.length > 0) {
          const item = response.data[0]
          const cfop = {
            label: `${item.codigo} - ${item.nome}`,
            value: item.codcfop,
            codigo: item.codigo,
            nome: item.nome,
            descricao: item.descricao,
          }

          // Adiciona ao cache
          this.cfopsById[codcfop] = cfop
          return cfop
        }
        return null
      } catch (error) {
        console.error('Erro ao buscar CFOP:', error)
        throw error
      }
    },

    /**
     * Limpa o cache de CFOP
     */
    clear() {
      this.cfopCache = {}
      this.cfopsById = {}
    },
  },
})
