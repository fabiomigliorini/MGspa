// Listagem única de pagamentos (MgPagamentoLista, M6.1 do plano doc-3): venda, título,
// transferência e avulso, com os filtros do plano. O app configura onde busca:
//   contas:   { endpoint: 'v1/pagamento', padrao: { codfilial } }
//   negocios: { endpoint: 'v1/pdv/pagamento', fixos: { pdv: uuid }, travadoPdv: true }
// O PDV fica travado no servidor (v1/pdv/pagamento força o PDV do dispositivo).
import { defineStore } from 'pinia'
import { api } from 'src/services/api'

const iso = (d) =>
  [
    d.getFullYear(),
    String(d.getMonth() + 1).padStart(2, '0'),
    String(d.getDate()).padStart(2, '0'),
  ].join('-')

const filtrosVazios = () => {
  const hoje = new Date()
  return {
    codpagamento: null,
    transacao_de: iso(new Date(hoje.getFullYear(), hoje.getMonth(), 1)),
    transacao_ate: iso(hoje),
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
    // detalhe aberto (página no contas, dialog no PDV)
    pagamento: null,
    dialogDetalhe: false,
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
        this.pagina = 1
        this.temMais = true
      }
      if (!this.temMais || this.carregando) return
      this.carregando = true
      try {
        const { data } = await api.get(this.endpoint, {
          params: { ...this.parametros(), page: this.pagina },
        })
        const linhas = data.data || []
        this.itens = reset ? linhas : [...this.itens, ...linhas]
        this.total = data.meta?.total ?? this.itens.length
        this.temMais = this.pagina < (data.meta?.last_page ?? this.pagina)
        this.pagina++
      } finally {
        this.carregando = false
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

    // desamarrar: estorna as baixas (todas, ou as linhas escolhidas); o pagamento continua
    async desamarrar(id, justificativa, codmovimentos = null) {
      const { data } = await api.post(`${this.endpoint}/${id}/desamarrar`, {
        ...this.fixos,
        justificativa,
        codmovimentos,
      })
      this.pagamento = data.data
      this.atualizarLinha(data.data)
      return this.pagamento
    },

    // cancelar: só o manual já desamarrado (o pagamento não aconteceu)
    async cancelar(id, justificativa) {
      const { data } = await api.post(`${this.endpoint}/${id}/cancelar`, {
        ...this.fixos,
        justificativa,
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
