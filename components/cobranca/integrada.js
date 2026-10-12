// Abre o dialog especialista da cobrança integrada (PixCobDialog, PagarMePedidoDialog,
// SaurusPedidoDialog), que o app renderiza onde usa o wizard.
import { pixStore } from '@components/stores/pixStore'
import { pagarMeStore } from '@components/stores/pagarMeStore'
import { saurusStore } from '@components/stores/saurusStore'

export const abrirCobrancaIntegrada = ({ tipo, dados }) => {
  switch (tipo) {
    case 'pix': {
      const sPix = pixStore()
      sPix.pixCob = dados
      sPix.dialog.detalhesPixCob = true
      break
    }
    case 'pagarme': {
      const sPagarMe = pagarMeStore()
      sPagarMe.pedido = dados
      sPagarMe.dialog.detalhesPedido = true
      break
    }
    case 'saurus': {
      const sSaurus = saurusStore()
      sSaurus.pedido = dados
      sSaurus.dialog.detalhesPedido = true
      break
    }
  }
}
