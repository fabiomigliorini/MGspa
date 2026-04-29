import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { notifyError } from 'src/utils/notify'

export const useBoletoStore = defineStore('boleto', () => {
  // --- Abertos ---
  const abertosResumo = ref([])
  const abertosLista = ref([])
  const abertosTipo = ref('vencidos')
  const carregandoResumoAbertos = ref(false)
  const carregandoAbertos = ref(false)

  async function carregarResumoAbertos() {
    carregandoResumoAbertos.value = true
    try {
      const { data } = await api.get('v1/titulo/boleto/abertos/resumo')
      abertosResumo.value = data
    } catch (error) {
      notifyError(error, 'Erro ao carregar resumo de abertos')
    } finally {
      carregandoResumoAbertos.value = false
    }
  }

  async function carregarAbertos(tipo) {
    abertosTipo.value = tipo
    carregandoAbertos.value = true
    try {
      const { data } = await api.get('v1/titulo/boleto/abertos', { params: { tipo } })
      abertosLista.value = data
    } catch (error) {
      notifyError(error, 'Erro ao carregar boletos abertos')
    } finally {
      carregandoAbertos.value = false
    }
  }

  // --- Liquidados ---
  const liqAnos = ref([])
  const liqMeses = ref([])
  const liqDias = ref([])
  const liqPortadores = ref([])
  const liqLista = ref([])
  const carregandoLiq = ref(false)

  async function carregarLiqNavegacao({ ano, mes, dia, codportador } = {}) {
    carregandoLiq.value = true
    try {
      const { data } = await api.get('v1/titulo/boleto/liquidados/navegacao', {
        params: { ano, mes, dia, codportador },
      })
      liqAnos.value = data.anos || []
      liqMeses.value = data.meses || []
      liqDias.value = data.dias || []
      liqPortadores.value = data.portadores || []
      liqLista.value = data.lista || []
      return data
    } catch (error) {
      notifyError(error, 'Erro ao carregar liquidados')
      return null
    } finally {
      carregandoLiq.value = false
    }
  }

  // --- Baixados ---
  const baixadosLista = ref([])
  const baixadosFiltros = ref({ codportador: null, tipobaixa: null })
  const carregandoBaixados = ref(false)

  async function carregarBaixados() {
    carregandoBaixados.value = true
    try {
      const params = {}
      if (baixadosFiltros.value.codportador) params.codportador = baixadosFiltros.value.codportador
      if (baixadosFiltros.value.tipobaixa) params.tipobaixa = baixadosFiltros.value.tipobaixa
      const { data } = await api.get('v1/titulo/boleto/baixados', { params })
      baixadosLista.value = data
    } catch (error) {
      notifyError(error, 'Erro ao carregar baixados')
    } finally {
      carregandoBaixados.value = false
    }
  }

  return {
    abertosResumo,
    abertosLista,
    abertosTipo,
    carregandoResumoAbertos,
    carregandoAbertos,
    carregarResumoAbertos,
    carregarAbertos,

    liqAnos,
    liqMeses,
    liqDias,
    liqPortadores,
    liqLista,
    carregandoLiq,
    carregarLiqNavegacao,

    baixadosLista,
    baixadosFiltros,
    carregandoBaixados,
    carregarBaixados,
  }
})
