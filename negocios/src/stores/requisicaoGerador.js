import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { Notify } from 'quasar'
import { db } from 'boot/db'
import { lojaDaRequisicao } from 'stores/requisicao'

// Gerador de requisição (TASK-258): a reposição de uma loja montada marca por marca, olhando o
// que está zerado ou abaixo do mínimo dela e o estoque das outras lojas. O rascunho (a loja,
// as quantidades e os filtros) fica guardado no navegador: fechar e voltar não perde nada. O
// saldo nunca vai para o rascunho, recarrega sempre. No fim, uma requisição de Reposição por
// loja que atende.

const notificar = (type, message) =>
  Notify.create({
    type,
    message,
    timeout: 3000,
    actions: [{ icon: 'close', color: 'white' }],
  })

const mensagemErro = (error, fallback) => {
  const data = error?.response?.data
  const primeiro = data?.errors ? Object.values(data.errors).flat()[0] : null
  return primeiro || data?.message || error?.message || fallback
}

// '' = sem filtro (o q-select mostra o rótulo de '', não o de null)
export const SITUACOES = [
  { value: '', label: 'Todos' },
  { value: 'abaixo', label: 'Abaixo do mínimo' },
  { value: 'zerado', label: 'Só zerados' },
]

export const ABCS = [
  { value: '', label: 'Todos' },
  { value: 'A', label: 'A' },
  { value: 'AB', label: 'A + B' },
  { value: 'ABC', label: 'A + B + C' },
]

export const ORDENS = [
  { value: 'furo', label: 'Furo (R$)' },
  { value: 'alfabetica', label: 'Alfabética' },
]

// a cor da variação pela situação na loja que pede
export const COR_SITUACAO = { zerado: 'red-5', abaixo: 'orange-5' }

// a prioridade que a revisão pré-seleciona pela pior situação dos itens
const PRIORIDADE_DA_SITUACAO = { zerado: 4, abaixo: 2, ok: 1 }

// a quantidade digitada em unidades sobe para o múltiplo da embalagem de transferência; sem
// embalagem, não arredonda (a fração errada é recusada no campo, nunca arredondada)
export const arredondar = (unidades, multiplo) => {
  const u = Number(unidades)
  const m = Number(multiplo)
  if (!u || u <= 0) return 0
  if (!m || m <= 1) return u
  return Math.ceil(u / m - 1e-9) * m
}

const chave = (codprodutovariacao, codestoquelocal) => `${codprodutovariacao}-${codestoquelocal}`

export const requisicaoGeradorStore = defineStore(
  'requisicaoGerador',
  () => {
    // ---- rascunho (persistido) ----
    const codestoquelocal = ref(null)
    // chave variação-loja que atende => item; a quantidade vai na embalagem da barra
    const itens = ref({})
    const situacao = ref('')
    const abc = ref('')
    const ordem = ref('furo')

    // as lojas: as mesmas das abas do painel (a que pede e as de onde pedir)
    const lojas = ref([])
    async function carregarLojas() {
      lojas.value = await db.estoqueLocal
        .orderBy('[codfilial+sigla]')
        .filter((l) => l.inativo == null && lojaDaRequisicao(l))
        .toArray()
    }
    const nomeLoja = (cod) => lojas.value.find((l) => l.codestoquelocal === cod)?.estoquelocal || ''
    const siglaLoja = (cod) => lojas.value.find((l) => l.codestoquelocal === cod)?.sigla || ''

    const listaItens = computed(() => Object.values(itens.value))
    const totalItens = computed(() => listaItens.value.length)

    // a loja do rascunho só troca com ele vazio: gere ou limpe antes
    function usarLoja(cod) {
      if (codestoquelocal.value === cod) return true
      if (totalItens.value && codestoquelocal.value) {
        notificar(
          'negative',
          `Gere ou limpe o rascunho do ${nomeLoja(codestoquelocal.value)} antes`,
        )
        return false
      }
      // outra loja: as listas abertas eram da anterior
      codestoquelocal.value = cod
      marcas.value = []
      lista.value = { ...lista.value, chave: null }
      enderecoLista.value = null
      return true
    }

    function limparRascunho() {
      itens.value = {}
    }

    const unidadesDe = (codprodutovariacao, codestoquelocalrequisitada) =>
      itens.value[chave(codprodutovariacao, codestoquelocalrequisitada)]?.unidades ?? null

    // grava (ou tira, com 0) a quantidade de uma variação pedida a uma loja; devolve as
    // unidades arredondadas
    function definir(produto, variacao, codestoquelocalrequisitada, unidades) {
      const k = chave(variacao.codprodutovariacao, codestoquelocalrequisitada)
      const comEmbalagem = !!(produto.multiplo && variacao.codprodutobarraembalagem)
      const arredondadas = arredondar(unidades, comEmbalagem ? produto.multiplo : null)
      if (!arredondadas) {
        delete itens.value[k]
        return null
      }
      const multiplo = comEmbalagem ? Number(produto.multiplo) : 1
      itens.value[k] = {
        codprodutovariacao: variacao.codprodutovariacao,
        codestoquelocalrequisitada,
        codproduto: produto.codproduto,
        codprodutobarra: comEmbalagem
          ? variacao.codprodutobarraembalagem
          : variacao.codprodutobarraunidade,
        unidades: arredondadas,
        // na embalagem da barra: 12 UN numa CX c/12 é 1
        quantidade: arredondadas / multiplo,
        embalagem: comEmbalagem ? `${produto.siglaembalagem} C/${multiplo}` : produto.sigla,
        sigla: produto.sigla,
        produto: produto.produto,
        variacao: variacao.variacao,
      }
      return arredondadas
    }

    function remover(item) {
      delete itens.value[chave(item.codprodutovariacao, item.codestoquelocalrequisitada)]
    }

    // o total pedido de um produto, em unidades (a lista marca o que já está no rascunho)
    const pedidoDoProduto = computed(() => {
      const ret = {}
      for (const i of listaItens.value) ret[i.codproduto] = (ret[i.codproduto] || 0) + i.unidades
      return ret
    })

    // ---- marcas ----
    const marcas = ref([])
    const carregandoMarcas = ref(false)

    async function carregarMarcas() {
      carregandoMarcas.value = true
      try {
        const { data } = await api.get('v1/requisicao/gerador/marcas', {
          params: {
            codestoquelocal: codestoquelocal.value,
            abc: abc.value || undefined,
            situacao: situacao.value || undefined,
          },
        })
        marcas.value = data
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível carregar as marcas'))
      } finally {
        carregandoMarcas.value = false
      }
    }

    // ---- produtos (o voltar do produto devolve a lista como estava) ----
    // versao: cada pesquisa recomeça a paginação (o q-infinite-scroll da tela renasce)
    const lista = ref({
      chave: null,
      versao: 0,
      busca: '',
      produtos: [],
      pagina: 0,
      temMais: true,
      scroll: 0,
    })
    const carregandoProdutos = ref(false)
    // o endereço da lista aberta: o voltar da tela do produto cai nela
    const enderecoLista = ref(null)

    // a lista é a da loja + marca + filtros; mudou qualquer um, recomeça
    const chaveLista = (codmarca) =>
      [codestoquelocal.value, codmarca ?? 'todas', abc.value, situacao.value, ordem.value].join('|')

    function abrirLista(codmarca) {
      const k = chaveLista(codmarca)
      if (lista.value.chave === k) return false
      lista.value = {
        chave: k,
        versao: 0,
        busca: '',
        produtos: [],
        pagina: 0,
        temMais: true,
        scroll: 0,
      }
      return true
    }

    function recomecarLista() {
      lista.value = {
        ...lista.value,
        versao: lista.value.versao + 1,
        produtos: [],
        pagina: 0,
        temMais: true,
        scroll: 0,
      }
    }

    // o pedido em andamento: quem chama durante ele espera o mesmo (o q-infinite-scroll não
    // pode receber "pronto" antes da página chegar, senão pede de novo sem parar)
    let emAndamento = null
    function carregarProdutos(codmarca) {
      if (emAndamento) return emAndamento
      if (!lista.value.temMais) return Promise.resolve()
      emAndamento = buscarPagina(codmarca).finally(() => (emAndamento = null))
      return emAndamento
    }

    async function buscarPagina(codmarca) {
      carregandoProdutos.value = true
      const k = lista.value.chave
      const versao = lista.value.versao
      try {
        const pagina = lista.value.pagina + 1
        const { data } = await api.get('v1/requisicao/gerador/produtos', {
          params: {
            codestoquelocal: codestoquelocal.value,
            codmarca: codmarca || undefined,
            busca: lista.value.busca || undefined,
            abc: abc.value || undefined,
            situacao: situacao.value || undefined,
            ordem: ordem.value,
            page: pagina,
          },
        })
        // resposta de uma lista que já foi trocada não entra
        if (lista.value.chave !== k || lista.value.versao !== versao) return
        lista.value.produtos.push(...data)
        lista.value.pagina = pagina
        lista.value.temMais = data.length === 50
      } catch (error) {
        lista.value.temMais = false
        notificar('negative', mensagemErro(error, 'Impossível carregar os produtos'))
      } finally {
        carregandoProdutos.value = false
      }
    }

    // ---- produto ----
    const produto = ref(null)
    const carregandoProduto = ref(false)

    async function carregarProduto(codproduto) {
      // o produto anterior não aparece enquanto o novo carrega
      if (produto.value?.produto?.codproduto !== Number(codproduto)) produto.value = null
      carregandoProduto.value = true
      try {
        const { data } = await api.get(`v1/requisicao/gerador/produto/${codproduto}`, {
          params: { codestoquelocal: codestoquelocal.value },
        })
        produto.value = data
      } catch (error) {
        produto.value = null
        notificar('negative', mensagemErro(error, 'Impossível carregar o produto'))
      } finally {
        carregandoProduto.value = false
      }
    }

    // ---- revisão e geração ----
    // as lojas que atendem, cada uma com os itens e a prioridade pré-selecionada
    const prioridades = ref({})
    const erros = ref({})
    const gerando = ref(false)

    const porLoja = computed(() => {
      const ret = {}
      for (const i of listaItens.value) {
        ;(ret[i.codestoquelocalrequisitada] ??= []).push(i)
      }
      return ret
    })

    // a pior situação dos itens de cada loja, na loja que pede, agora
    async function preSelecionarPrioridades() {
      const cods = [...new Set(listaItens.value.map((i) => i.codprodutovariacao))]
      if (!cods.length) return
      try {
        const { data } = await api.get('v1/requisicao/gerador/situacao', {
          params: { codestoquelocal: codestoquelocal.value, codprodutovariacao: cods },
        })
        const ret = {}
        for (const [cod, lojaItens] of Object.entries(porLoja.value)) {
          ret[cod] = Math.max(
            ...lojaItens.map((i) => PRIORIDADE_DA_SITUACAO[data[i.codprodutovariacao]] ?? 1),
          )
        }
        prioridades.value = ret
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível conferir a situação dos itens'))
      }
    }

    // uma requisição por loja que atende; a que deu certo sai do rascunho, a que falhou fica
    // com o erro. Devolve quantas foram criadas.
    async function gerar() {
      if (gerando.value) return 0
      gerando.value = true
      erros.value = {}
      let criadas = 0
      try {
        for (const [cod, lojaItens] of Object.entries(porLoja.value)) {
          try {
            const { data } = await api.post('v1/requisicao', {
              codestoquelocalrequisitante: codestoquelocal.value,
              codestoquelocalrequisitada: Number(cod),
              destino: 'L',
              prioridade: prioridades.value[cod] ?? 2,
              itens: lojaItens.map((i) => ({
                codprodutobarra: i.codprodutobarra,
                quantidade: i.quantidade,
              })),
            })
            lojaItens.forEach(remover)
            criadas++
            for (const aviso of data.avisos || []) notificar('warning', aviso)
          } catch (error) {
            erros.value[cod] = mensagemErro(error, 'Impossível gerar a requisição')
          }
        }
      } finally {
        gerando.value = false
      }
      if (criadas) {
        notificar(
          'positive',
          `${criadas} ${criadas === 1 ? 'requisição gerada' : 'requisições geradas'}`,
        )
      }
      return criadas
    }

    return {
      lojas,
      carregarLojas,
      nomeLoja,
      siglaLoja,
      codestoquelocal,
      itens,
      situacao,
      abc,
      ordem,
      listaItens,
      totalItens,
      usarLoja,
      limparRascunho,
      unidadesDe,
      definir,
      remover,
      pedidoDoProduto,
      marcas,
      carregandoMarcas,
      carregarMarcas,
      lista,
      carregandoProdutos,
      enderecoLista,
      abrirLista,
      recomecarLista,
      carregarProdutos,
      produto,
      carregandoProduto,
      carregarProduto,
      prioridades,
      erros,
      gerando,
      porLoja,
      preSelecionarPrioridades,
      gerar,
    }
  },
  {
    persist: { pick: ['codestoquelocal', 'itens', 'situacao', 'abc', 'ordem'] },
  },
)

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(requisicaoGeradorStore, import.meta.hot))
}
