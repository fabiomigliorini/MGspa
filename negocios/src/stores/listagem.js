import { formataTimestampIso } from '@components/formatters'
import { defineStore } from 'pinia'
import { api } from 'src/services/api'
import { Notify } from 'quasar'
import moment from 'moment'
import { negocioStore } from './negocio'
import { sincronizacaoStore } from './sincronizacao'

const sNegocio = negocioStore()
const sSinc = sincronizacaoStore()

export const listagemStore = defineStore('listagem', {
  persist: false,

  state: () => ({
    opcoes: {
      // à vista/à prazo como no MGsis (valoravista/valoraprazo), m<meio> do
      // pagamento, c<condição> da parcela
      forma: [
        { label: 'À Vista', value: 'avista' },
        { label: 'À Prazo', value: 'aprazo' },
        { label: 'Dinheiro', value: 'm1' },
        { label: 'Cartão Crédito', value: 'm3' },
        { label: 'Cartão Débito', value: 'm4' },
        { label: 'PIX QR Code', value: 'm17' },
        { label: 'Cheque', value: 'm2' },
        { label: 'Vale Compras', value: 'm12' },
        { label: 'PIX Chave', value: 'cX' },
        { label: 'Entrega', value: 'cE' },
        { label: 'Fechamento', value: 'cF' },
        { label: 'Crediário', value: 'cP' },
        { label: 'Boleto', value: 'cB' },
      ],
      integracao: ['Manual', 'Integrado'],
      codnegociostatus: [
        {
          label: 'Aberto',
          value: 1,
        },
        {
          label: 'Fechado',
          value: 2,
        },
        {
          label: 'Cancelado',
          value: 3,
        },
        // venda fechada que o gerente reabriu e ainda não fechou de novo
        {
          label: 'Reaberto',
          value: 'R',
        },
      ],
    },
    filtro: {},
    negocios: [],
    carregando: false,
    orcamentos: [],
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

  getters: {
    filtrosAtivos: (state) =>
      Object.values(state.filtro).filter((valor) =>
        Array.isArray(valor) ? valor.length > 0 : valor !== null && valor !== '',
      ).length,
  },

  actions: {
    limparFiltros() {
      this.filtro = Object.fromEntries(
        Object.entries(this.filtro).map(([chave, valor]) => [
          chave,
          Array.isArray(valor) ? [] : null,
        ]),
      )
    },

    async inicializaFiltro() {
      if (Object.keys(this.filtro).length > 0) {
        return
      }
      const filtro = {
        codnegocio: null,
        lancamento_de: moment().subtract(7, 'd').startOf('day').format('YYYY-MM-DD HH:mm'),
        lancamento_ate: formataTimestampIso(moment().endOf('day').toDate()),
        codestoquelocal: sNegocio.padrao.codestoquelocal,
        codusuario: null,
        codnegociostatus: null,
        // codnaturezaoperacao: sNegocio.padrao.codnaturezaoperacao,
        codnaturezaoperacao: null,
        codpessoa: null,
        codpessoavendedor: null,
        codpessoatransportador: null,
        codpdv: null,
        codmaquineta: null,
        integracao: [],
        forma: [],
        valor_de: null,
        valor_ate: null,
      }
      if (sSinc.pdv.codpdv) {
        filtro.codpdv = sSinc.pdv.codpdv
      }
      // a ListagemPage observa o filtro e busca; buscar aqui também pedia 2 vezes
      this.filtro = filtro
    },

    async getNegocios() {
      this.paginacao.current_page = 0
      this.paginacao.last_page = 99999
      // this.negocios = [];
      await this.getNegociosPaginacao()
    },

    async getNegociosPaginacao() {
      if (this.paginacao.current_page >= this.paginacao.last_page) {
        return false
      }
      this.carregando = true
      try {
        const filtro = { ...this.filtro }
        filtro.page = this.paginacao.current_page + 1
        const { data } = await api.get('/v1/pdv/negocio', {
          params: filtro,
        })
        if (filtro.page == 1) {
          this.negocios = data.data
        } else {
          this.negocios = this.negocios.concat(data.data)
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
        // para de paginar: senão o infinite scroll pede de novo na hora, sem
        // parar. Mudar um filtro chama o getNegocios, que recomeça da 1ª página
        this.paginacao.last_page = this.paginacao.current_page
        return false
      } finally {
        this.carregando = false
      }
    },

    async getOrcamentos(uuid) {
      try {
        const { data } = await api.get('/v1/pdv/orcamento', {
          params: {
            uuid: uuid,
          },
        })
        this.orcamentos = data.data
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
