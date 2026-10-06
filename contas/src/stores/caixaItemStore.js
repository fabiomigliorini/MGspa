// Itens do caixa (doc-4, "Itens do caixa"): o que se controla no portador em espécie além do
// dinheiro (chips, ingressos), que conta como cédula. Cadastro mínimo: o nome.
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

const vazio = () => ({
  codcaixaitem: null,
  item: '',
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

  async function carregar(codcaixaitem) {
    loading.value = true
    try {
      const [i, s, t] = await Promise.all([
        api.get(`v1/caixa-item/${codcaixaitem}`),
        api.get(`v1/caixa-item/${codcaixaitem}/saldo`),
        api.get(`v1/caixa-item/${codcaixaitem}/tipo`),
      ])
      item.value = i.data.data
      saldos.value = s.data.data
      tipos.value = t.data.data
    } catch (e) {
      item.value = null
      saldos.value = []
      tipos.value = []
      notifyError(e, 'Erro ao carregar o item')
    } finally {
      loading.value = false
    }
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
    }
    dialog.value = true
  }

  async function salvar() {
    salvando.value = true
    try {
      const { data } = isNovo.value
        ? await api.post('v1/caixa-item', model.value)
        : await api.put(`v1/caixa-item/${model.value.codcaixaitem}`, model.value)
      upsertLocal(data.data)
      notifySuccess(isNovo.value ? 'Item criado' : 'Item atualizado')
      dialog.value = false
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
    fetchItems,
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
