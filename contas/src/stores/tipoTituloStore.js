import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const FLAGS = ['pagar', 'receber', 'debito', 'credito']

const defaultFilters = () => ({
  codtipotitulo: null,
  tipotitulo: null,
  codtipomovimentotitulo: null,
  pagar: null,
  receber: null,
  debito: null,
  credito: null,
  inativo: false,
})

export const useTipoTituloStore = defineStore(
  'tipoTitulo',
  () => {
    const filters = ref(defaultFilters())
    const items = ref([])
    const loading = ref(false)
    const page = ref(1)
    const hasMore = ref(true)
    const total = ref(0)
    const tiposMovimento = ref([])

    const activeFiltersCount = computed(() => {
      const f = filters.value
      let count = 0
      if (f.codtipotitulo) count++
      if (f.tipotitulo) count++
      if (f.codtipomovimentotitulo) count++
      for (const flag of FLAGS) if (f[flag] !== null) count++
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
        const params = { ...filters.value, page: page.value }
        const { data } = await api.get('v1/tipo-titulo', { params })
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

    async function fetchTiposMovimento() {
      if (tiposMovimento.value.length) return
      const { data } = await api.get('v1/tipo-movimento-titulo', { params: { todos: 1 } })
      tiposMovimento.value = data.data || []
    }

    function clearFilters() {
      filters.value = defaultFilters()
    }

    function setFilters(novos) {
      Object.assign(filters.value, novos)
    }

    function upsertLocal(item) {
      const idx = items.value.findIndex((i) => i.codtipotitulo === item.codtipotitulo)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
    }

    function removeLocal(codtipotitulo) {
      items.value = items.value.filter((i) => i.codtipotitulo !== codtipotitulo)
    }

    return {
      filters,
      items,
      loading,
      page,
      hasMore,
      total,
      tiposMovimento,
      activeFiltersCount,
      fetchItems,
      fetchTiposMovimento,
      clearFilters,
      setFilters,
      upsertLocal,
      removeLocal,
    }
  },
  {
    persist: { paths: ['filters'] },
  },
)
