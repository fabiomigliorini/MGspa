import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  nome: null,
  cpf: null,
  sort: 'horario',
  negocio: 'todos',
  horarioinicial: null,
  horariofinal: null,
  valorinicial: null,
  valorfinal: null,
})

export const usePixStore = defineStore(
  'pix',
  () => {
    const filters = ref(defaultFilters())
    const items = ref([])
    const loading = ref(false)
    const page = ref(1)
    const hasMore = ref(true)

    const activeFiltersCount = computed(() => {
      const f = filters.value
      let count = 0
      if (f.nome) count++
      if (f.cpf) count++
      if (f.sort !== 'horario') count++
      if (f.negocio !== 'todos') count++
      if (f.horarioinicial) count++
      if (f.horariofinal) count++
      if (f.valorinicial) count++
      if (f.valorfinal) count++
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
        const params = { ...filters.value, page: page.value, per_page: 50 }
        const { data } = await api.get('v1/pix', { params })
        const rows = data.data || []
        items.value.push(...rows)
        hasMore.value = rows.length >= 50
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
      activeFiltersCount,
      fetchItems,
      clearFilters,
    }
  },
  {
    persist: { paths: ['filters'] },
  },
)
