import { api } from 'src/services/api'
import { useAuthStore } from 'src/stores/auth'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

// O contas como contexto do wizard de cobrança (Receber ou Pagar Títulos, Vale / Adiantamento):
// sem PDV, portadores das filiais do usuário menos gaveta (o PDV recebe na dele) e maquinetas de
// todas as filiais (as de outra filial vêm marcadas; o financeiro conserta lançamento de qualquer
// loja).

// o financeiro não usa gaveta
export const FORMAS_TITULOS = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque', 'banco', 'compensacao'],
  saida: ['banco', 'dinheiro', 'cartaoEmpresa', 'cheque', 'compensacao'],
}

// vale e adiantamento nascem com dinheiro: sem compensação
export const FORMAS_ADIANTAMENTO = {
  entrada: ['cartao', 'pix', 'dinheiro', 'cheque', 'banco'],
  saida: ['banco', 'dinheiro', 'cartaoEmpresa', 'cheque'],
}

const portadores = async () => {
  const auth = useAuthStore()
  const todos = await useSelectCacheStore().loadList('portador', 'v1/select/portador')
  const filiais = auth.filiaisRestritas()
  return todos.filter(
    (p) => !p.gaveta && (filiais == null || filiais.map(Number).includes(Number(p.codfilial))),
  )
}

export const contextoCobranca = async (codfilialInformada) => {
  const codfilial = codfilialInformada ?? useAuthStore().usuario?.codfilial ?? null
  return {
    pdv: null,
    codfilial,
    portadores: await portadores(),
    carregarMaquinetas: async () => {
      const { data } = await api.get(`v1/cobranca/maquineta/${codfilial}`)
      return data.data
    },
  }
}
