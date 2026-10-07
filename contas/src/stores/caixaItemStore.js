// Itens do caixa (doc-4, "Itens do caixa" e "Itens de parceiro"): o que se controla no portador em
// espécie além do dinheiro. Cédula (chips) conta como cédula no caixa; maquineta de parceiro não se
// conta: o caixa lança o borderô e o financeiro paga o parceiro pela conta corrente da maquineta
// (créditos = borderôs; débitos = títulos gerados; ajustes com observação).
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { formataDataIso, formataTimestampIso } from '@components/formatters'
import { notifySuccess, notifyError } from 'src/utils/notify'

export const MODOS = [
  { value: 'C', label: 'Conta como cédula (chips, ingressos)' },
  { value: 'M', label: 'Maquineta de parceiro' },
]

const vazio = () => ({
  codcaixaitem: null,
  item: '',
  modo: 'C',
  codpessoa: null,
  codfilial: null,
  codcontacontabil: null,
})

export const useCaixaItemStore = defineStore('caixaItem', () => {
  const items = ref([])
  const loading = ref(false)
  const salvando = ref(false)
  const dialog = ref(false)
  const model = ref(vazio())
  const isNovo = computed(() => !model.value.codcaixaitem)
  // a tela do item: o item e quanto tem dele em cada caixa que já mexeu com ele (a última contagem
  // de caixa fechado)
  const item = ref(null)
  const saldos = ref([])
  // os tipos do item (descrição + preço distintos) e o dialog que troca a descrição de um em tudo
  const tipos = ref([])
  const tipoDialog = ref(false)
  const tipoModel = ref({ preco: null, descricao: null, nova: '' })
  // o popup dos períodos de um caixa em que o item mexeu (abertura, entradas, fechamento e
  // diferença), do mais novo para o mais antigo (rolagem infinita)
  const fechamentosDialog = ref(false)
  const fechamentosCaixa = ref(null)
  const fechamentos = ref([])
  const fechamentosPagina = ref(1)
  const fechamentosTemMais = ref(true)
  // o total dos períodos fechados (todas as páginas), do servidor
  const fechamentosTotais = ref(null)
  // a conta corrente da maquineta de parceiro: o extrato de/até (sem eles, o servidor manda os
  // últimos 60 dias e devolve as datas), o título e o ajuste
  const conta = ref(null)
  const contaDe = ref(null)
  const contaAte = ref(null)
  const tituloDialog = ref(false)
  const tituloModel = ref({})
  const ajusteDialog = ref(false)
  const ajusteModel = ref({})

  async function fetchItems() {
    loading.value = true
    try {
      const { data } = await api.get('v1/caixa-item')
      items.value = data.data
    } catch (e) {
      notifyError(e, 'Erro ao carregar os itens do caixa')
    } finally {
      loading.value = false
    }
  }

  function upsertLocal(registro) {
    const idx = items.value.findIndex((i) => i.codcaixaitem === registro.codcaixaitem)
    if (idx >= 0) items.value.splice(idx, 1, registro)
    else items.value.push(registro)
    if (item.value?.codcaixaitem === registro.codcaixaitem) item.value = registro
  }

  // a maquineta mostra a conta corrente; o item de cédula, os saldos nos caixas e os tipos
  async function carregar(codcaixaitem) {
    loading.value = true
    try {
      const i = await api.get(`v1/caixa-item/${codcaixaitem}`)
      item.value = i.data.data
      saldos.value = []
      tipos.value = []
      conta.value = null
      contaDe.value = null
      contaAte.value = null
      if (item.value.modo === 'M') {
        await carregarConta()
      } else {
        const [s, t] = await Promise.all([
          api.get(`v1/caixa-item/${codcaixaitem}/saldo`),
          api.get(`v1/caixa-item/${codcaixaitem}/tipo`),
        ])
        saldos.value = s.data.data
        tipos.value = t.data.data
      }
    } catch (e) {
      item.value = null
      saldos.value = []
      tipos.value = []
      conta.value = null
      notifyError(e, 'Erro ao carregar o item')
    } finally {
      loading.value = false
    }
  }

  // ==== conta corrente da maquineta de parceiro ====

  // toda rota da conta devolve o extrato de/até já com o que mudou
  async function executarConta(fn, ok) {
    salvando.value = true
    try {
      const { data } = await fn({ de: contaDe.value, ate: contaAte.value })
      conta.value = data.data
      contaDe.value = data.data.de
      contaAte.value = data.data.ate
      if (ok) notifySuccess(ok)
      return true
    } catch (e) {
      notifyError(e)
      return false
    } finally {
      salvando.value = false
    }
  }

  function carregarConta() {
    return executarConta((params) =>
      api.get(`v1/caixa-item/${item.value.codcaixaitem}/conta`, { params }),
    )
  }

  // o valor sugerido é o saldo (o que devemos ao parceiro), vencimento hoje
  function abrirTitulo() {
    const saldo = conta.value?.saldo ?? 0
    tituloModel.value = {
      valor: saldo > 0 ? saldo : null,
      vencimento: formataDataIso(new Date()),
      observacoes: '',
    }
    tituloDialog.value = true
  }

  async function gerarTitulo() {
    const url = `v1/caixa-item/${item.value.codcaixaitem}/conta/titulo`
    if (
      await executarConta((p) => api.post(url, { ...tituloModel.value, ...p }), 'Título gerado')
    ) {
      tituloDialog.value = false
    }
  }

  function abrirAjuste() {
    ajusteModel.value = {
      sinal: null,
      valor: null,
      observacoes: '',
      transacao: formataTimestampIso(new Date()),
    }
    ajusteDialog.value = true
  }

  // sinal 1 aumenta o que devemos ao parceiro, -1 diminui (comissão que ele desconta)
  async function ajustarConta() {
    const { sinal, valor, observacoes, transacao } = ajusteModel.value
    const url = `v1/caixa-item/${item.value.codcaixaitem}/conta/ajuste`
    const corpo = { valor: sinal * valor, observacoes, transacao }
    if (await executarConta((p) => api.post(url, { ...corpo, ...p }), 'Ajuste lançado')) {
      ajusteDialog.value = false
    }
  }

  function cancelarAcerto(codcaixaitemacerto, justificativa) {
    return executarConta(
      (p) =>
        api.post(`v1/caixa-item-acerto/${codcaixaitemacerto}/cancelar`, { justificativa, ...p }),
      'Lançamento cancelado',
    )
  }

  function abrirFechamentos(saldo) {
    fechamentosCaixa.value = saldo
    fechamentos.value = []
    fechamentosPagina.value = 1
    fechamentosTemMais.value = true
    fechamentosTotais.value = null
    fechamentosDialog.value = true
  }

  async function carregarFechamentos() {
    if (!fechamentosTemMais.value) return
    const { codcaixaitem } = item.value
    const { codportador } = fechamentosCaixa.value
    try {
      const { data } = await api.get(`v1/caixa-item/${codcaixaitem}/saldo/${codportador}`, {
        params: { page: fechamentosPagina.value },
      })
      fechamentos.value = [...fechamentos.value, ...data.data]
      fechamentosTemMais.value = data.meta.current_page < data.meta.last_page
      if (data.meta.current_page === 1) fechamentosTotais.value = data.meta.totais
      fechamentosPagina.value++
    } catch (e) {
      fechamentosTemMais.value = false
      notifyError(e, 'Erro ao carregar os fechamentos')
    }
  }

  function abrirTipo(tipo) {
    tipoModel.value = { preco: tipo.preco, descricao: tipo.descricao, nova: tipo.descricao ?? '' }
    tipoDialog.value = true
  }

  async function salvarTipo() {
    salvando.value = true
    try {
      await api.put(`v1/caixa-item/${item.value.codcaixaitem}/tipo`, tipoModel.value)
      notifySuccess('Descrição alterada')
      tipoDialog.value = false
      await carregar(item.value.codcaixaitem)
    } catch (e) {
      notifyError(e, 'Erro ao alterar a descrição')
    } finally {
      salvando.value = false
    }
  }

  function abrirNovo() {
    model.value = vazio()
    dialog.value = true
  }

  function abrirEditar(row) {
    model.value = {
      codcaixaitem: row.codcaixaitem,
      item: row.item,
      modo: row.modo ?? 'C',
      codpessoa: row.codpessoa ?? null,
      codfilial: row.codfilial ?? null,
      codcontacontabil: row.codcontacontabil ?? null,
    }
    dialog.value = true
  }

  async function salvar() {
    const modoAntes = item.value?.codcaixaitem === model.value.codcaixaitem ? item.value.modo : null
    salvando.value = true
    try {
      const { data } = isNovo.value
        ? await api.post('v1/caixa-item', model.value)
        : await api.put(`v1/caixa-item/${model.value.codcaixaitem}`, model.value)
      upsertLocal(data.data)
      notifySuccess(isNovo.value ? 'Item criado' : 'Item atualizado')
      dialog.value = false
      // trocou o modo na tela do item: a conta corrente no lugar dos saldos, ou o contrário
      if (modoAntes && modoAntes !== data.data.modo) await carregar(data.data.codcaixaitem)
    } catch (e) {
      notifyError(e, 'Erro ao salvar o item')
    } finally {
      salvando.value = false
    }
  }

  async function alternarInativo(row) {
    try {
      const { data } = row.inativo
        ? await api.delete(`v1/caixa-item/${row.codcaixaitem}/inativo`)
        : await api.post(`v1/caixa-item/${row.codcaixaitem}/inativo`)
      upsertLocal(data.data)
      notifySuccess(data.data.inativo ? 'Item inativado' : 'Item reativado')
    } catch (e) {
      notifyError(e, 'Erro ao alterar o status')
    }
  }

  async function excluir(row) {
    try {
      await api.delete(`v1/caixa-item/${row.codcaixaitem}`)
      items.value = items.value.filter((i) => i.codcaixaitem !== row.codcaixaitem)
      notifySuccess('Item excluído')
      return true
    } catch (e) {
      notifyError(e, 'Erro ao excluir')
      return false
    }
  }

  return {
    items,
    loading,
    salvando,
    dialog,
    model,
    isNovo,
    item,
    saldos,
    tipos,
    tipoDialog,
    tipoModel,
    fechamentosDialog,
    fechamentosCaixa,
    fechamentos,
    fechamentosTemMais,
    fechamentosTotais,
    conta,
    contaDe,
    contaAte,
    tituloDialog,
    tituloModel,
    ajusteDialog,
    ajusteModel,
    fetchItems,
    carregarConta,
    abrirTitulo,
    gerarTitulo,
    abrirAjuste,
    ajustarConta,
    cancelarAcerto,
    carregar,
    abrirFechamentos,
    abrirTipo,
    salvarTipo,
    carregarFechamentos,
    abrirNovo,
    abrirEditar,
    salvar,
    alternarInativo,
    excluir,
  }
})
