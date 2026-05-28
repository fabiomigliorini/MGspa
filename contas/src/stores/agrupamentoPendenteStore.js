import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  codgrupocliente: null,
  codportador: [],
  codgrupoeconomico: null,
  codpessoa: null,
  codtipotitulo: null,
  codformapagamento: null,
  vencimento_de: null,
  vencimento_ate: null,
  valor_de: null,
  valor_ate: null,
})

export const useAgrupamentoPendenteStore = defineStore(
  'agrupamentoPendente',
  () => {
    const filters = ref(defaultFilters())
    const items = ref([])
    const loading = ref(false)

    const activeFiltersCount = computed(() => {
      const f = filters.value
      const def = defaultFilters()
      let count = 0
      Object.keys(def).forEach((k) => {
        const v = f[k]
        if (v === null || v === undefined || v === '') return
        if (Array.isArray(v) && v.length === 0) return
        if (Array.isArray(v) && Array.isArray(def[k]) && v.length === def[k].length) return
        if (v !== def[k]) count++
      })
      return count
    })

    async function fetchItems() {
      loading.value = true
      try {
        const { data } = await api.get('v1/titulo-agrupamento/pendentes', {
          params: filters.value,
        })
        items.value = data.data || []
      } finally {
        loading.value = false
      }
    }

    function clearFilters() {
      filters.value = defaultFilters()
    }

    function aplicarPadrao() {
      // "Trazer Tudo Menos Escolas, Colaboradores e Perdas" — replica selectFechamentoPadrao do legado
      // Sem GrupoCliente=8 (Colaborador) e GrupoCliente=1 (Escola Pública)
      // Sem Portador=202035 (Perda por prazo)
      // Como não temos a lista completa aqui, deixa o drawer popular via selects
      filters.value = defaultFilters()
    }

    return {
      filters,
      items,
      loading,
      activeFiltersCount,
      fetchItems,
      clearFilters,
      aplicarPadrao,
    }
  },
  { persist: { pick: ['filters'] } },
)
