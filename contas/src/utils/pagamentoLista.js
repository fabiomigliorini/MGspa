import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'
import { useAuthStore } from 'src/stores/auth'

// Listagem única de pagamentos no contas: tudo, com padrão filial do usuário e mês
export const configurarPagamentoLista = () => {
  const store = pagamentoListaStore()
  store.configurar({
    endpoint: 'v1/pagamento',
    padrao: { codfilial: useAuthStore().usuario?.codfilial ?? null },
  })
  return store
}
