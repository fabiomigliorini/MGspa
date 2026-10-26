// Cancelar, da janela da cobrança (PIX, Stone, SafraPay), o pagamento que ela gerou: o mesmo
// Cancelar do detalhe do pagamento (v1/pagamento/{id}/cancelar), em qualquer tela. Na venda, ela
// precisa estar aberta e a cobrança se solta dela; na baixa, as baixas são estornadas. Quem pode
// é o servidor que diz (TASK-30).
import { Dialog, Notify } from 'quasar'
import { api } from 'src/services/api'
import { avisar } from './eventos.js'

// aviso: o que fica de fora (o estorno na maquininha, o PIX que volta a ficar sem uso).
// Resolve true quando cancelou; false quando a pessoa voltou ou o servidor recusou
export const cancelarPagamento = ({ codpagamento, codnegocio, aviso }) =>
  new Promise((resolve) => {
    const onde = codnegocio
      ? 'Ele sai da venda, que precisa estar aberta (reabra antes), e a cobrança se solta dela.'
      : 'Ele sai do caixa/banco e as baixas dos títulos são estornadas.'
    Dialog.create({
      title: 'Cancelar pagamento?',
      message: `${onde} ${aviso} Informe o motivo:`,
      prompt: {
        model: '',
        type: 'text',
        outlined: true,
        isValid: (v) => (v || '').trim().length >= 5,
      },
      ok: { label: 'Cancelar pagamento', color: 'negative', flat: true },
      cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    })
      .onOk(async (justificativa) => {
        try {
          await api.post('/v1/pagamento/' + codpagamento + '/cancelar', { justificativa })
        } catch (error) {
          console.log(error)
          Notify.create({
            type: 'negative',
            message: error?.response?.data?.message ?? error?.message,
            color: 'red-5',
            icon: 'error',
          })
          resolve(false)
          return
        }
        Notify.create({
          type: 'positive',
          message: 'Pagamento cancelado!',
          color: 'green-5',
          icon: 'done',
        })
        // a venda aberta na tela recarrega do servidor
        await avisar('cobrancaAtualizada', { codnegocio })
        resolve(true)
      })
      .onCancel(() => resolve(false))
  })
