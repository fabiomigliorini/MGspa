import { defineStore } from 'pinia'
import { api } from 'src/boot/axios'

export const useSelectPessoaStore = defineStore('selectPessoa', {
  state: () => ({
    // Cache de pessoa por busca
    pessoaCache: {},
  }),

  getters: {},

  actions: {
    /**
     * Busca pessoa por texto
     */
    async search(busca, options = {}) {
      if (!busca || busca.length < 2) {
        return []
      }

      const { somenteAtivos = true, somenteVendedores = false } = options

      // Cria chave de cache
      const cacheKey = `${busca.toLowerCase()}_${somenteAtivos}_${somenteVendedores}`
      if (this.pessoaCache[cacheKey]) {
        return this.pessoaCache[cacheKey]
      }

      try {
        const response = await api.get('v1/select/pessoa', {
          params: {
            pessoa: busca,
            somenteAtivos: somenteAtivos ? 1 : 0,
            somenteVendedores: somenteVendedores ? 1 : 0,
          },
        })

        const pessoas = response.data.map((item) => ({
          label: item.fantasia || item.pessoa,
          sublabel: item.pessoa !== item.fantasia ? item.pessoa : null,
          value: item.codpessoa,
          cnpj: item.cnpj,
          ie: item.ie,
          fisica: item.fisica,
          cidade: item.cidade,
          uf: item.sigla,
          inativo: item.inativo,
          codgrupoeconomico: item.codgrupoeconomico,
          grupoeconomico: item.grupoeconomico,
        }))

        // Salva no cache
        this.pessoaCache[cacheKey] = pessoas
        return pessoas
      } catch (error) {
        console.error('Erro ao buscar pessoa:', error)
        throw error
      }
    },

    /**
     * Busca uma pessoa específica por código
     */
    async fetch(codpessoa) {
      try {
        const response = await api.get('v1/select/pessoa', {
          params: { codpessoa },
        })
        if (response.data.length > 0) {
          const item = response.data[0]
          return {
            label: item.fantasia || item.pessoa,
            sublabel: item.pessoa !== item.fantasia ? item.pessoa : null,
            value: item.codpessoa,
            cnpj: item.cnpj,
            ie: item.ie,
            fisica: item.fisica,
            cidade: item.cidade,
            uf: item.sigla,
            inativo: item.inativo,
            codgrupoeconomico: item.codgrupoeconomico,
            grupoeconomico: item.grupoeconomico,
          }
        }
        return null
      } catch (error) {
        console.error('Erro ao buscar pessoa:', error)
        throw error
      }
    },

    /**
     * Limpa o cache de pessoa
     */
    clear() {
      this.pessoaCache = {}
    },
  },
})
