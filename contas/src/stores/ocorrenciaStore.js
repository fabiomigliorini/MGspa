import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Livro de ocorrências (TASK-205): o que o caixa cancelou, removeu, diminuiu, descontou ou esqueceu
// aberto, para o gerente conferir. Rotas v1/ocorrencia.

const defaultFilters = () => ({
  codfilial: null,
  codpdv: null,
  tipo: null,
  codusuario: null,
  data_de: null,
  data_ate: null,
  situacao: 'pendente',
  ordem: 'recentes',
})

export const useOcorrenciaStore = defineStore(
  'ocorrencia',
  () => {
    const filters = ref(defaultFilters())
    const items = ref([])
    const loading = ref(false)
    const salvando = ref(false)
    const page = ref(1)
    const hasMore = ref(true)
    const total = ref(0)

    const activeFiltersCount = computed(() => {
      const f = filters.value
      const padrao = defaultFilters()
      return Object.keys(padrao).filter((k) => f[k] !== null && f[k] !== '' && f[k] !== padrao[k])
        .length
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
        const { data } = await api.get('v1/ocorrencia', { params })
        const rows = data.data || []
        items.value = reset ? rows : [...items.value, ...rows]
        total.value = data.meta?.total ?? items.value.length
        hasMore.value = page.value < (data.meta?.last_page ?? page.value)
        page.value++
      } catch (e) {
        notifyError(e, 'Erro ao buscar as ocorrências')
        hasMore.value = false
      } finally {
        loading.value = false
      }
    }

    function substituir(linha) {
      const i = items.value.findIndex((o) => o.codocorrencia === linha.codocorrencia)
      if (i > -1) items.value[i] = linha
    }

    async function conferir(codocorrencia, observacao) {
      salvando.value = true
      try {
        const { data } = await api.post(`v1/ocorrencia/${codocorrencia}/conferir`, { observacao })
        substituir(data.data)
        notifySuccess('Conferida')
        return data.data
      } catch (e) {
        notifyError(e)
        return null
      } finally {
        salvando.value = false
      }
    }

    async function reabrir(codocorrencia) {
      salvando.value = true
      try {
        const { data } = await api.delete(`v1/ocorrencia/${codocorrencia}/conferir`)
        substituir(data.data)
        notifySuccess('Reaberta')
        return data.data
      } catch (e) {
        notifyError(e)
        return null
      } finally {
        salvando.value = false
      }
    }

    function clearFilters() {
      filters.value = defaultFilters()
    }

    return {
      filters,
      items,
      loading,
      salvando,
      page,
      hasMore,
      total,
      activeFiltersCount,
      fetchItems,
      conferir,
      reabrir,
      clearFilters,
    }
  },
  {
    persist: { pick: ['filters'] },
  },
)
