import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { useAuthStore } from 'src/stores/auth'
import { notifyError } from 'src/utils/notify'

// Itens do caixa (M13 doc-3): o que chips, ingressos, Bilhete Agora... movimentaram por período,
// para o acerto com o parceiro (v1/caixa/item-lancamento). Controle à parte: os portadores, as
// transferências e os períodos ficam na tela do portador (doc-4).

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

    function limpar() {
      filtros.value = filtrosVazios()
    }

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

    function atualizar() {
      buscarItens()
    }

    return {
      filtros,
      limpar,
      codcaixaitem,
      itens,
      carregandoItens,
      buscarItens,
      atualizar,
    }
  },
  {
    persist: { pick: ['filtros', 'codcaixaitem'] },
  },
)
