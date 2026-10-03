import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { useAuthStore } from 'src/stores/auth'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Domínio caixas (M11 doc-3): os portadores em espécie com saldo e sessão, e as transferências
// entre portadores (registrar, confirmar, cancelar). Rotas v1/portador/caixas e
// v1/pagamento/transferencia. Quem opera cada lado decide o servidor (decisão 23).

const iso = (d) =>
  [
    d.getFullYear(),
    String(d.getMonth() + 1).padStart(2, '0'),
    String(d.getDate()).padStart(2, '0'),
  ].join('-')

const filtrosVazios = () => {
  const hoje = new Date()
  return {
    codfilial: useAuthStore().usuario?.codfilial ?? null,
    transacao_de: iso(new Date(hoje.getFullYear(), hoje.getMonth(), 1)),
    transacao_ate: iso(hoje),
  }
}

export const useCaixaStore = defineStore(
  'caixa',
  () => {
    const filtros = ref(filtrosVazios())
    const aba = ref('portadores')
    const salvando = ref(false)

    function limpar() {
      filtros.value = filtrosVazios()
    }

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

    // ---- portadores em espécie ----
    const caixas = ref([])
    const carregandoCaixas = ref(false)

    async function buscarCaixas() {
      carregandoCaixas.value = true
      try {
        const { data } = await api.get('v1/portador/caixas', {
          params: { codfilial: filtros.value.codfilial || undefined },
        })
        caixas.value = data.data
      } catch (e) {
        notifyError(e, 'Erro ao buscar os caixas')
      } finally {
        carregandoCaixas.value = false
      }
    }

    // ---- transferências ----
    const pendentes = ref([])
    const historico = ref([])
    const carregandoTransferencias = ref(false)
    const dialogTransferir = ref(false)

    async function buscarTransferencias() {
      carregandoTransferencias.value = true
      const codfilial = filtros.value.codfilial || undefined
      try {
        const [p, h] = await Promise.all([
          api.get('v1/pagamento/transferencia', { params: { codfilial, estado: 'P' } }),
          api.get('v1/pagamento/transferencia', {
            params: {
              codfilial,
              transacao_de: filtros.value.transacao_de || undefined,
              transacao_ate: filtros.value.transacao_ate || undefined,
            },
          }),
        ])
        pendentes.value = p.data.data
        historico.value = h.data.data.filter((t) => t.estado !== 'P')
      } catch (e) {
        notifyError(e, 'Erro ao buscar as transferências')
      } finally {
        carregandoTransferencias.value = false
      }
    }

    function atualizar() {
      buscarCaixas()
      buscarTransferencias()
    }

    async function transferir(dados) {
      const pag = await executar(async () => {
        const { data } = await api.post('v1/pagamento/transferencia', dados)
        return data.data
      })
      if (pag) {
        notifySuccess(
          pag.estado === 'E'
            ? 'Transferência registrada'
            : `Transferência registrada: a confirmar por quem opera ${pag.portadordestino}`,
        )
        atualizar()
      }
      return pag
    }

    async function confirmar(codpagamento) {
      const ok = await executar(
        () => api.post(`v1/pagamento/transferencia/${codpagamento}/confirmar`),
        'Transferência confirmada',
      )
      if (ok) atualizar()
    }

    async function cancelar(codpagamento, justificativa) {
      const ok = await executar(
        () => api.post(`v1/pagamento/transferencia/${codpagamento}/cancelar`, { justificativa }),
        'Transferência cancelada',
      )
      if (ok) atualizar()
    }

    return {
      filtros,
      aba,
      salvando,
      limpar,
      caixas,
      carregandoCaixas,
      buscarCaixas,
      pendentes,
      historico,
      carregandoTransferencias,
      dialogTransferir,
      buscarTransferencias,
      atualizar,
      transferir,
      confirmar,
      cancelar,
    }
  },
  {
    persist: { pick: ['filtros', 'aba'] },
  },
)
