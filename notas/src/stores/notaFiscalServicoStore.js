import { defineStore } from 'pinia'
import { Notify } from 'quasar'
import notaFiscalServicoService from '../services/notaFiscalServicoService'
import { hoje } from '@components/utils/datas'

// Nota fiscal de servico (NFS-e Nacional, TASK-279). Uma store para o dominio inteiro:
// a lista, a nota aberta no detalhe e o formulario de criar/editar.

const filtrosVazios = () => ({
  codfilial: null,
  codpessoa: null,
  status: null,
  emissao_inicio: null,
  emissao_fim: null,
  numero: null,
  nfsenumero: null,
})

function mensagemErro(error, fallback) {
  const errors = error?.response?.data?.errors
  if (errors) return Object.values(errors).flat().join('\n')
  return error?.response?.data?.message || error?.message || fallback
}

export const useNotaFiscalServicoStore = defineStore('notaFiscalServico', {
  persist: {
    pick: ['filters'],
  },

  state: () => ({
    // Lista
    notas: [],
    pagination: {
      page: 1,
      perPage: 50,
      hasMore: true,
      loading: false,
      total: 0,
    },
    filters: filtrosVazios(),
    initialLoadDone: false,

    // Nota aberta no detalhe
    nota: null,
    loadingNota: false,

    // Filiais que emitem NFS-e (GET /nota-fiscal-servico/filiais)
    filiais: [],

    // Formulario de criar/editar
    form: null,
    salvando: false,
  }),

  getters: {
    hasActiveFilters: (state) =>
      Object.values(state.filters).some((value) => value !== null && value !== ''),

    activeFiltersCount: (state) =>
      Object.values(state.filters).filter((value) => value !== null && value !== '').length,

    // So DIG e ERR podem ser alteradas: AUT e CAN ja sao documento fiscal
    notaBloqueada: (state) => !!state.nota && ['AUT', 'CAN'].includes(state.nota.status),

    isNovo: (state) => !state.form?.codnotafiscalservico,
  },

  actions: {
    // ==================== LISTA ====================

    async fetchNotas(reset = false) {
      if (this.pagination.loading || (!reset && !this.pagination.hasMore)) return

      if (reset) {
        this.notas = []
        this.pagination.page = 1
        this.pagination.hasMore = true
      }

      this.pagination.loading = true
      try {
        const response = await notaFiscalServicoService.list({
          ...this.filters,
          page: this.pagination.page,
          per_page: this.pagination.perPage,
        })
        const novas = response.data || []
        if (reset) {
          this.notas = novas
        } else {
          this.notas.push(...novas)
        }
        this.pagination.total = response.meta?.total ?? this.notas.length
        this.pagination.hasMore = response.meta
          ? response.meta.current_page < response.meta.last_page
          : false
        if (this.pagination.hasMore) this.pagination.page++
        this.initialLoadDone = true
        return response
      } finally {
        this.pagination.loading = false
      }
    },

    setFilters(filters) {
      Object.assign(this.filters, filters)
      this.initialLoadDone = false
    },

    clearFilters() {
      Object.assign(this.filters, filtrosVazios())
      this.initialLoadDone = false
    },

    // ==================== NOTA ABERTA ====================

    async fetchNota(codnotafiscalservico, force = false) {
      const cod = parseInt(codnotafiscalservico)
      if (!force && this.nota?.codnotafiscalservico === cod) return this.nota

      this.loadingNota = true
      try {
        const response = await notaFiscalServicoService.get(cod)
        this.setNota(response.data)
        return this.nota
      } finally {
        this.loadingNota = false
      }
    },

    // Recebe a nota ja carregada (payload das acoes) e sincroniza com a lista
    setNota(nota) {
      if (!nota) return
      this.nota = nota
      const index = this.notas.findIndex(
        (n) => n.codnotafiscalservico === nota.codnotafiscalservico,
      )
      if (index !== -1) {
        this.notas[index] = { ...nota }
      } else {
        this.notas.unshift({ ...nota })
        this.pagination.total++
      }
    },

    removerDaLista(codnotafiscalservico) {
      const index = this.notas.findIndex((n) => n.codnotafiscalservico === codnotafiscalservico)
      if (index !== -1) {
        this.notas.splice(index, 1)
        this.pagination.total--
      }
      if (this.nota?.codnotafiscalservico === codnotafiscalservico) this.nota = null
    },

    async excluirNota(codnotafiscalservico) {
      try {
        await notaFiscalServicoService.delete(codnotafiscalservico)
        this.removerDaLista(parseInt(codnotafiscalservico))
        Notify.create({ color: 'green-5', icon: 'done', message: 'Nota de serviço excluída' })
      } catch (error) {
        Notify.create({
          color: 'red-5',
          icon: 'error',
          message: 'Erro ao excluir a nota de serviço',
          caption: mensagemErro(error),
        })
        throw error
      }
    },

    // ==================== FILIAIS ====================

    async carregarFiliais() {
      if (this.filiais.length) return this.filiais
      this.filiais = await notaFiscalServicoService.filiais()
      return this.filiais
    },

    filialPorCodigo(codfilial) {
      return this.filiais.find((f) => f.codfilial === codfilial) || null
    },

    // ==================== FORMULARIO ====================

    async novaNota() {
      await this.carregarFiliais()
      // Uma filial so emitindo NFS-e: ja vem escolhida
      const filial = this.filiais.length === 1 ? this.filiais[0] : null
      this.form = {
        codnotafiscalservico: null,
        codfilial: filial?.codfilial ?? null,
        codpessoa: null,
        competencia: hoje(),
        codtributacaonacional: null,
        codnbs: null,
        descricao: null,
        valorservico: null,
        valordescontoincondicionado: null,
        valordescontocondicionado: null,
        // Aliquota efetiva do ISS no Simples: muda todo mes, por isso vem vazia
        issaliquota: null,
        issretido: false,
        tributospercentual: null,
        codcidadeprestacao: filial?.codcidade ?? null,
        observacoes: null,
      }
    },

    async editarNota(codnotafiscalservico) {
      await this.carregarFiliais()
      const nota = await this.fetchNota(codnotafiscalservico)
      this.form = {
        codnotafiscalservico: nota.codnotafiscalservico,
        codfilial: nota.codfilial,
        codpessoa: nota.codpessoa,
        competencia: nota.competencia ? String(nota.competencia).substring(0, 10) : hoje(),
        codtributacaonacional: nota.codtributacaonacional,
        codnbs: nota.codnbs,
        descricao: nota.descricao,
        valorservico: nota.valorservico,
        valordescontoincondicionado: nota.valordescontoincondicionado,
        valordescontocondicionado: nota.valordescontocondicionado,
        issaliquota: nota.issaliquota,
        issretido: !!nota.issretido,
        tributospercentual: nota.tributospercentual,
        codcidadeprestacao: nota.codcidadeprestacao,
        observacoes: nota.observacoes,
      }
    },

    async salvarNota() {
      if (this.salvando) return null
      this.salvando = true
      const { codnotafiscalservico, ...dados } = this.form
      try {
        const response = codnotafiscalservico
          ? await notaFiscalServicoService.update(codnotafiscalservico, dados)
          : await notaFiscalServicoService.create(dados)
        this.setNota(response.data)
        Notify.create({
          color: 'green-5',
          icon: 'done',
          message: codnotafiscalservico ? 'Nota de serviço alterada' : 'Nota de serviço criada',
        })
        return response.data
      } catch (error) {
        Notify.create({
          color: 'red-5',
          icon: 'error',
          message: `Erro ao ${codnotafiscalservico ? 'alterar' : 'criar'} a nota de serviço`,
          caption: mensagemErro(error),
          multiLine: true,
          timeout: 5000,
        })
        throw error
      } finally {
        this.salvando = false
      }
    },
  },
})
