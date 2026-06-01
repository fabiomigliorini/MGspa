import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  codcheque: null,
  codbanco: null,
  agencia: null,
  numero: null,
  codpessoa: null,
  emitente: null,
  valor_de: null,
  valor_ate: null,
  indstatus: null,
  vencimento_de: null,
  vencimento_ate: null,
})

export const useChequeStore = defineStore(
  'cheque',
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
      Object.keys(defaultFilters()).forEach((k) => {
        if (f[k] !== null && f[k] !== '') count++
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
        const { data } = await api.get('v1/cheque', { params })
        const meta = data.meta ?? data
        const rows = data.data || []
        items.value = reset ? rows : [...items.value, ...rows]
        total.value = meta.total ?? items.value.length
        const lastPage = meta.last_page ?? (rows.length ? page.value + 1 : page.value)
        hasMore.value = page.value < lastPage
        page.value++
      } finally {
        loading.value = false
      }
    }

    function clearFilters() {
      filters.value = defaultFilters()
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
    }
  },
  {
    persist: { pick: ['filters'] },
  },
)
