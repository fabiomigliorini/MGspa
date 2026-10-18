import moment from 'moment'
// do dist: no Vite o 'moment' resolve para moment/dist/moment.js, e o moment/locale/pt-br
// registraria o idioma em outra cópia (a CommonJS), deixando as datas em inglês
import 'moment/dist/locale/pt-br'

moment.locale('pt-br')
