import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { useAuthStore } from 'src/stores/auth'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Domínio conferência (M9 doc-3): o que o caixa movimentou e o gerente confere — pendências da
// filial, lote da maquineta, sessão da gaveta, venda desbalanceada, cheque e vale — e as
// correções dos lançamentos. Rotas v1/conferencia.

export const useConferenciaStore = defineStore(
  'conferencia',
  () => {
    // ---- pendências ----
    const codfilial = ref(useAuthStore().usuario?.codfilial ?? null)
    const pendencias = ref([])
    const carregando = ref(false)

    async function buscarPendencias() {
      carregando.value = true
      try {
        const { data } = await api.get('v1/conferencia', {
          params: { codfilial: codfilial.value || undefined },
        })
        pendencias.value = data.data
      } catch (e) {
        notifyError(e, 'Erro ao buscar as pendências')
      } finally {
        carregando.value = false
      }
    }

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

    // ---- lote da maquineta ----
    const lote = ref(null)

    async function carregarLote(id) {
      lote.value = null
      try {
        const { data } = await api.get(`v1/conferencia/lote/${id}`)
        lote.value = data.data
      } catch (e) {
        notifyError(e, 'Erro ao carregar o lote')
      }
    }

    const fecharLote = (id, payload) =>
      executar(async () => {
        const { data } = await api.post(`v1/conferencia/lote/${id}/fechar`, payload)
        lote.value = data.data
        return lote.value
      }, 'Lote conferido')

    const reabrirLote = (id) =>
      executar(async () => {
        const { data } = await api.post(`v1/conferencia/lote/${id}/reabrir`)
        lote.value = data.data
        return lote.value
      }, 'Lote reaberto')

    const enviarFotoLote = (id, anexoBase64) =>
      executar(async () => {
        const { data } = await api.post(`v1/conferencia/lote/${id}/foto`, { anexoBase64 })
        if (lote.value?.codmaquinetalote === id) lote.value.fotos = data.data.fotos
        return data.data
      }, 'Foto do borderô anexada')

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

    const acertarVenda = (id, payload) =>
      executar(async () => {
        const { data } = await api.post(`v1/conferencia/venda/${id}/acerto`, payload)
        venda.value = data.data
        return venda.value
      }, 'Diferença acertada')

    const desfazerAcerto = (codnegocioacerto) =>
      executar(async () => {
        const { data } = await api.delete(`v1/conferencia/acerto/${codnegocioacerto}`)
        venda.value = data.data
        return venda.value
      }, 'Acerto desfeito')

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

    // cheque e vale recebido, item a item
    const conferirPagamento = (id) =>
      executar(async () => {
        const { data } = await api.post(`v1/conferencia/pagamento/${id}/conferir`)
        pendencias.value = pendencias.value.filter(
          (p) => !(['cheque', 'vale'].includes(p.tipo) && p.id === id),
        )
        return data.data
      }, 'Conferido')

    return {
      codfilial,
      pendencias,
      carregando,
      salvando,
      buscarPendencias,
      lote,
      carregarLote,
      fecharLote,
      reabrirLote,
      enviarFotoLote,
      venda,
      carregarVenda,
      acertarVenda,
      desfazerAcerto,
      incluirPagamento,
      corrigirPagamento,
      indevido,
      conferirPagamento,
    }
  },
  {
    persist: { pick: ['codfilial'] },
  },
)
