import { defineStore } from 'pinia'
import { api } from 'boot/axios'

export const rhStore = defineStore('rh', {
  state: () => ({
    periodos: [],
    dashboard: {},
    colaboradores: [],
    indicadores: [],
    rubricas: [],
    resumo: {},
    setores: [],
  }),

  actions: {
    // --- PERÍODOS ---

    async getPeriodos() {
      const ret = await api.get('v1/rh/periodo')
      this.periodos = ret.data.data
      return ret
    },

    async criarPeriodo(model) {
      const ret = await api.post('v1/rh/periodo', model)
      return ret
    },

    async duplicarPeriodo(codperiodo) {
      const ret = await api.post('v1/rh/periodo/' + codperiodo + '/duplicar')
      return ret
    },

    async importarEstruturaPeriodo(codperiodo) {
      const ret = await api.post('v1/rh/periodo/' + codperiodo + '/importar-estrutura')
      return ret
    },

    async fecharPeriodo(codperiodo) {
      const ret = await api.post('v1/rh/periodo/' + codperiodo + '/fechar')
      return ret
    },

    async reabrirPeriodo(codperiodo) {
      const ret = await api.post('v1/rh/periodo/' + codperiodo + '/reabrir')
      return ret
    },

    async excluirPeriodo(codperiodo) {
      const ret = await api.delete('v1/rh/periodo/' + codperiodo)
      return ret
    },

    async atualizarPeriodo(codperiodo, data) {
      const ret = await api.put('v1/rh/periodo/' + codperiodo, data)
      return ret
    },

    // --- DASHBOARD ---

    async getDashboard(codperiodo) {
      const ret = await api.get('v1/rh/dashboard/' + codperiodo)
      this.dashboard = ret.data
      return ret
    },

    // --- COCKPIT (lazy-load) ---

    async getResumo(codperiodo) {
      const ret = await api.get('v1/rh/periodo/' + codperiodo + '/resumo')
      this.resumo = ret.data
      return ret
    },

    async getUnidade(codperiodo, codunidade) {
      const ret = await api.get('v1/rh/periodo/' + codperiodo + '/unidade/' + codunidade)
      return ret.data
    },

    // --- COLABORADORES DO PERÍODO ---

    async getColaboradores(codperiodo) {
      const ret = await api.get('v1/rh/periodo/' + codperiodo + '/colaborador')
      this.colaboradores = ret.data.data
      return ret
    },

    async encerrar(codperiodo, codperiodocolaborador) {
      const ret = await api.post(
        'v1/rh/periodo/' + codperiodo + '/colaborador/' + codperiodocolaborador + '/encerrar',
      )
      return ret
    },

    async estornar(codperiodo, codperiodocolaborador) {
      const ret = await api.post(
        'v1/rh/periodo/' + codperiodo + '/colaborador/' + codperiodocolaborador + '/estornar',
      )
      return ret
    },

    async recalcular(codperiodo, codperiodocolaborador) {
      const ret = await api.post(
        'v1/rh/periodo/' + codperiodo + '/colaborador/' + codperiodocolaborador + '/recalcular',
      )
      return ret
    },

    async getColaboradoresDisponiveis(codperiodo) {
      const ret = await api.get('v1/rh/periodo/' + codperiodo + '/colaborador/disponiveis')
      return ret.data.data
    },

    async adicionarColaboradores(codperiodo, colaboradores, codsetor = null) {
      const ret = await api.post('v1/rh/periodo/' + codperiodo + '/colaborador', {
        colaboradores,
        codsetor,
      })
      return ret
    },

    // --- SETORES (catálogo p/ select) ---

    async getSetores() {
      const ret = await api.get('v1/rh/setores')
      this.setores = ret.data.data
      return ret.data.data
    },

    async atualizarSetorColaborador(codperiodo, codperiodocolaborador, codsetor) {
      const ret = await api.patch(
        'v1/rh/periodo/' + codperiodo + '/colaborador/' + codperiodocolaborador + '/setor',
        { codsetor },
      )
      return ret
    },

    // --- UNIDADES DE NEGÓCIO (cadastro global) ---

    async criarUnidade(data) {
      const ret = await api.post('v1/unidade-negocio', data)
      return ret
    },

    async atualizarUnidade(codunidadenegocio, data) {
      const ret = await api.put('v1/unidade-negocio/' + codunidadenegocio, data)
      return ret
    },

    async excluirUnidade(codunidadenegocio) {
      const ret = await api.delete('v1/unidade-negocio/' + codunidadenegocio)
      return ret
    },

    async inativarUnidade(codunidadenegocio) {
      const ret = await api.post('v1/unidade-negocio/' + codunidadenegocio + '/inativo')
      return ret
    },

    async ativarUnidade(codunidadenegocio) {
      const ret = await api.delete('v1/unidade-negocio/' + codunidadenegocio + '/inativo')
      return ret
    },

    // --- SETORES (cadastro global) ---

    async criarSetor(data) {
      const ret = await api.post('v1/setor', data)
      return ret
    },

    async atualizarSetor(codsetor, data) {
      const ret = await api.put('v1/setor/' + codsetor, data)
      return ret
    },

    async excluirSetor(codsetor) {
      const ret = await api.delete('v1/setor/' + codsetor)
      return ret
    },

    async inativarSetor(codsetor) {
      const ret = await api.post('v1/setor/' + codsetor + '/inativo')
      return ret
    },

    async ativarSetor(codsetor) {
      const ret = await api.delete('v1/setor/' + codsetor + '/inativo')
      return ret
    },

    async excluirColaborador(codperiodo, codperiodocolaborador) {
      const ret = await api.delete(
        'v1/rh/periodo/' + codperiodo + '/colaborador/' + codperiodocolaborador,
      )
      return ret
    },

    // --- CATÁLOGO DE RUBRICAS (tblrubrica) ---

    async getRubricas() {
      const ret = await api.get('v1/rh/rubrica')
      this.rubricas = ret.data.data
      return ret
    },

    async criarRubrica(data) {
      const ret = await api.post('v1/rh/rubrica', data)
      return ret
    },

    async atualizarRubrica(codrubrica, data) {
      const ret = await api.put('v1/rh/rubrica/' + codrubrica, data)
      return ret
    },

    async inativarRubrica(codrubrica) {
      const ret = await api.post('v1/rh/rubrica/' + codrubrica + '/inativo')
      return ret
    },

    async ativarRubrica(codrubrica) {
      const ret = await api.delete('v1/rh/rubrica/' + codrubrica + '/inativo')
      return ret
    },

    async excluirRubrica(codrubrica) {
      const ret = await api.delete('v1/rh/rubrica/' + codrubrica)
      return ret
    },

    async aplicarRubricaMassa(codperiodo, codrubrica) {
      const ret = await api.post('v1/rh/periodo/' + codperiodo + '/rubrica-massa', { codrubrica })
      return ret
    },

    // --- RUBRICAS DO COLABORADOR (tblcolaboradorrubrica) ---

    async criarColaboradorRubrica(codperiodocolaborador, data) {
      const ret = await api.post(
        'v1/rh/periodo-colaborador/' + codperiodocolaborador + '/colaborador-rubrica',
        data,
      )
      return ret
    },

    async atualizarColaboradorRubrica(codcolaboradorrubrica, data) {
      const ret = await api.put('v1/rh/colaborador-rubrica/' + codcolaboradorrubrica, data)
      return ret
    },

    async excluirColaboradorRubrica(codcolaboradorrubrica) {
      const ret = await api.delete('v1/rh/colaborador-rubrica/' + codcolaboradorrubrica)
      return ret
    },

    async toggleConcedido(codcolaboradorrubrica) {
      const ret = await api.patch(
        'v1/rh/colaborador-rubrica/' + codcolaboradorrubrica + '/concedido',
      )
      return ret
    },

    // --- INDICADORES ---

    async getIndicadores(codperiodo) {
      const ret = await api.get('v1/rh/periodo/' + codperiodo + '/indicador')
      this.indicadores = ret.data.data
      return ret
    },

    async criarIndicador(codperiodo, data) {
      const ret = await api.post('v1/rh/periodo/' + codperiodo + '/indicador', data)
      return ret
    },

    async atualizarMeta(codindicador, data) {
      const ret = await api.put('v1/rh/indicador/' + codindicador + '/meta', data)
      return ret
    },

    async lancamentoManual(codindicador, data) {
      const ret = await api.post('v1/rh/indicador/' + codindicador + '/lancamento', data)
      return ret
    },

    async atualizarLancamento(codindicadorlancamento, data) {
      const ret = await api.put('v1/rh/indicador-lancamento/' + codindicadorlancamento, data)
      return ret
    },

    async excluirLancamento(codindicadorlancamento) {
      const ret = await api.delete('v1/rh/indicador-lancamento/' + codindicadorlancamento)
      return ret
    },

    async excluirIndicador(codindicador) {
      const ret = await api.delete('v1/rh/indicador/' + codindicador)
      return ret
    },

    // --- REPROCESSAMENTO ---

    async reprocessarPeriodo(codperiodo, limpar = false) {
      const ret = await api.post('v1/rh/periodo/' + codperiodo + '/reprocessar', { limpar })
      return ret
    },

    async progressoReprocessamento(codperiodo) {
      const ret = await api.get('v1/rh/periodo/' + codperiodo + '/reprocessar')
      return ret.data
    },

    async cancelarReprocessamento(codperiodo) {
      const ret = await api.delete('v1/rh/periodo/' + codperiodo + '/reprocessar')
      return ret
    },

    async getExtrato(codindicador, page = 1) {
      const ret = await api.get('v1/rh/indicador/' + codindicador + '/lancamento', {
        params: { page },
      })
      return ret.data
    },

    // --- ACERTOS ---

    async getAcertos(codperiodo, dias = 5) {
      const ret = await api.get('v1/rh/periodo/' + codperiodo + '/acertos', {
        params: { dias },
      })
      return ret
    },

    async getTitulosAcerto(codperiodo, codperiodocolaborador, dias = 5) {
      const ret = await api.get(
        'v1/rh/periodo/' + codperiodo + '/acertos/' + codperiodocolaborador + '/titulos',
        { params: { dias } },
      )
      return ret
    },

    async efetivarAcerto(codperiodo, codperiodocolaborador, payload) {
      const ret = await api.post(
        'v1/rh/periodo/' + codperiodo + '/acertos/' + codperiodocolaborador + '/efetivar',
        payload,
      )
      return ret
    },

    async estornarAcerto(codperiodo, codperiodocolaborador) {
      const ret = await api.post(
        'v1/rh/periodo/' + codperiodo + '/acertos/' + codperiodocolaborador + '/estornar',
      )
      return ret
    },

    // --- MEU PAINEL ---

    async getMeuPainelPeriodos() {
      const ret = await api.get('v1/rh/meu-painel/periodos')
      return ret.data.data
    },

    async getMeuPainel(codperiodo) {
      const ret = await api.get('v1/rh/meu-painel/' + codperiodo)
      return ret.data
    },

    async getMeuPainelColaborador(codperiodo, codperiodocolaborador) {
      const ret = await api.get(
        'v1/rh/meu-painel/' + codperiodo + '/colaborador/' + codperiodocolaborador,
      )
      return ret.data
    },

    async toggleGestor(codperiodo, codperiodocolaborador) {
      const ret = await api.patch(
        'v1/rh/periodo/' + codperiodo + '/colaborador/' + codperiodocolaborador + '/gestor',
      )
      return ret.data
    },
  },
})
