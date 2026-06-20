import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  moeda: null,
  descricao: null,
  inativo: false,
})

export const useMoedaStore = defineStore(
  'moeda',
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
      if (f.moeda) count++
      if (f.descricao) count++
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
        const { data } = await api.get('v1/moeda', { params })
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

    function setFilters(novos) {
      Object.assign(filters.value, novos)
    }

    // PK = código da moeda (string ISO).
    function upsertLocal(item) {
      const idx = items.value.findIndex((i) => i.moeda === item.moeda)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
    }

    function removeLocal(moeda) {
      items.value = items.value.filter((i) => i.moeda !== moeda)
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
      setFilters,
      upsertLocal,
      removeLocal,
    }
  },
  {
    persist: { pick: ['filters'] },
  },
)
