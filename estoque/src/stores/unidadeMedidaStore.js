import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  codunidademedida: null,
  unidademedida: null,
  sigla: null,
  inativo: 1, // 1 = ativos, 2 = inativos, null = todos
})

export const useUnidadeMedidaStore = defineStore(
  'unidadeMedida',
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
      if (f.codunidademedida) count++
      if (f.unidademedida) count++
      if (f.sigla) count++
      if (f.inativo !== 1) count++
      return count
    })

    function montarParams() {
      const f = filters.value
      const params = {}
      if (f.codunidademedida) params.codunidademedida = f.codunidademedida
      if (f.unidademedida) params.unidademedida = f.unidademedida
      if (f.sigla) params.sigla = f.sigla
      if (f.inativo) params.inativo = f.inativo
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
        const { data } = await api.get('v1/unidade-medida', { params })
        const rows = data.data || []
        items.value = reset ? rows : [...items.value, ...rows]
        total.value = data.total ?? items.value.length
        const lastPage = data.last_page ?? (rows.length ? page.value + 1 : page.value)
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
      const idx = items.value.findIndex((i) => i.codunidademedida === item.codunidademedida)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
    }

    function removeLocal(codunidademedida) {
      items.value = items.value.filter((i) => i.codunidademedida !== codunidademedida)
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
