// O vale de crédito do título (Vale Compras / Contra Vale, TASK-216): o mesmo papel na venda, na
// tela do título e na do pagamento. O caixa bipa o código VAL… para pagar com ele. `imprimir` (no
// PDV) manda para a térmica pelo Imprimir do cupom.
import { abrirPdf } from './abrirPdf'

export const abrirVale = (api, codtitulo, imprimir = null) =>
  abrirPdf(
    api,
    `v1/titulo/${codtitulo}/vale`,
    {},
    { title: 'Vale', size: 'cupom', onImprimir: imprimir },
  )
