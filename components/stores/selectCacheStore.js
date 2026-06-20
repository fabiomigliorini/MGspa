import { defineStore } from 'pinia'
import { reactive } from 'vue'
import { api } from 'src/services/api'

// Cache compartilhado de TODOS os selects (usado pelos @components/MgSelect*).
// Genérico, keyed pelo nome da entidade. Por entidade guarda:
//  - items/loaded/loading  → lista completa das entidades LOCAL (<100 registros)
//  - byId                  → registros resolvidos por id (LOCAL e REMOTE)
// Contrato backend: GET v1/select/{ent} (lista), v1/select/{ent}/{id} (objeto), ?busca&page (REMOTE).
export const useSelectCacheStore = defineStore('selectCache', () => {
  const entities = reactive({})

  function ent(name) {
    if (!entities[name]) {
      entities[name] = { items: [], loaded: false, loading: null, byId: {} }
    }
    return entities[name]
  }

  // LOCAL: carrega tudo uma vez; cacheia lista + byId. force=true refaz do backend.
  // inativos=true traz tambem os inativos (cache em bucket separado + ?inativos=1).
  async function loadList(name, endpoint, { force = false, inativos = false } = {}) {
    const key = inativos ? `${name}__inativos` : name
    const url = inativos ? `${endpoint}${endpoint.includes('?') ? '&' : '?'}inativos=1` : endpoint
    const s = ent(key)
    if (force) {
      s.loaded = false
      s.loading = null
    }
    if (s.loaded) return s.items
    if (s.loading) return s.loading
    s.loading = api
      .get(url)
      .then((ret) => {
        const rows = Array.isArray(ret.data) ? ret.data : ret.data?.data || []
        s.items = rows
        rows.forEach((r) => {
          if (r.value != null) s.byId[r.value] = r
        })
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

  // Resolve um registro pelo id (path param) → objeto único. Usa cache se já tem.
  async function loadById(name, endpoint, id) {
    if (id == null) return null
    const s = ent(name)
    if (s.byId[id]) return s.byId[id]
    const ret = await api.get(`${endpoint}/${id}`)
    const row = Array.isArray(ret.data) ? ret.data[0] : ret.data?.data || ret.data
    if (row && row.value != null) s.byId[row.value] = row
    return row || null
  }

  // Cacheia resultados de busca por id (REMOTE).
  function mergeById(name, rows) {
    const s = ent(name)
    rows.forEach((r) => {
      if (r && r.value != null) s.byId[r.value] = r
    })
  }

  function getById(name, id) {
    return ent(name).byId[id] || null
  }

  function invalidate(name) {
    ;[name, `${name}__inativos`].forEach((k) => {
      const s = entities[k]
      if (!s) return
      s.items = []
      s.loaded = false
      s.loading = null
      s.byId = {}
    })
  }

  return { entities, loadList, loadById, mergeById, getById, invalidate }
})
