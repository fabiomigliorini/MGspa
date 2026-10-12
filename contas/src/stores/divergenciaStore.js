import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { notifyError } from 'src/utils/notify'

// Divergências (TASK-218): vendas que não fecham e pagamentos que não batem com as baixas ou com a
// venda. Cada caso é pedido à parte, todos ao mesmo tempo: no histórico todo, cada um leva segundos.
export const CASOS = [
  { value: 'venda', label: 'Venda que não fecha' },
  { value: 'baixas', label: 'Pagamento que não bate com as baixas' },
  { value: 'sem', label: 'Pagamento sem venda nem título' },
  { value: 'aberta', label: 'Pagamento em venda aberta' },
  { value: 'cancelada', label: 'Pagamento em venda cancelada' },
]

const defaultFilters = () => ({
  caso: null,
  data_de: null,
  data_ate: null,
  codfilial: null,
})

export const useDivergenciaStore = defineStore(
  'divergencia',
  () => {
    const filters = ref(defaultFilters())
    const items = ref([])
    const total = ref(0)
    const limite = ref(0)
    const loading = ref(false)
    // a resposta de um filtro antigo não sobrescreve a do atual
    let consulta = 0

    const activeFiltersCount = computed(
      () => Object.values(filters.value).filter((v) => v !== null && v !== '').length,
    )

    async function fetchItems() {
      const esta = ++consulta
      loading.value = true
      try {
        const casos = filters.value.caso ? [filters.value.caso] : CASOS.map((c) => c.value)
        const respostas = await Promise.all(
          casos.map((caso) =>
            api.get('v1/pagamento/divergencia', { params: { ...filters.value, caso } }),
          ),
        )
        if (esta !== consulta) return
        const todos = respostas.flatMap((r) => r.data.data)
        todos.sort((a, b) => String(b.data).localeCompare(String(a.data)))
        limite.value = respostas[0]?.data.limite ?? 0
        items.value = todos.slice(0, limite.value)
        total.value = respostas.reduce((soma, r) => soma + r.data.total, 0)
      } catch (e) {
        if (esta === consulta) notifyError(e, 'Erro ao carregar as divergências')
      } finally {
        if (esta === consulta) loading.value = false
      }
    }

    function clearFilters() {
      filters.value = defaultFilters()
    }

    return {
      filters,
      items,
      total,
      limite,
      loading,
      activeFiltersCount,
      fetchItems,
      clearFilters,
    }
  },
  { persist: { pick: ['filters'] } },
)
