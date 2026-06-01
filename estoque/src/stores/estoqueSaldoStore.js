import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  valor: 'custo', // 'custo' | 'venda'
  codestoquelocal: null,
  codmarca: null,
  saldo: null, // -1 negativo, 1 positivo
  minimo: null, // -1 abaixo, 1 acima
  maximo: null, // -1 abaixo, 1 acima
})

export const useEstoqueSaldoStore = defineStore(
  'estoqueSaldo',
  () => {
    const filters = ref(defaultFilters())
    const agrupamento = ref('secaoproduto')
    const drill = ref([]) // [{ label, filtroKey, filtroVal, agrupamentoResultante }]
    const itens = ref([])
    const loading = ref(false)

    const activeFiltersCount = computed(() => {
      const f = filters.value
      let c = 0
      if (f.codestoquelocal) c++
      if (f.codmarca) c++
      if (f.saldo) c++
      if (f.minimo) c++
      if (f.maximo) c++
      return c
    })

    function montarParams() {
      const f = filters.value
      const params = { agrupamento: agrupamento.value, valor: f.valor }
      if (f.codestoquelocal) params.codestoquelocal = f.codestoquelocal
      if (f.codmarca) params.codmarca = f.codmarca
      if (f.saldo) params.saldo = f.saldo
      if (f.minimo) params.minimo = f.minimo
      if (f.maximo) params.maximo = f.maximo
      for (const d of drill.value) params[d.filtroKey] = d.filtroVal
      return params
    }

    async function fetchItems() {
      loading.value = true
      try {
        const { data } = await api.get('v1/estoque-saldo-grid', { params: montarParams() })
        itens.value = data.itens || []
      } finally {
        loading.value = false
      }
    }

    function drillInto(item) {
      if (!item.proximo) return // variação é folha
      drill.value.push({
        label: item.item,
        filtroKey: item.filtroProximo,
        filtroVal: item.coditem,
        agrupamentoResultante: item.proximo,
      })
      agrupamento.value = item.proximo
      fetchItems()
    }

    function voltarPara(index) {
      // index 0 = raiz; index = drill.length = nível atual (no-op)
      if (index >= drill.value.length) return
      agrupamento.value = index === 0 ? 'secaoproduto' : drill.value[index - 1].agrupamentoResultante
      drill.value = drill.value.slice(0, index)
      fetchItems()
    }

    function setAgrupamentoRaiz(novo) {
      agrupamento.value = novo
      drill.value = []
      fetchItems()
    }

    function clearFilters() {
      filters.value = defaultFilters()
      fetchItems()
    }

    return {
      filters,
      agrupamento,
      drill,
      itens,
      loading,
      activeFiltersCount,
      fetchItems,
      drillInto,
      voltarPara,
      setAgrupamentoRaiz,
      clearFilters,
    }
  },
  {
    persist: { pick: ['filters'] },
  },
)
