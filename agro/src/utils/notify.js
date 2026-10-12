import { Notify } from 'quasar'
import { extrairErro } from './extrairErro'

export function notifySuccess(message) {
  Notify.create({
    color: 'green-5',
    textColor: 'white',
    icon: 'done',
    message,
  })
}

export function notifyWarning(message) {
  Notify.create({
    color: 'orange-5',
    textColor: 'white',
    icon: 'warning',
    message,
  })
}

export function notifyError(error, fallback = 'Ocorreu um erro') {
  const message = typeof error === 'string' ? error : extrairErro(error, fallback)
  Notify.create({
    color: 'red-5',
    textColor: 'white',
    icon: 'error',
    message,
  })
}
