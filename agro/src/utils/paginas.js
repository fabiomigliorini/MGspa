import { api } from 'src/services/api'

// Varre todas as páginas de um endpoint paginado: MgModel::$perPage = 50, e o
// 51º registro sumiria da lista sem nenhum aviso. `opcoes` vai pro axios
// (ex.: { skipLoading: true }).
export async function todasPaginas(url, params = {}, opcoes = {}) {
  const todos = []
  let page = 1
  let last = 1
  do {
    const { data } = await api.get(url, { ...opcoes, params: { ...params, page } })
    todos.push(...(Array.isArray(data) ? data : (data.data ?? [])))
    last = data?.meta?.last_page ?? data?.last_page ?? 1
    page++
  } while (page <= last)
  return todos
}
