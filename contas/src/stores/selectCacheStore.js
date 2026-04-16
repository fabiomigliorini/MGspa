import { defineStore } from 'pinia'
import { reactive } from 'vue'
import { api } from 'src/services/api'

const listEntity = () => ({ items: [], loaded: false, loading: null })
const byIdEntity = () => ({ byId: {} })

export const useSelectCacheStore = defineStore('selectCache', () => {
  const filial = reactive(listEntity())
  const tipoMovimentoTitulo = reactive(listEntity())
  const formaPagamento = reactive(listEntity())
  const portador = reactive(listEntity())
  const grupoCliente = reactive(listEntity())
  const tipoTitulo = reactive(listEntity())

  const banco = reactive(byIdEntity())
  const contaContabil = reactive(byIdEntity())

  const entities = {
    filial,
    tipoMovimentoTitulo,
    formaPagamento,
    portador,
    grupoCliente,
    tipoTitulo,
    banco,
    contaContabil,
  }

  async function loadList(entity, endpoint, extract = (data) => data.data || data) {
    const s = entities[entity]
    if (s.loaded) return s.items
    if (s.loading) return s.loading
    s.loading = api
      .get(endpoint)
      .then((ret) => {
        s.items = extract(ret.data) || []
        s.loaded = true
        s.loading = null
        return s.items
      })
      .catch((err) => {
        s.loading = null
        throw err
      })
    return s.loading
  }

  async function loadById(entity, endpoint, idField, id) {
    const s = entities[entity]
    if (s.byId[id]) return s.byId[id]
    const ret = await api.get(endpoint, { params: { [idField]: id } })
    const rows = Array.isArray(ret.data) ? ret.data : ret.data.data || []
    rows.forEach((r) => {
      s.byId[r[idField]] = r
    })
    return s.byId[id] || null
  }

  function mergeById(entity, records, idField) {
    const s = entities[entity]
    records.forEach((r) => {
      s.byId[r[idField]] = r
    })
  }

  function invalidate(entity) {
    const s = entities[entity]
    if ('items' in s) {
      s.items = []
      s.loaded = false
      s.loading = null
    }
    if ('byId' in s) {
      s.byId = {}
    }
  }

  return {
    filial,
    tipoMovimentoTitulo,
    formaPagamento,
    portador,
    grupoCliente,
    tipoTitulo,
    banco,
    contaContabil,
    loadList,
    loadById,
    mergeById,
    invalidate,
  }
})
