import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Domínio conferência (M9 doc-3): o que o caixa movimentou e o gerente confere — venda
// desbalanceada — e as correções dos lançamentos (também usadas na tela da maquineta e seus
// períodos). As pendências aparecem no painel da filial (TASK-203). Rotas v1/conferencia.

export const useConferenciaStore = defineStore('conferencia', () => {
  const salvando = ref(false)

  async function executar(fn, mensagem) {
    salvando.value = true
    try {
      const ret = await fn()
      if (mensagem) notifySuccess(mensagem)
      return ret
    } catch (e) {
      notifyError(e)
      return null
    } finally {
      salvando.value = false
    }
  }

  // ---- venda desbalanceada ----
  const venda = ref(null)

  async function carregarVenda(id) {
    venda.value = null
    try {
      const { data } = await api.get(`v1/conferencia/venda/${id}`)
      venda.value = data.data
    } catch (e) {
      notifyError(e, 'Erro ao carregar a venda')
    }
  }

  const incluirPagamento = (id, payload) =>
    executar(async () => {
      const { data } = await api.post(`v1/conferencia/venda/${id}/pagamento`, payload)
      venda.value = data.data
      return venda.value
    }, 'Pagamento incluído')

  // ---- pagamento ----
  const corrigirPagamento = (id, payload) =>
    executar(async () => {
      const { data } = await api.post(`v1/conferencia/pagamento/${id}/correcao`, payload)
      return data.data
    }, 'Lançamento corrigido')

  const indevido = (id, justificativa) =>
    executar(async () => {
      const { data } = await api.post(`v1/conferencia/pagamento/${id}/indevido`, {
        justificativa,
      })
      return data.data
    }, 'Registro indevido cancelado')

  return {
    salvando,
    venda,
    carregarVenda,
    incluirPagamento,
    corrigirPagamento,
    indevido,
  }
})
