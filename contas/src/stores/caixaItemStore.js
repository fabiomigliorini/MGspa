// Itens do caixa (M13 doc-3): chips, ingressos e maquinetas de parceiros que passam pela gaveta.
// Pessoa e conta contábil formam o título de repasse no fechamento do caixa.
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

const vazio = () => ({
  codcaixaitem: null,
  item: '',
  modo: 'M',
  codfilial: null,
  codpessoa: null,
  codcontacontabil: null,
  ordem: 0,
})

export const useCaixaItemStore = defineStore('caixaItem', () => {
  const items = ref([])
  const loading = ref(false)
  const salvando = ref(false)
  const dialog = ref(false)
  const model = ref(vazio())
  const registro = ref(null)
  const isNovo = computed(() => !model.value.codcaixaitem)

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

  function upsertLocal(item) {
    const idx = items.value.findIndex((i) => i.codcaixaitem === item.codcaixaitem)
    if (idx >= 0) items.value.splice(idx, 1, item)
    else items.value.push(item)
  }

  function abrirNovo() {
    model.value = vazio()
    registro.value = null
    dialog.value = true
  }

  function abrirEditar(row) {
    model.value = {
      codcaixaitem: row.codcaixaitem,
      item: row.item,
      modo: row.modo,
      codfilial: row.codfilial,
      codpessoa: row.codpessoa,
      codcontacontabil: row.codcontacontabil,
      ordem: row.ordem,
    }
    registro.value = row
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
    } catch (e) {
      notifyError(e, 'Erro ao excluir')
    }
  }

  return {
    items,
    loading,
    salvando,
    dialog,
    model,
    registro,
    isNovo,
    fetchItems,
    abrirNovo,
    abrirEditar,
    salvar,
    alternarInativo,
    excluir,
  }
})
