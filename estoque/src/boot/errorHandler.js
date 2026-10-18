import { defineBoot } from '#q-app/wrappers'
import { Notify } from 'quasar'

export default defineBoot(({ app }) => {
  app.config.errorHandler = (err, _instance, info) => {
    console.error('Erro Vue não tratado:', err, info)
    Notify.create({
      type: 'negative',
      message: 'Erro inesperado. Se persistir, recarregue a página.',
      timeout: 5000,
    })
  }
})
