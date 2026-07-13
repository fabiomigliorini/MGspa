import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  codigo: null,
  ente: null,
  inativo: false,
})

export const useUnidadeReferenciaStore = defineStore(
  'unidadeReferencia',
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
      if (f.codigo) count++
      if (f.ente) count++
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
        const { data } = await api.get('v1/unidade-referencia', { params })
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

    // PK = codunidadereferencia.
    function upsertLocal(item) {
      const idx = items.value.findIndex((i) => i.codunidadereferencia === item.codunidadereferencia)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
    }

    function removeLocal(codunidadereferencia) {
      items.value = items.value.filter((i) => i.codunidadereferencia !== codunidadereferencia)
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
