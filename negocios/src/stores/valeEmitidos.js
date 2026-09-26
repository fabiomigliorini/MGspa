import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'boot/axios'
import { Notify } from 'quasar'
import { extrairErro } from '@components/extrairErro'

const filtrosVazios = () => ({
  busca: '',
  codvalemodelo: null,
  codpessoafavorecido: null,
  codnegocio: null,
  valorde: null,
  valorate: null,
  situacao: 'ativo',
  de: null,
  ate: null,
})

const mensagemErro = (error) =>
  extrairErro(error, 'Não foi possível carregar os vales emitidos')

export const valeEmitidosStore = defineStore(
  'valeEmitidos',
  () => {
    const filtros = ref(filtrosVazios())
    const rows = ref([])
    const paginacao = ref({ current_page: 1, last_page: 1, total: 0 })
    const carregando = ref(false)
    let solicitacao = 0

    const filtrosAtivos = computed(() => {
      const f = filtros.value
      let total = 0
      if (f.busca) total++
      if (f.codvalemodelo) total++
      if (f.codpessoafavorecido) total++
      if (f.codnegocio) total++
      if (f.valorde !== null && f.valorde !== '') total++
      if (f.valorate !== null && f.valorate !== '') total++
      if (f.situacao !== 'ativo') total++
      if (f.de) total++
      if (f.ate) total++
      return total
    })

    async function carregar(page = 1) {
      const id = ++solicitacao
      carregando.value = true
      try {
        const params = Object.fromEntries(
          Object.entries(filtros.value).filter(([, value]) => value !== null && value !== ''),
        )
        const { data } = await api.get('v1/vale-modelo-emitidos', {
          params: { ...params, page },
        })
        if (id !== solicitacao) return true
        rows.value = page === 1 ? data.data : rows.value.concat(data.data)
        paginacao.value = {
          current_page: data.meta.current_page,
          last_page: data.meta.last_page,
          total: data.meta.total,
        }
        return paginacao.value.current_page < paginacao.value.last_page
      } catch (error) {
        if (id === solicitacao) Notify.create({ type: 'negative', message: mensagemErro(error) })
        return false
      } finally {
        if (id === solicitacao) carregando.value = false
      }
    }

    function limparFiltros() {
      filtros.value = filtrosVazios()
    }

    return {
      filtros,
      filtrosAtivos,
      rows,
      paginacao,
      carregando,
      carregar,
      limparFiltros,
    }
  },
  { persist: { pick: ['filtros'] } },
)

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(valeEmitidosStore, import.meta.hot))
}
