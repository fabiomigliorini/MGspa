import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  codchequerepasse: null,
  codportador: null,
  data_de: null,
  data_ate: null,
  inativo: false,
})

export const useChequeRepasseStore = defineStore(
  'chequeRepasse',
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
      if (f.codchequerepasse) count++
      if (f.codportador) count++
      if (f.data_de) count++
      if (f.data_ate) count++
      if (f.inativo !== false) count++
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
        const { data } = await api.get('v1/cheque-repasse', { params })
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

    function upsertLocal(item) {
      const idx = items.value.findIndex((i) => i.codchequerepasse === item.codchequerepasse)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
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
    }
  },
  {
    persist: { pick: ['filters'] },
  },
)
