import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'
import { Dialog } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Store do DOMÍNIO classificação: os parâmetros de classificação POR CULTURA.
// Cada parâmetro carrega a fórmula inteira (metodo/reduzbase/ordem/tolerância/
// fator/deságio) — não há camada de tabela. CRUD explícito, sem motor genérico
// (KISS). O cálculo do desconto vive em utils/desconto.js (offline) e no
// CargaService (autoridade).
export const useClassificacaoStore = defineStore('classificacao', () => {
  const parametros = ref([])
  const formParametro = ref({})
  const dialogParametro = ref(false)
  const salvandoParametro = ref(false)

  // `codcultura` opcional: a tela da cultura filtra, a listagem geral não.
  async function carregarParametros(codcultura = null) {
    try {
      const params = { sort: 'codcultura,ordem' }
      if (codcultura) params.codcultura = codcultura
      const { data } = await api.get('v1/parametro-classificacao', { params })
      parametros.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }

  // Parâmetro novo entra no fim da cascata e NORMALIZADO — a fórmula da norma.
  // FATOR é a taxa comercial por ponto e só se escolhe de propósito.
  function novoParametro(codcultura) {
    const ultima = parametros.value
      .filter((p) => p.codcultura === codcultura)
      .reduce((max, p) => Math.max(max, Number(p.ordem) || 0), 0)
    formParametro.value = {
      codcultura,
      metodo: 'NORMALIZADO',
      reduzbase: false,
      ordem: ultima + 1,
      tolerancia: 0,
      fator: 0,
      desagio: 0,
    }
    dialogParametro.value = true
  }

  function editarParametro(p) {
    formParametro.value = { ...p }
    dialogParametro.value = true
  }

  async function salvarParametro() {
    if (salvandoParametro.value) return
    salvandoParametro.value = true
    try {
      const f = formParametro.value
      const payload = {
        codcultura: f.codcultura,
        parametroclassificacao: f.parametroclassificacao,
        metodo: f.metodo,
        reduzbase: !!f.reduzbase,
        ordem: Number(f.ordem) || 0,
        tolerancia: Number(f.tolerancia) || 0,
        fator: Number(f.fator) || 0,
        desagio: Number(f.desagio) || 0,
      }
      if (f.codparametroclassificacao) {
        await api.put(`v1/parametro-classificacao/${f.codparametroclassificacao}`, payload)
      } else {
        await api.post('v1/parametro-classificacao', payload)
      }
      notifySuccess('Parâmetro salvo!')
      dialogParametro.value = false
      await carregarParametros(f.codcultura)
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoParametro.value = false
    }
  }

  async function inativarParametro(p) {
    try {
      if (p.inativo)
        await api.delete(`v1/parametro-classificacao/${p.codparametroclassificacao}/inativo`)
      else await api.post(`v1/parametro-classificacao/${p.codparametroclassificacao}/inativo`)
      await carregarParametros(p.codcultura)
    } catch (e) {
      notifyError(e)
    }
  }

  function excluirParametro(p) {
    Dialog.create({
      title: 'Excluir',
      message: `Excluir o parâmetro "${p.parametroclassificacao}"?`,
      cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
      ok: { label: 'Excluir', color: 'red-5', flat: true },
    }).onOk(async () => {
      try {
        await api.delete(`v1/parametro-classificacao/${p.codparametroclassificacao}`)
        notifySuccess('Excluído!')
        await carregarParametros(p.codcultura)
      } catch (e) {
        notifyError(e)
      }
    })
  }

  return {
    parametros,
    formParametro,
    dialogParametro,
    salvandoParametro,
    carregarParametros,
    novoParametro,
    editarParametro,
    salvarParametro,
    inativarParametro,
    excluirParametro,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useClassificacaoStore, import.meta.hot))
}
