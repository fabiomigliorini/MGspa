import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'

// a listagem única de pagamentos, travada no PDV (o servidor força o PDV do dispositivo, que vai
// no cabeçalho X-Pdv)
export const configurarPagamentoLista = () => {
  const store = pagamentoListaStore()
  store.configurar({
    endpoint: 'v1/pdv/pagamento',
    travadoPdv: true,
  })
  return store
}
