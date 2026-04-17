import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { useSelectCacheStore } from 'src/stores/selectCacheStore'

const defaultFilters = () => ({
  codportador: null,
  portador: null,
  codbanco: null,
  codfilial: null,
  emiteboleto: null,
  inativo: false,
})

export const usePortadorStore = defineStore(
  'portador',
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
      if (f.codportador) count++
      if (f.portador) count++
      if (f.codbanco) count++
      if (f.codfilial) count++
      if (f.emiteboleto !== null) count++
      if (f.inativo !== false) count++
      return count
    })

    async function fetchItems(reset = false) {
      if (reset) {
        page.value = 1
        items.value = []
        hasMore.value = true
      }
      if (!hasMore.value || loading.value) return

      loading.value = true
      try {
        const params = { ...filters.value, paginar: 1, page: page.value }
        const { data } = await api.get('v1/portador', { params })
        const rows = data.data || []
        items.value.push(...rows)
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
      const idx = items.value.findIndex((i) => i.codportador === item.codportador)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
      useSelectCacheStore().invalidate('portador')
    }

    function removeLocal(codportador) {
      items.value = items.value.filter((i) => i.codportador !== codportador)
      useSelectCacheStore().invalidate('portador')
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
    persist: { paths: ['filters'] },
  },
)
