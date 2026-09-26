import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'boot/axios'
import { Notify } from 'quasar'
import { extrairErro } from '@components/extrairErro'

// Vales compras vendidos (tblnegociovale), ativos e cancelados.

const POR_PAGINA = 50

const filtrosVazios = () => ({
  busca: null,
  codvalemodelo: null,
  codpessoafavorecido: null,
  codnegocio: null,
  valorde: null,
  valorate: null,
  situacao: 'ativo',
  comsaldo: null,
  de: null,
  ate: null,
})

export const valeEmitidosStore = defineStore(
  'valeEmitidos',
  () => {
    const filtros = ref(filtrosVazios())
    const vales = ref([])
    const carregando = ref(false)
    let solicitacao = 0

    const filtrosAtivos = computed(
      () =>
        Object.entries(filtros.value).filter(([chave, valor]) =>
          chave === 'situacao' ? valor !== 'ativo' : valor !== null && valor !== '',
        ).length,
    )

    const params = computed(() =>
      Object.fromEntries(
        Object.entries(filtros.value).filter(([, valor]) => valor !== null && valor !== ''),
      ),
    )

    // Devolve se tem mais pagina. Resposta atrasada de um filtro antigo e
    // descartada, senao ela sobrescreveria a lista do filtro novo.
    async function carregar(pagina = 1) {
      const id = ++solicitacao
      carregando.value = true
      try {
        const { data } = await api.get('v1/vale-emitido', {
          params: { ...params.value, page: pagina },
        })
        if (id !== solicitacao) return false
        vales.value = pagina === 1 ? data.data : vales.value.concat(data.data)
        return data.data.length === POR_PAGINA
      } catch (error) {
        if (id === solicitacao) {
          Notify.create({
            type: 'negative',
            message: extrairErro(error, 'Não foi possível carregar os vales emitidos'),
          })
        }
        return false
      } finally {
        if (id === solicitacao) carregando.value = false
      }
    }

    function limparFiltros() {
      filtros.value = filtrosVazios()
    }

    return { filtros, filtrosAtivos, params, vales, carregando, carregar, limparFiltros }
  },
  {
    persist: {
      pick: ['filtros'],
      // Filtro salvo por versao anterior da tela pode vir sem campo novo ou
      // com o codigo em texto ("183"), e o select so mostra o nome quando o
      // valor e numero.
      afterHydrate: ({ store }) => {
        const f = { ...filtrosVazios(), ...store.filtros }
        for (const cod of ['codvalemodelo', 'codpessoafavorecido', 'codnegocio']) {
          f[cod] = f[cod] ? Number(f[cod]) : null
        }
        if (!['ativo', 'cancelado', 'todos'].includes(f.situacao)) f.situacao = 'ativo'
        store.filtros = f
      },
    },
  },
)

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(valeEmitidosStore, import.meta.hot))
}
