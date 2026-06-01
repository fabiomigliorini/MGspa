import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const hoje = () => new Date().toISOString().slice(0, 10)

const defaultFilters = () => ({
  inativo: 0, // 0 = ativos | 1 = inativos | 9 = todos
  conferidos: false, // false = a conferir | true = já conferidos
  dataCorte: hoje(),
})

export const useConferenciaStore = defineStore('conferencia', () => {
  // Parâmetros da conferência (definidos na tela de setup)
  const setup = ref({
    codestoquelocal: null,
    estoquelocal: null,
    fiscal: false,
    conferenciaperiodica: false,
    codmarca: null,
    marca: null,
    data: null, // datetime ISO em que o ajuste será lançado
  })

  const filters = ref(defaultFilters())
  const produtos = ref([])
  const local = ref(null)
  const marcaInfo = ref(null)
  const loading = ref(false)
  const page = ref(1)
  const hasMore = ref(true)

  const activeFiltersCount = computed(() => {
    const f = filters.value
    let count = 0
    if (f.inativo !== 0) count++
    if (f.dataCorte !== hoje()) count++
    return count
  })

  function aplicarParamsRota(params) {
    setup.value.codestoquelocal = Number(params.codestoquelocal)
    setup.value.codmarca = Number(params.codmarca) || null
    setup.value.fiscal = params.fiscal === '1' || params.fiscal === 1
    setup.value.conferenciaperiodica =
      params.conferenciaperiodica === '1' || params.conferenciaperiodica === 1
    setup.value.data = params.data
  }

  async function fetchListagem(reset = false) {
    if (reset) {
      page.value = 1
      hasMore.value = true
    }
    if (!hasMore.value || loading.value) return

    loading.value = true
    try {
      const params = {
        codestoquelocal: setup.value.codestoquelocal,
        fiscal: setup.value.fiscal,
        conferenciaperiodica: setup.value.conferenciaperiodica,
        inativo: filters.value.inativo,
        conferidos: filters.value.conferidos,
        dataCorte: filters.value.dataCorte,
        page: page.value,
      }
      if (setup.value.codmarca) params.codmarca = setup.value.codmarca
      const { data } = await api.get('v1/estoque-saldo-conferencia/busca-listagem', { params })
      const rows = data.produtos || []
      if (reset) {
        local.value = data.local ?? null
        marcaInfo.value = data.marca ?? null
        produtos.value = rows
      } else {
        produtos.value = [...produtos.value, ...rows]
      }
      hasMore.value = rows.length > 0
      page.value++
    } finally {
      loading.value = false
    }
  }

  function setConferidos(valor) {
    filters.value.conferidos = valor
  }

  function clearFilters() {
    const periodicaAtual = filters.value.conferidos
    filters.value = defaultFilters()
    filters.value.conferidos = periodicaAtual
  }

  return {
    setup,
    filters,
    produtos,
    local,
    marcaInfo,
    loading,
    page,
    hasMore,
    activeFiltersCount,
    aplicarParamsRota,
    fetchListagem,
    setConferidos,
    clearFilters,
  }
})
