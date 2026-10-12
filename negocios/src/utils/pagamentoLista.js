import { pagamentoListaStore } from '@components/stores/pagamentoListaStore'
import { sincronizacaoStore } from 'stores/sincronizacao'

// a listagem única de pagamentos, travada no PDV (o servidor força o PDV do dispositivo)
export const configurarPagamentoLista = () => {
  const store = pagamentoListaStore()
  store.configurar({
    endpoint: 'v1/pdv/pagamento',
    fixos: { pdv: sincronizacaoStore().pdv.uuid },
    travadoPdv: true,
  })
  return store
}
