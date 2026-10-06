import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { normalizarCargaParaExibicao } from 'src/utils/carga'
import { abrirPdf } from 'src/utils/abrirPdf'
import { notifyError } from 'src/utils/notify'

// Consulta do HISTÓRICO de romaneios — tela /cargas.
//
// Deliberadamente SEM Dexie: o pátio (stores/carga.js) é offline-first e só
// enxerga a safra ativa; aqui a pergunta é outra ("o que saiu pro contrato
// CT-0042 na safra passada?") e precisa do servidor. Os cadastros dos selects
// também vêm da API, porque quem abre esta tela pode nunca ter aberto o pátio
// e ter o Dexie vazio.

const PER_PAGE = 50

function primeiroDiaDoMes() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
}

function filtrosVazios() {
  return {
    data_inicio: null,
    data_fim: null,
    codsafra: null,
    codcultura: null,
    sentido: null,
    etapa: null,
    // 1 = ativos, 2 = cancelados, 9 = todos (MgModel::scopeAtivoInativo)
    inativo: 1,
    papel: null,
    codunidadearmazenadora: null,
    codplantio: null,
    codcontrato: null,
    codpessoacontrato: null,
    placa: null,
    placacarreta: null,
    motorista: null,
    codcarga: null,
  }
}

// Chaves que não contam como "filtro ativo" no valor default (senão o contador
// do drawer nasce em 1 e o botão limpar parece que não funciona).
const DEFAULTS_NEUTROS = { inativo: 1, papel: null }

export const useCargaListagemStore = defineStore(
  'cargaListagem',
  () => {
    const cargas = ref([])
    const totais = ref({ qtd: 0, bruto: 0, desconto: 0, liquido: 0, sentidos: {} })
    const paginacao = ref({ page: 1, perPage: PER_PAGE, hasMore: true, loading: false, total: 0 })
    const filtros = ref({ ...filtrosVazios(), data_inicio: primeiroDiaDoMes() })
    const agrupar = ref('nenhum')
    const carregadoUmaVez = ref(false)

    // Cadastros dos selects (online).
    const safras = ref([])
    const culturas = ref([])
    const unidades = ref([])
    const contratos = ref([])
    const plantios = ref([])
    const carregandoPlantios = ref(false)

    const filtrosAtivos = computed(() =>
      Object.entries(filtros.value).filter(([k, v]) => {
        if (v === null || v === '' || v === undefined) return false
        return !(k in DEFAULTS_NEUTROS) || v !== DEFAULTS_NEUTROS[k]
      }),
    )
    const contagemFiltros = computed(() => filtrosAtivos.value.length)
    const carregando = computed(() => paginacao.value.loading)

    // Cultura das cargas carregadas, quando é uma só — é o que permite somar
    // sacas nos totais (a tela decide).
    // `Safra.cultura` minúsculo: relação aninhada num model sai em snake_case
    // pelo Eloquent; só o `Safra` do topo é PascalCase (posto pelo Resource).
    const culturaUnica = computed(() => {
      const codigos = new Set(cargas.value.map((c) => c.Safra?.codcultura).filter((v) => v != null))
      if (codigos.size !== 1) return null
      const cod = [...codigos][0]
      return cargas.value.find((c) => c.Safra?.codcultura === cod)?.Safra?.cultura || null
    })

    // Tradução UI -> backend. O `inativo` NUNCA pode faltar: o CargaService só
    // aplica o scope quando a chave vem preenchida, então omiti-la traria as
    // canceladas junto e ninguém entenderia por quê.
    function params(extra = {}) {
      return { ...filtros.value, inativo: filtros.value.inativo || 1, ...extra }
    }

    // O filtro salvo no navegador pode ser de uma versão anterior da tela (o
    // toggle "canceladas" virou `inativo`): fica só com as chaves de hoje, e as
    // que faltam entram com o default — senão a Situação nasce sem nada marcado
    // e o contador conta uma chave que nem existe mais.
    function normalizarFiltros() {
      const atual = filtros.value || {}
      const base = filtrosVazios()
      for (const k of Object.keys(base)) {
        if (k in atual) base[k] = atual[k]
      }
      if (![1, 2, 9].includes(base.inativo)) base.inativo = 1
      filtros.value = base
    }

    // Token da requisição em voo. Sem isso, um filtro digitado enquanto o
    // infinite-scroll está carregando ou é descartado (se abortarmos por
    // `loading`) ou chega fora de ordem e sobrescreve o resultado novo.
    let requisicaoId = 0

    // A busca em andamento: o scroll infinito espera por ela em vez de pedir
    // a mesma página de novo (ou concluir que acabou) — como no valeModelo.
    let emVoo = null

    function buscar(reset = false) {
      emVoo = buscarPagina(reset)
      return emVoo
    }

    // Chamado pelo q-infinite-scroll; devolve se ainda tem página depois desta.
    async function carregarMais() {
      if (emVoo) await emVoo
      if (paginacao.value.hasMore) await buscar()
      return paginacao.value.hasMore
    }

    // A listagem sempre abre da primeira página, e não do ponto em que a store
    // ficou na visita anterior.
    function reiniciar() {
      requisicaoId++
      emVoo = null
      cargas.value = []
      paginacao.value = { page: 1, perPage: PER_PAGE, hasMore: true, loading: false, total: 0 }
      carregadoUmaVez.value = false
    }

    async function buscarPagina(reset) {
      if (!reset && (paginacao.value.loading || !paginacao.value.hasMore)) return

      const id = ++requisicaoId
      if (reset) {
        paginacao.value.page = 1
        paginacao.value.hasMore = true
      }
      paginacao.value.loading = true

      try {
        const { data } = await api.get('v1/carga/listagem', {
          params: params({ page: paginacao.value.page, per_page: paginacao.value.perPage }),
        })
        if (id !== requisicaoId) return // resposta obsoleta

        const novas = (data.data ?? []).map(normalizarCargaParaExibicao)
        cargas.value = reset ? novas : [...cargas.value, ...novas]
        totais.value = data.totais ?? totais.value
        paginacao.value.total = data.meta?.total ?? novas.length
        paginacao.value.hasMore = (data.meta?.current_page ?? 1) < (data.meta?.last_page ?? 1)
        if (paginacao.value.hasMore) paginacao.value.page++
        carregadoUmaVez.value = true
      } catch (e) {
        if (id === requisicaoId) notifyError(e)
      } finally {
        if (id === requisicaoId) paginacao.value.loading = false
      }
    }

    // Varre todas as páginas: MgModel::$perPage = 50 e o 51º contrato sumiria
    // do select sem nenhum aviso.
    async function todasPaginas(endpoint, extra = {}) {
      const todos = []
      let page = 1
      let last = 1
      do {
        const { data } = await api.get(endpoint, {
          params: { ...extra, page },
          skipLoading: true,
        })
        todos.push(...(Array.isArray(data) ? data : (data.data ?? [])))
        last = data?.meta?.last_page ?? data?.last_page ?? 1
        page++
      } while (page <= last)
      return todos
    }

    async function carregarCadastros() {
      const [s, c, u, ct] = await Promise.all([
        todasPaginas('v1/safra'),
        todasPaginas('v1/cultura'),
        todasPaginas('v1/unidade-armazenadora'),
        todasPaginas('v1/contrato'),
      ])
      safras.value = s
      culturas.value = c
      unidades.value = u
      contratos.value = ct
    }

    // Talhões dependem da safra escolhida — sem safra a lista seria inútil
    // (todo talhão de toda safra) e pesada.
    async function carregarPlantios(codsafra) {
      if (!codsafra) {
        plantios.value = []
        return
      }
      carregandoPlantios.value = true
      try {
        plantios.value = await todasPaginas(`v1/safra/${codsafra}/plantio`)
      } catch (e) {
        notifyError(e)
      } finally {
        carregandoPlantios.value = false
      }
    }

    // O watch do drawer recarrega; chamar a busca aqui também faria duas
    // requisições.
    function limparFiltros() {
      filtros.value = { ...filtrosVazios(), data_inicio: primeiroDiaDoMes() }
      plantios.value = []
    }

    // O PDF usa EXATAMENTE os filtros da tela — é o que torna "imprimir o que
    // estou vendo" verdade. `agrupar` só viaja aqui, nunca na busca da tela.
    function imprimirRelatorio() {
      return abrirPdf('v1/carga/relatorio', params({ agrupar: agrupar.value }), {
        title: 'Romaneios',
      })
    }

    return {
      cargas,
      totais,
      paginacao,
      filtros,
      agrupar,
      carregadoUmaVez,
      safras,
      culturas,
      unidades,
      contratos,
      plantios,
      carregandoPlantios,
      filtrosAtivos,
      contagemFiltros,
      carregando,
      normalizarFiltros,
      culturaUnica,
      params,
      buscar,
      carregarMais,
      reiniciar,
      carregarCadastros,
      carregarPlantios,
      limparFiltros,
      imprimirRelatorio,
    }
  },
  {
    // `cargas`/`paginacao` fora de propósito: depois de um F5 a tela refaz o
    // fetch com os filtros restaurados, em vez de mostrar dado velho.
    persist: {
      pick: ['filtros', 'agrupar'],
      afterHydrate: ({ store }) => store.normalizarFiltros(),
    },
  },
)

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useCargaListagemStore, import.meta.hot))
}
