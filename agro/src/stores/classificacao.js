import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'
import { Dialog } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Store do DOMÍNIO classificação. Dona do catálogo de parâmetros (global) + das
// tabelas de classificação nomeadas (por cultura) + seus itens (valores N:N).
// CRUD explícito, sem motor genérico (KISS). O cálculo do desconto vive em
// utils/desconto.js (offline) e no CargaService (autoridade).
export const useClassificacaoStore = defineStore('classificacao', () => {
  // ======================= CATÁLOGO DE PARÂMETROS (global) =======================
  const parametros = ref([])
  const formParametro = ref({})
  const dialogParametro = ref(false)
  const salvandoParametro = ref(false)

  const parametrosAtivos = () => parametros.value.filter((p) => !p.inativo)

  async function carregarParametros() {
    try {
      const { data } = await api.get('v1/parametro-classificacao', {
        params: { sort: 'parametroclassificacao' },
      })
      parametros.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }
  function novoParametro() {
    formParametro.value = { metodo: 'NORMALIZADO', reduzbase: false }
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
        parametroclassificacao: f.parametroclassificacao,
        metodo: f.metodo,
        reduzbase: !!f.reduzbase,
      }
      if (f.codparametroclassificacao) {
        await api.put(`v1/parametro-classificacao/${f.codparametroclassificacao}`, payload)
      } else {
        await api.post('v1/parametro-classificacao', payload)
      }
      notifySuccess('Parâmetro salvo!')
      dialogParametro.value = false
      await carregarParametros()
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
      await carregarParametros()
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
        await carregarParametros()
      } catch (e) {
        notifyError(e)
      }
    })
  }

  // ======================= TABELAS DE CLASSIFICAÇÃO (por cultura) =======================
  const tabelas = ref([])
  const codPadrao = ref(null) // tblcultura.codtabelaclassificacao (qual é a padrão)
  const formTabela = ref({ itens: [] })
  const dialogTabela = ref(false)
  const salvandoTabela = ref(false)

  async function carregarTabelas(codcultura) {
    try {
      const [t, c] = await Promise.all([
        api.get('v1/tabela-classificacao', { params: { codcultura, sort: 'tabelaclassificacao' } }),
        api.get(`v1/cultura/${codcultura}`),
      ])
      tabelas.value = t.data.data ?? t.data
      codPadrao.value = (c.data.data ?? c.data)?.codtabelaclassificacao ?? null
    } catch (e) {
      notifyError(e)
    }
  }

  // Monta as linhas do form: UMA por parâmetro do catálogo (merge com os itens da
  // tabela). `aplicar` diz se o parâmetro entra na tabela; só os aplicados viram
  // itens no salvar. Numa tabela nova todos começam aplicados.
  function montarItens(tabela, novo) {
    const existentes = new Map(
      (tabela?.TabelaClassificacaoItemS || []).map((i) => [i.codparametroclassificacao, i]),
    )
    return parametrosAtivos()
      .map((p, idx) => {
        const e = existentes.get(p.codparametroclassificacao)
        return {
          codparametroclassificacao: p.codparametroclassificacao,
          parametroclassificacao: p.parametroclassificacao,
          metodo: p.metodo,
          reduzbase: p.reduzbase,
          aplicar: novo ? true : !!e,
          ordem: e?.ordem ?? idx + 1,
          tolerancia: e?.tolerancia ?? 0,
          fator: e?.fator ?? 0,
          desagio: e?.desagio ?? 0,
        }
      })
      .sort((a, b) => (Number(a.ordem) || 0) - (Number(b.ordem) || 0))
  }

  function novaTabela(codcultura) {
    formTabela.value = { codcultura, tabelaclassificacao: '', itens: montarItens(null, true) }
    dialogTabela.value = true
  }
  function editarTabela(t) {
    formTabela.value = {
      codtabelaclassificacao: t.codtabelaclassificacao,
      codcultura: t.codcultura,
      tabelaclassificacao: t.tabelaclassificacao,
      itens: montarItens(t, false),
    }
    dialogTabela.value = true
  }
  async function salvarTabela() {
    if (salvandoTabela.value) return
    salvandoTabela.value = true
    try {
      const f = formTabela.value
      const payload = {
        codcultura: f.codcultura,
        tabelaclassificacao: f.tabelaclassificacao,
        itens: (f.itens || [])
          .filter((i) => i.aplicar)
          .map((i) => ({
            codparametroclassificacao: i.codparametroclassificacao,
            ordem: Number(i.ordem) || 0,
            tolerancia: Number(i.tolerancia) || 0,
            fator: Number(i.fator) || 0,
            desagio: Number(i.desagio) || 0,
          })),
      }
      if (f.codtabelaclassificacao) {
        await api.put(`v1/tabela-classificacao/${f.codtabelaclassificacao}`, payload)
      } else {
        await api.post('v1/tabela-classificacao', payload)
      }
      notifySuccess('Tabela salva!')
      dialogTabela.value = false
      await carregarTabelas(f.codcultura)
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoTabela.value = false
    }
  }
  async function marcarPadrao(t) {
    try {
      await api.post(`v1/tabela-classificacao/${t.codtabelaclassificacao}/padrao`)
      codPadrao.value = t.codtabelaclassificacao
      notifySuccess('Tabela padrão definida!')
    } catch (e) {
      notifyError(e)
    }
  }
  function excluirTabela(t) {
    Dialog.create({
      title: 'Excluir',
      message: `Excluir a tabela "${t.tabelaclassificacao}"?`,
      cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
      ok: { label: 'Excluir', color: 'red-5', flat: true },
    }).onOk(async () => {
      try {
        await api.delete(`v1/tabela-classificacao/${t.codtabelaclassificacao}`)
        notifySuccess('Excluído!')
        await carregarTabelas(t.codcultura)
      } catch (e) {
        notifyError(e)
      }
    })
  }

  return {
    // catálogo
    parametros,
    parametrosAtivos,
    formParametro,
    dialogParametro,
    salvandoParametro,
    carregarParametros,
    novoParametro,
    editarParametro,
    salvarParametro,
    inativarParametro,
    excluirParametro,
    // tabelas
    tabelas,
    codPadrao,
    formTabela,
    dialogTabela,
    salvandoTabela,
    carregarTabelas,
    novaTabela,
    editarTabela,
    salvarTabela,
    marcarPadrao,
    excluirTabela,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useClassificacaoStore, import.meta.hot))
}
