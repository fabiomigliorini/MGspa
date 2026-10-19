import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { formataData, formataMesAno } from '@components/formatters'
import { paraDate, inicioMes, fimMes } from '@components/utils/datas'
import { api } from 'src/services/api'
import { notifyError, notifySuccess } from 'src/utils/notify'

export const useExtratoStore = defineStore('extrato', () => {
  const codportador = ref(null)
  const ano = ref(null)
  const mes = ref(null)
  const diaSelecionado = ref(null)

  const portador = ref(null)
  const intervalo = ref(null)
  const saldos = ref([])
  const saldoAnterior = ref(null)
  const extratos = ref([])

  const buscandoPortador = ref(false)
  const buscandoIntervalo = ref(false)
  const buscandoSaldos = ref(false)
  const buscandoExtratos = ref(false)
  const buscandoApi = ref(false)

  const isLoading = computed(() => buscandoSaldos.value || buscandoExtratos.value)

  const diasDoMes = computed(() => {
    const dias = new Set()
    extratos.value.forEach((e) => dias.add(formataData(e.transacao, 0).slice(0, 2)))
    return Array.from(dias).sort()
  })

  const anosDisponiveis = computed(() => {
    if (!intervalo.value?.primeira_data) return []
    const inicio = paraDate(intervalo.value.primeira_data).getFullYear()
    const fim = paraDate(intervalo.value.ultima_data).getFullYear()
    const arr = []
    for (let a = inicio; a <= fim; a++) arr.push(String(a))
    return arr
  })

  const mesesDisponiveis = computed(() => {
    if (!intervalo.value || !ano.value) return []
    // 'YYYY-MM-DD': comparar o texto é comparar a data
    const inicio = inicioMes(intervalo.value.primeira_data)
    const fim = fimMes(intervalo.value.ultima_data)
    const out = []
    for (let m = 1; m <= 12; m++) {
      const mm = String(m).padStart(2, '0')
      const ref = `${ano.value}-${mm}-01`
      if (ref >= inicio && ref <= fim) {
        out.push({ value: mm, label: formataMesAno(ref, 0).toUpperCase() })
      }
    }
    return out
  })

  const linhas = computed(() => {
    const out = []
    if (saldoAnterior.value) {
      out.push({
        _key: `sa-${saldoAnterior.value.dia}`,
        dia: saldoAnterior.value.dia,
        observacoes: 'SALDO ANTERIOR',
        numero: '',
        valor: null,
        saldo: saldoAnterior.value.saldobancario,
        tipo: 'saldo',
      })
    }
    let diaAtual = null
    for (const e of extratos.value) {
      const dia = new Date(e.transacao)
      dia.setHours(0, 0, 0, 0)
      if (diaAtual !== null && diaAtual.getTime() !== dia.getTime()) {
        const saldoDia = saldos.value.find((s) => {
          const sd = new Date(s.dia)
          sd.setHours(0, 0, 0, 0)
          return sd.getTime() === diaAtual.getTime()
        })
        if (saldoDia) {
          out.push({
            _key: `s-${saldoDia.dia}`,
            dia: saldoDia.dia,
            observacoes: 'SALDO',
            numero: '',
            valor: null,
            saldo: saldoDia.saldobancario,
            tipo: 'saldo',
          })
        }
      }
      out.push({
        _key: `e-${e.codextratobancario}`,
        dia: e.transacao,
        observacoes: e.observacoes,
        numero: e.numero,
        valor: e.valor,
        saldo: undefined,
        tipo: 'extrato',
      })
      diaAtual = dia
    }
    const ultimo = saldos.value[saldos.value.length - 1]
    if (ultimo) {
      out.push({
        _key: `s-final-${ultimo.dia}`,
        dia: ultimo.dia,
        observacoes: 'SALDO',
        numero: '',
        valor: null,
        saldo: ultimo.saldobancario,
        tipo: 'saldo',
      })
    }
    return out
  })

  async function buscaPortador() {
    if (!codportador.value) return
    buscandoPortador.value = true
    try {
      const { data } = await api.get(`v1/portador/${codportador.value}/info`)
      portador.value = data
    } catch (error) {
      notifyError(error, 'Erro ao buscar portador')
    } finally {
      buscandoPortador.value = false
    }
  }

  async function buscaIntervalo() {
    buscandoIntervalo.value = true
    try {
      const { data } = await api.get('v1/portador/intervalo-saldos')
      intervalo.value = data
    } catch (error) {
      notifyError(error, 'Erro ao buscar intervalo')
    } finally {
      buscandoIntervalo.value = false
    }
  }

  async function buscaSaldos() {
    if (!codportador.value || !mes.value || !ano.value) return
    buscandoSaldos.value = true
    try {
      const { data } = await api.get(`v1/portador/${codportador.value}/saldos-portador`, {
        params: { mes: mes.value, ano: ano.value },
      })
      saldos.value = data.saldos
      saldoAnterior.value = data.saldoAnterior
    } catch (error) {
      notifyError(error, 'Erro ao buscar saldos')
    } finally {
      buscandoSaldos.value = false
    }
  }

  async function buscaExtratos() {
    if (!codportador.value || !mes.value || !ano.value) return
    buscandoExtratos.value = true
    try {
      const { data } = await api.get(`v1/portador/${codportador.value}/extratos`, {
        params: { mes: mes.value, ano: ano.value },
      })
      extratos.value = data
      if (diasDoMes.value.length > 0 && !diasDoMes.value.includes(diaSelecionado.value)) {
        diaSelecionado.value = diasDoMes.value[0]
      }
    } catch (error) {
      notifyError(error, 'Erro ao buscar extratos')
    } finally {
      buscandoExtratos.value = false
    }
  }

  async function carregar() {
    await Promise.all([buscaSaldos(), buscaExtratos()])
  }

  async function consultarApiBB() {
    if (!codportador.value || !mes.value || !ano.value) return
    buscandoApi.value = true
    try {
      const { data } = await api.get(`v1/portador/${codportador.value}/consulta-extrato`, {
        params: { mes: mes.value, ano: ano.value },
      })
      notifySuccess(`Importados ${data.registros} registros com ${data.falhas} falhas`)
      await carregar()
    } catch (error) {
      notifyError(error, 'Erro ao consultar API')
    } finally {
      buscandoApi.value = false
    }
  }

  return {
    codportador,
    ano,
    mes,
    diaSelecionado,
    portador,
    intervalo,
    saldos,
    saldoAnterior,
    extratos,
    buscandoPortador,
    buscandoIntervalo,
    isLoading,
    buscandoApi,
    diasDoMes,
    anosDisponiveis,
    mesesDisponiveis,
    linhas,
    buscaPortador,
    buscaIntervalo,
    buscaSaldos,
    buscaExtratos,
    carregar,
    consultarApiBB,
  }
})
