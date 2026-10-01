import { formataDataIso } from '@components/formatters'
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

// Recebimentos e pagamentos de títulos (M6 do plano doc-3; era a Liquidação).
// Um pagamento por forma; o movimento de cada título aponta para ele.
const defaultFilters = () => {
  const ate = new Date()
  const de = new Date()
  de.setDate(de.getDate() - 7)
  return {
    codpagamento: null,
    codpessoa: null,
    codgrupoeconomico: null,
    codgrupocliente: null,
    codportador: null,
    meio: [],
    sentido: null,
    codusuariocriacao: null,
    cancelado: '0',
    criacao_de: null,
    criacao_ate: null,
    lancamento_de: formataDataIso(de),
    lancamento_ate: formataDataIso(ate),
  }
}

// meios que o contas escolhe (os demais aparecem só no histórico)
export const MEIOS = [
  { value: 1, label: 'Dinheiro' },
  { value: 2, label: 'Cheque' },
  { value: 3, label: 'Cartão Crédito' },
  { value: 4, label: 'Cartão Débito' },
  { value: 15, label: 'Boleto' },
  { value: 16, label: 'Depósito' },
  { value: 17, label: 'PIX' },
  { value: 18, label: 'Transferência' },
  { value: 99, label: 'Outros' },
]

// filtro mostra também os internos do histórico
export const MEIOS_FILTRO = [
  ...MEIOS,
  { value: 12, label: 'Vale' },
  { value: 91, label: 'Compensação' },
  { value: 92, label: 'Folha' },
  { value: 93, label: 'Permuta' },
  { value: 94, label: 'Perda' },
]

// meio sugerido pelo tipo do portador (o mesmo do backend)
export const meioDoTipo = (tipo) => ({ E: 1, B: 18, A: 18, C: 3 })[tipo] ?? 99

export const usePagamentoStore = defineStore(
  'pagamento',
  () => {
    const filters = ref(defaultFilters())
    const items = ref([])
    const loading = ref(false)
    const page = ref(1)
    const hasMore = ref(true)
    const total = ref(0)
    const pagamento = ref(null)

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
        const { data } = await api.get('v1/pagamento', { params })
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
      const idx = items.value.findIndex((i) => i.codpagamento === item.codpagamento)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
    }

    async function carregar(id) {
      const { data } = await api.get(`v1/pagamento/${id}`)
      pagamento.value = data.data
      return pagamento.value
    }

    async function criar(payload) {
      const { data } = await api.post('v1/pagamento', payload)
      pagamento.value = data.data
      upsertLocal(data.data)
      return pagamento.value
    }

    async function atualizar(id, payload) {
      const { data } = await api.put(`v1/pagamento/${id}`, payload)
      pagamento.value = data.data
      upsertLocal(data.data)
      return pagamento.value
    }

    async function estornar(id, justificativa) {
      const { data } = await api.post(`v1/pagamento/${id}/estornar`, { justificativa })
      pagamento.value = data.data
      upsertLocal(data.data)
      return pagamento.value
    }

    return {
      filters,
      items,
      loading,
      page,
      hasMore,
      total,
      pagamento,
      activeFiltersCount,
      fetchItems,
      clearFilters,
      upsertLocal,
      carregar,
      criar,
      atualizar,
      estornar,
    }
  },
  { persist: { pick: ['filters'] } },
)
