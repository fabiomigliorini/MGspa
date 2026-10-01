import { formataTimestampIso } from '@components/formatters'
import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import { api } from 'src/boot/axios'
import { pdvStore } from './pdv'
import { sincronizacaoStore } from './sincronizacao'
import moment from 'moment'

const sPdv = pdvStore()
const sSinc = sincronizacaoStore()

export const liquidacaoStore = defineStore('liquidacao', {
  persist: {
    pick: ['filtroListagem', 'listagem'],
  },
  state: () => ({
    opcoes: {
      sentido: [
        { value: 'R', label: 'Recebimentos' },
        { value: 'P', label: 'Pagamentos' },
        { value: 'C', label: 'Encontro de contas' },
      ],
      meio: [
        { value: 1, label: 'Dinheiro' },
        { value: 2, label: 'Cheque' },
        { value: 3, label: 'Cartão Crédito' },
        { value: 4, label: 'Cartão Débito' },
        { value: 15, label: 'Boleto' },
        { value: 17, label: 'PIX' },
        { value: 18, label: 'Transferência' },
        { value: 91, label: 'Compensação' },
        { value: 92, label: 'Folha' },
        { value: 99, label: 'Outros' },
      ],
    },
    counter: 0,
    listagem: [],
    filtro: {},
    paginacao: {
      current_page: 0,
      from: null,
      last_page: null,
      path: null,
      per_page: null,
      to: null,
      total: 0,
    },
  }),

  // getters: {
  //   doubleCount(state) {
  //     return state.counter * 2;
  //   },
  // },

  actions: {
    async inicializaFiltro() {
      // filtro guardado ainda da liquidação (antes do M6): recomeça
      if (Object.keys(this.filtro).length > 0 && !('lancamento_de' in this.filtro)) {
        this.filtro = {}
      }
      if (Object.keys(this.filtro).length > 0) {
        return
      }
      const filtro = {
        codpdv: null,
        codusuariocriacao: null,
        codportador: null,
        codpagamento: null,
        lancamento_de: moment().subtract(7, 'd').startOf('day').format('YYYY-MM-DD HH:mm'),
        lancamento_ate: formataTimestampIso(moment().endOf('day').toDate()),
        codpessoa: null,
        sentido: null,
        meio: [],
        valor_de: null,
        valor_ate: null,
      }
      const pdv = await sPdv.findByUuid(sSinc.pdv.uuid)
      if (pdv) {
        filtro.codpdv = pdv.codpdv
      }
      this.filtro = filtro
      await this.getLiquidacoes()
    },

    async getLiquidacoes() {
      this.paginacao.current_page = 0
      this.paginacao.last_page = 99999
      // this.negocios = [];
      await this.getLiquidacoesPaginacao()
    },

    async getLiquidacoesPaginacao() {
      if (this.paginacao.current_page >= this.paginacao.last_page) {
        return false
      }
      try {
        const filtro = { ...this.filtro }
        filtro.pdv = sSinc.pdv.uuid
        filtro.page = this.paginacao.current_page + 1
        const { data } = await api.get('/v1/pdv/pagamento', {
          params: filtro,
        })
        if (filtro.page == 1) {
          this.listagem = data.data
        } else {
          this.listagem = this.listagem.concat(data.data)
        }
        this.paginacao = data.meta
      } catch (error) {
        var message = error?.response?.data?.message
        if (!message) {
          message = error?.message
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000, // 3 segundos
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },
  },
})
