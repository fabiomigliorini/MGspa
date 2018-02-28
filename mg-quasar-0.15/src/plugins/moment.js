import moment from 'moment'

export default ({ Vue }) => {
  moment.locale('pt-BR')
  Vue.prototype.moment = moment
}
