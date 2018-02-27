import numeral from 'numeral'

export default ({ Vue }) => {
  numeral.register('locale', 'pt-BR', {
    delimiters: {
      thousands: '.',
      decimal: ','
    },
    abbreviations: {
      thousand: 'K',
      million: 'M',
      billion: 'B',
      trillion: 'T'
    },
    ordinal: function (number) {
      return 'º'
    },
    currency: {
      symbol: 'R$'
    }
  })
  numeral.locale('pt-BR')
  numeral.defaultFormat('0,0.00')
  Vue.prototype.numeral = numeral
}
