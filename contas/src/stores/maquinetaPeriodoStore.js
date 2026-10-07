import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// A maquineta e seus períodos (TASK-188 M9.8), no padrão do portador e seus períodos (doc-4): o
// gerente confere o cartão de cada período com o borderô da maquineta (aberto → pendente →
// conferido), corrige os lançamentos, divide, unifica e anexa a foto. Toda rota que muda devolve
// a tela inteira (maquineta, abas e o período); o store troca pelo que veio.

export const useMaquinetaPeriodoStore = defineStore('maquinetaPeriodo', () => {
  const maquineta = ref(null)
  // todos os períodos, sem lançamentos (as abas)
  const periodos = ref([])
  // o período da tela, com o sistema por PDV, as fotos e os lançamentos
  const periodo = ref(null)
  const carregando = ref(false)
  const salvando = ref(false)

  function aplicar(data) {
    maquineta.value = data.maquineta
    periodos.value = data.periodos
    periodo.value = data.periodo
  }

  async function carregar(codmaquineta, codmaquinetalote = null) {
    carregando.value = true
    try {
      const url =
        `v1/maquineta/${codmaquineta}/periodo` + (codmaquinetalote ? `/${codmaquinetalote}` : '')
      const { data } = await api.get(url)
      aplicar(data.data)
    } catch (e) {
      notifyError(e, 'Erro ao carregar a maquineta')
    } finally {
      carregando.value = false
    }
  }

  // recarrega o período da tela (depois de corrigir um lançamento)
  const recarregar = () =>
    carregar(maquineta.value.codmaquineta, periodo.value?.codmaquinetalote ?? null)

  // ação do período da tela; devolve o codmaquinetalote que veio (a tela vai para ele)
  async function executar(acao, payload, mensagem) {
    salvando.value = true
    try {
      const { data } = await api.post(
        `v1/maquineta-lote/${periodo.value.codmaquinetalote}/${acao}`,
        payload,
      )
      aplicar(data.data)
      if (mensagem) notifySuccess(typeof mensagem === 'function' ? mensagem() : mensagem)
      return periodo.value.codmaquinetalote
    } catch (e) {
      notifyError(e)
      return null
    } finally {
      salvando.value = false
    }
  }

  // bateu: conferido; não bateu: pendente (corrige e confere de novo)
  const conferir = (payload) =>
    executar('conferir', payload, () =>
      periodo.value.situacao === 'conferido'
        ? 'Conferido: o borderô bateu com o sistema'
        : 'Não bateu: o período ficou pendente',
    )
  const reabrir = () => executar('reabrir', null, 'Período reaberto')
  const editarDatas = (payload) => executar('datas', payload, 'Início e fim alterados')
  const dividir = (corte) => executar('dividir', { corte }, 'Período dividido')
  const unificar = () => executar('unificar', null, 'Períodos unificados')
  const anexarFoto = (anexoBase64) => executar('foto', { anexoBase64 }, 'Foto anexada')

  return {
    maquineta,
    periodos,
    periodo,
    carregando,
    salvando,
    carregar,
    recarregar,
    conferir,
    reabrir,
    editarDatas,
    dividir,
    unificar,
    anexarFoto,
  }
})
