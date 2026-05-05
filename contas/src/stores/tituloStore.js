import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  codtitulo: null,
  numero: null,
  nossonumero: null,
  codfilial: null,
  codpessoa: null,
  codgrupoeconomico: null,
  codtipotitulo: null,
  codcontacontabil: null,
  codportador: null,
  codgrupocliente: null,
  codusuariocriacao: null,
  vencimento_de: null,
  vencimento_ate: null,
  emissao_de: null,
  emissao_ate: null,
  criacao_de: null,
  criacao_ate: null,
  liquidacao_de: null,
  liquidacao_ate: null,
  debito_de: null,
  debito_ate: null,
  credito_de: null,
  credito_ate: null,
  saldo_de: null,
  saldo_ate: null,
  status: 'A',
  credito: null,
  gerencial: null,
  boleto: null,
  pagarreceber: null,
  ordem: 'AV',
})

export const useTituloStore = defineStore(
  'titulo',
  () => {
    const filters = ref(defaultFilters())
    const items = ref([])
    const loading = ref(false)
    const page = ref(1)
    const hasMore = ref(true)
    const total = ref(0)

    const activeFiltersCount = computed(() => {
      const f = filters.value
      const def = defaultFilters()
      let count = 0
      Object.keys(def).forEach((k) => {
        const v = f[k]
        if (v === null || v === undefined || v === '') return
        if (Array.isArray(v) && v.length === 0) return
        if (v !== def[k]) count++
      })
      return count
    })

    async function fetchItems(reset = false) {
      if (reset) {
        page.value = 1
        hasMore.value = true
      }
      if (!hasMore.value || loading.value) return

      loading.value = true
      try {
        const params = { ...filters.value, page: page.value }
        const { data } = await api.get('v1/titulo', { params })
        const rows = data.data || []
        items.value = reset ? rows : [...items.value, ...rows]
        total.value = data.meta?.total ?? items.value.length
        const lastPage = data.meta?.last_page ?? (rows.length ? page.value + 1 : page.value)
        hasMore.value = page.value < lastPage
        page.value++
      } finally {
        loading.value = false
      }
    }

    function clearFilters() {
      filters.value = defaultFilters()
    }

    function upsertLocal(item) {
      const idx = items.value.findIndex((i) => i.codtitulo === item.codtitulo)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
    }

    function removeLocal(codtitulo) {
      items.value = items.value.filter((i) => i.codtitulo !== codtitulo)
    }

    return {
      filters,
      items,
      loading,
      page,
      hasMore,
      total,
      activeFiltersCount,
      fetchItems,
      clearFilters,
      upsertLocal,
      removeLocal,
    }
  },
  {
    persist: { pick: ['filters'] },
  },
)
