import { formataDataIso } from "@components/formatters";
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => {
  const ate = new Date()
  const de = new Date()
  de.setDate(de.getDate() - 7)
  return {
    codliquidacaotitulo: null,
    codpessoa: null,
    codgrupoeconomico: null,
    codportador: null,
    codusuariocriacao: null,
    estornado: '0',
    criacao_de: null,
    criacao_ate: null,
    transacao_de: formataDataIso(de),
    transacao_ate: formataDataIso(ate),
  }
}

export const useLiquidacaoTituloStore = defineStore(
  'liquidacaoTitulo',
  () => {
    const filters = ref(defaultFilters())
    const items = ref([])
    const loading = ref(false)
    const page = ref(1)
    const hasMore = ref(true)
    const total = ref(0)

    const activeFiltersCount = computed(() => {
      const f = filters.value
      const def = defaultFilters()
      let count = 0
      Object.keys(def).forEach((k) => {
        const v = f[k]
        if (v === null || v === undefined || v === '') return
        if (Array.isArray(v) && v.length === 0) return
        if (v !== def[k]) count++
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
        const { data } = await api.get('v1/liquidacao-titulo', { params })
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

    function upsertLocal(item) {
      const idx = items.value.findIndex(
        (i) => i.codliquidacaotitulo === item.codliquidacaotitulo,
      )
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
  { persist: { pick: ['filters'] } },
)
