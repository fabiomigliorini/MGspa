import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  codproduto: null,
  barras: null,
  produto: null,
  referencia: null,
  preco_de: null,
  preco_ate: null,
  codsecaoproduto: null,
  codfamiliaproduto: null,
  codgrupoproduto: null,
  codsubgrupoproduto: null,
  codmarca: null,
  codncm: null,
  site: null,
  inativo: 1, // 1 = ativos, 2 = inativos, null = todos
})

export const useProdutoStore = defineStore(
  'produto',
  () => {
    const filters = ref(defaultFilters())
    const items = ref([])
    const loading = ref(false)
    const page = ref(1)
    const hasMore = ref(true)
    const total = ref(0)

    const activeFiltersCount = computed(() => {
      const f = filters.value
      let count = 0
      for (const [k, v] of Object.entries(f)) {
        if (k === 'inativo') {
          if (v !== 1) count++
        } else if (v !== null && v !== '') {
          count++
        }
      }
      return count
    })

    function montarParams() {
      const params = {}
      for (const [k, v] of Object.entries(filters.value)) {
        if (v !== null && v !== '') params[k] = v
      }
      return params
    }

    async function fetchItems(reset = false) {
      if (reset) {
        page.value = 1
        hasMore.value = true
      }
      if (!hasMore.value || loading.value) return

      loading.value = true
      try {
        const params = { ...montarParams(), page: page.value }
        const { data } = await api.get('v1/produto', { params })
        const rows = data.data || []
        items.value = reset ? rows : [...items.value, ...rows]
        total.value = data.meta?.total ?? data.total ?? items.value.length
        const lastPage = data.meta?.last_page ?? data.last_page ?? (rows.length ? page.value + 1 : page.value)
        hasMore.value = page.value < lastPage
        page.value++
      } finally {
        loading.value = false
      }
    }

    function clearFilters() {
      filters.value = defaultFilters()
    }

    function removeLocal(codproduto) {
      items.value = items.value.filter((i) => i.codproduto !== codproduto)
    }

    function upsertLocal(item) {
      const idx = items.value.findIndex((i) => i.codproduto === item.codproduto)
      if (idx >= 0) items.value.splice(idx, 1, item)
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
      removeLocal,
      upsertLocal,
    }
  },
  {
    persist: { pick: ['filters'] },
  },
)
