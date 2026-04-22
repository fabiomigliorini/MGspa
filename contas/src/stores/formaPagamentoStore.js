import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { useSelectCacheStore } from 'src/stores/selectCacheStore'

const FLAGS = [
  'avista',
  'boleto',
  'fechamento',
  'notafiscal',
  'entrega',
  'valecompra',
  'lio',
  'pix',
  'stone',
  'integracao',
  'safrapay',
]

const defaultFilters = () => ({
  codformapagamento: null,
  formapagamento: null,
  avista: null,
  boleto: null,
  fechamento: null,
  notafiscal: null,
  entrega: null,
  valecompra: null,
  lio: null,
  pix: null,
  stone: null,
  integracao: null,
  safrapay: null,
  inativo: false,
})

export const useFormaPagamentoStore = defineStore(
  'formaPagamento',
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
      if (f.codformapagamento) count++
      if (f.formapagamento) count++
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
        const { data } = await api.get('v1/forma-pagamento', { params })
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
      const idx = items.value.findIndex(
        (i) => i.codformapagamento === item.codformapagamento,
      )
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
      useSelectCacheStore().invalidate('formaPagamento')
    }

    function removeLocal(codformapagamento) {
      items.value = items.value.filter(
        (i) => i.codformapagamento !== codformapagamento,
      )
      useSelectCacheStore().invalidate('formaPagamento')
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
