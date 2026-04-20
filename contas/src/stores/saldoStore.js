import { defineStore } from 'pinia'
import { ref } from 'vue'
import moment from 'moment'
import { api } from 'src/services/api'
import { notifyError } from 'src/utils/notify'

export const useSaldoStore = defineStore('saldo', () => {
  const dataSelecionada = ref(moment().format('DD-MM-YYYY'))
  const intervalo = ref(null)
  const filiais = ref([])
  const totalPorBanco = ref([])
  const totalGeral = ref(0)
  const isLoading = ref(false)
  const buscandoIntervalo = ref(false)

  async function buscaIntervalo() {
    buscandoIntervalo.value = true
    try {
      const { data } = await api.get('v1/portador/intervalo-saldos')
      intervalo.value = data
    } catch (error) {
      notifyError(error, 'Erro ao buscar intervalo de saldos')
    } finally {
      buscandoIntervalo.value = false
    }
  }

  async function listaSaldos() {
    if (isLoading.value) return
    if (!dataSelecionada.value) return
    isLoading.value = true
    try {
      const { data } = await api.get('v1/portador/lista-saldos', {
        params: { dia: dataSelecionada.value },
      })
      filiais.value = data.data.filiais
      totalPorBanco.value = data.data.totalPorBanco
      totalGeral.value = data.data.totalGeral
    } catch (error) {
      notifyError(error, 'Erro ao buscar saldos')
    } finally {
      isLoading.value = false
    }
  }

  return {
    dataSelecionada,
    intervalo,
    filiais,
    totalPorBanco,
    totalGeral,
    isLoading,
    buscandoIntervalo,
    buscaIntervalo,
    listaSaldos,
  }
})
