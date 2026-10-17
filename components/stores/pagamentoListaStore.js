// Listagem única de pagamentos (MgPagamentoLista, M6.1 do plano doc-3): venda, título,
// transferência e avulso, com os filtros do plano. O app configura onde busca:
//   contas:   { endpoint: 'v1/pagamento', padrao: { codfilial } }
//   negocios: { endpoint: 'v1/pdv/pagamento', fixos: { pdv: uuid }, travadoPdv: true }
// O PDV fica travado no servidor (v1/pdv/pagamento força o PDV do dispositivo).
import { defineStore } from 'pinia'
import { api } from 'src/services/api'

// sem período por padrão: as datas só filtram quando a pessoa preenche
const filtrosVazios = () => {
  return {
    codpagamento: null,
    transacao_de: null,
    transacao_ate: null,
    valor_de: null,
    valor_ate: null,
    codfilial: null,
    codpdv: null,
    codportador: null,
    meio: [],
    estado: [],
    origem: [],
    codpessoa: null,
    codmaquineta: null,
    documento: null,
    codusuariocriacao: null,
  }
}

// busca corrente: o filtro novo não espera a anterior, e a resposta dela é descartada
let seq = 0

export const pagamentoListaStore = defineStore('pagamentoLista', {
  persist: {
    pick: ['filtros', 'filtrosDe'],
  },

  state: () => ({
    endpoint: 'v1/pagamento',
    fixos: {},
    padrao: {},
    travadoPdv: false,
    filtros: filtrosVazios(),
    // de qual listagem são os filtros guardados (o padrão vale na primeira vez)
    filtrosDe: null,
    itens: [],
    carregando: false,
    pagina: 1,
    temMais: true,
    total: 0,
    // detalhe aberto (a página do pagamento, no contas e no PDV)
    pagamento: null,
  }),

  getters: {
    filtrosAtivos() {
      const padrao = { ...filtrosVazios(), ...this.padrao }
      return Object.keys(padrao).filter((k) => {
        const v = this.filtros[k]
        if (v === null || v === undefined || v === '') return false
        if (Array.isArray(v)) return v.length > 0
        return v !== padrao[k]
      }).length
    },
  },

  actions: {
    configurar({ endpoint, fixos = {}, padrao = {}, travadoPdv = false }) {
      this.endpoint = endpoint
      this.fixos = fixos
      this.padrao = padrao
      this.travadoPdv = travadoPdv
      if (this.filtrosDe !== endpoint) {
        this.filtrosDe = endpoint
        this.limpar()
      }
    },

    limpar() {
      this.filtros = { ...filtrosVazios(), ...this.padrao }
    },

    parametros() {
      const params = { ...this.fixos }
      for (const [k, v] of Object.entries(this.filtros)) {
        if (v === null || v === undefined || v === '') continue
        if (Array.isArray(v) && !v.length) continue
        if (this.travadoPdv && (k === 'codfilial' || k === 'codpdv')) continue
        params[k] = v
      }
      return params
    },

    async buscar(reset = false) {
      if (reset) {
        seq++
        this.pagina = 1
        this.temMais = true
      } else if (!this.temMais || this.carregando) {
        return
      }
      const minha = seq
      this.carregando = true
      try {
        const { data } = await api.get(this.endpoint, {
          params: { ...this.parametros(), page: this.pagina },
        })
        if (minha !== seq) return
        const linhas = data.data || []
        this.itens = reset ? linhas : [...this.itens, ...linhas]
        this.total = data.meta?.total ?? this.itens.length
        this.temMais = this.pagina < (data.meta?.last_page ?? this.pagina)
        this.pagina++
      } finally {
        if (minha === seq) this.carregando = false
      }
    },

    // linha da listagem com o que voltou do detalhe (estornado, editado)
    atualizarLinha(pag) {
      const i = this.itens.findIndex((l) => l.codpagamento === pag.codpagamento)
      if (i >= 0) {
        this.itens.splice(i, 1, { ...this.itens[i], ...pag })
      }
    },

    async carregar(id) {
      const { data } = await api.get(`${this.endpoint}/${id}`, { params: this.fixos })
      this.pagamento = data.data
      return this.pagamento
    },

    // cancelar: o pagamento não aconteceu como foi lançado; as baixas são estornadas junto
    async cancelar(id, justificativa) {
      const { data } = await api.post(`${this.endpoint}/${id}/cancelar`, {
        ...this.fixos,
        justificativa,
      })
      this.pagamento = data.data
      this.atualizarLinha(data.data)
      return this.pagamento
    },

    // o "Referente a" (sai no recibo), sozinho, em qualquer pagamento efetivado (contas e PDV)
    async atualizarReferente(id, referente) {
      const { data } = await api.put(`${this.endpoint}/${id}/referente`, {
        ...this.fixos,
        referente: referente || null,
      })
      this.pagamento = data.data
      this.atualizarLinha(data.data)
      return this.pagamento
    },

    // o lápis: pessoa e observação; a data só do pagamento manual (só o contas)
    async atualizar(id, payload) {
      const { data } = await api.put(`${this.endpoint}/${id}`, payload)
      this.pagamento = data.data
      this.atualizarLinha(data.data)
      return this.pagamento
    },
  },
})
