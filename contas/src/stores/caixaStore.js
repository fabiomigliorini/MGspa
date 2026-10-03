import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { useAuthStore } from 'src/stores/auth'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Domínio caixas (M11 doc-3): os portadores em espécie com saldo e sessão, e as transferências
// entre portadores (registrar, confirmar, cancelar). Rotas v1/portador/caixas e
// v1/pagamento/transferencia. Quem opera cada lado decide o servidor (decisão 23).
// M12: os períodos dos portadores (fechar com corte, reabrir, lançamento avulso), rotas
// v1/portador-periodo, Financeiro/Admin.
// M13: o que os itens do caixa (chips, ingressos, Bilhete Agora...) movimentaram, para o acerto
// com o parceiro (v1/caixa/item-lancamento, qualquer um consulta).

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

    // os períodos só para o Financeiro/Admin (a página decide)
    let comPeriodos = false
    function atualizar(periodosTambem) {
      if (typeof periodosTambem === 'boolean') comPeriodos = periodosTambem
      buscarCaixas()
      buscarTransferencias()
      if (comPeriodos) buscarPeriodos()
      buscarItens()
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

    // ---- períodos (M12) ----
    const periodos = ref([])
    const carregandoPeriodos = ref(false)
    const periodo = ref(null)
    const dialogPeriodo = ref(false)
    const dialogLancamento = ref(false)

    async function buscarPeriodos() {
      carregandoPeriodos.value = true
      try {
        const { data } = await api.get('v1/portador-periodo', {
          params: {
            codfilial: filtros.value.codfilial || undefined,
            transacao_de: filtros.value.transacao_de || undefined,
            transacao_ate: filtros.value.transacao_ate || undefined,
          },
        })
        periodos.value = data.data
      } catch (e) {
        notifyError(e, 'Erro ao buscar os períodos')
      } finally {
        carregandoPeriodos.value = false
      }
    }

    async function abrirPeriodo(id) {
      periodo.value = null
      dialogPeriodo.value = true
      try {
        const { data } = await api.get(`v1/portador-periodo/${id}`)
        periodo.value = data.data
      } catch (e) {
        notifyError(e, 'Erro ao carregar o período')
      }
    }

    async function fecharPeriodo(id, corte) {
      const ret = await executar(
        () => api.post(`v1/portador-periodo/${id}/fechar`, { corte: corte || null }),
        'Período fechado',
      )
      if (ret) {
        if (periodo.value?.codportadorperiodo === id) periodo.value = ret.data.data
        buscarPeriodos()
      }
      return !!ret
    }

    async function reabrirPeriodo(id) {
      const ret = await executar(
        () => api.post(`v1/portador-periodo/${id}/reabrir`),
        'Período reaberto',
      )
      if (ret) {
        if (periodo.value?.codportadorperiodo === id) periodo.value = ret.data.data
        buscarPeriodos()
      }
    }

    async function lancar(dados) {
      const ret = await executar(
        () => api.post('v1/portador-periodo/lancamento', dados),
        'Lançamento registrado',
      )
      if (ret) buscarPeriodos()
      return !!ret
    }

    // ---- itens do caixa (M13) ----
    const codcaixaitem = ref(null)
    const itens = ref({ linhas: [], totais: [] })
    const carregandoItens = ref(false)

    async function buscarItens() {
      carregandoItens.value = true
      try {
        const { data } = await api.get('v1/caixa/item-lancamento', {
          params: {
            codcaixaitem: codcaixaitem.value || undefined,
            codfilial: filtros.value.codfilial || undefined,
            transacao_de: filtros.value.transacao_de || undefined,
            transacao_ate: filtros.value.transacao_ate || undefined,
          },
        })
        itens.value = data.data
      } catch (e) {
        notifyError(e, 'Erro ao buscar os itens do caixa')
      } finally {
        carregandoItens.value = false
      }
    }

    return {
      codcaixaitem,
      itens,
      carregandoItens,
      buscarItens,
      periodos,
      carregandoPeriodos,
      periodo,
      dialogPeriodo,
      dialogLancamento,
      buscarPeriodos,
      abrirPeriodo,
      fecharPeriodo,
      reabrirPeriodo,
      lancar,
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
    persist: { pick: ['filtros', 'aba', 'codcaixaitem'] },
  },
)
