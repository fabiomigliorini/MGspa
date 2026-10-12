import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

const defaultFilters = () => ({
  nome: null,
  cpf: null,
  sort: 'horario',
  negocio: 'todos',
  // sem uso: PIX que ninguém usou num pagamento nem marcou (TASK-219)
  uso: 'todos',
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
      if (f.uso !== 'todos') count++
      if (f.horarioinicial) count++
      if (f.horariofinal) count++
      if (f.valorinicial) count++
      if (f.valorfinal) count++
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
        const params = { ...filters.value, page: page.value, per_page: 50 }
        const { data } = await api.get('v1/pix', { params })
        const rows = data.data || []
        items.value = reset ? rows : [...items.value, ...rows]
        hasMore.value = rows.length >= 50
        page.value++
      } finally {
        loading.value = false
      }
    }

    function clearFilters() {
      filters.value = defaultFilters()
    }

    // o uso do PIX relido do servidor, na linha da lista
    function aplicarUso(uso) {
      const item = items.value.find((i) => i.codpix == uso.codpix)
      if (item) {
        item.utilizacao = uso.utilizacao
        item.usuarioutilizacao = uso.usuarioutilizacao
        item.codpagamento = uso.codpagamento
      }
    }

    // o PIX para usar: valor, data, conta, a pessoa do pagador e se já foi usado
    async function carregarUso(codpix) {
      const { data } = await api.get(`v1/pix/${codpix}`)
      aplicarUso(data.data)
      return data.data
    }

    // à mão, no PIX sem pagamento ligado
    async function marcar(codpix) {
      const { data } = await api.post(`v1/pix/${codpix}/utilizacao`)
      aplicarUso(data.data)
    }

    async function desmarcar(codpix) {
      const { data } = await api.delete(`v1/pix/${codpix}/utilizacao`)
      aplicarUso(data.data)
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
      carregarUso,
      marcar,
      desmarcar,
    }
  },
  {
    persist: { pick: ['filters'] },
  },
)
