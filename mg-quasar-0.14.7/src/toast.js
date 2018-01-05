import { Toast } from 'quasar'
export default {
  create: function (mensagem) {
    Toast.create.negative({
      html: mensagem
    })
  }
}
