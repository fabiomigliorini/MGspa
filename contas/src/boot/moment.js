import { defineBoot } from '#q-app/wrappers'
import moment from 'moment'
import 'moment/locale/pt-br'

moment.locale('pt-br')
moment.updateLocale('pt-br', {
  monthsShort: [
    'jan', 'fev', 'mar', 'abr', 'mai', 'jun',
    'jul', 'ago', 'set', 'out', 'nov', 'dez'
  ]
});
export default defineBoot(({ app }) => {
  app.config.globalProperties.$moment = moment
})

export { moment }
