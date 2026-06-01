import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  codmarca: null,
  marca: null,
  inativo: 1, // 1 = ativos, 2 = inativos, null = todos
  sort: 'marca', // 'marca' = alfabética | 'abcposicao' = curva ABC
  sobrando: false,
  faltando: false,
  abccategoria: { min: 0, max: 3 },
})

export const useMarcaStore = defineStore(
  'marca',
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
      if (f.codmarca) count++
      if (f.marca) count++
      if (f.inativo !== 1) count++
      if (f.sobrando) count++
      if (f.faltando) count++
      if (f.abccategoria.min !== 0 || f.abccategoria.max !== 3) count++
      return count
    })

    function montarParams() {
      const f = filters.value
      const params = {
        sort: f.sort,
        sobrando: f.sobrando,
        faltando: f.faltando,
        abccategoria: { min: f.abccategoria.min, max: f.abccategoria.max },
      }
      if (f.codmarca) params.codmarca = f.codmarca
      if (f.marca) params.marca = f.marca
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
        const { data } = await api.get('v1/marca', { params })
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
      const idx = items.value.findIndex((i) => i.codmarca === item.codmarca)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
    }

    function removeLocal(codmarca) {
      items.value = items.value.filter((i) => i.codmarca !== codmarca)
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
