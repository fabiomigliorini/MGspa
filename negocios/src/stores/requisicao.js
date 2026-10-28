import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { Notify } from 'quasar'
import { abrirPdf } from '@components/abrirPdf'

// Store do domínio "requisição entre lojas" (TASK-55, doc-6): serve o painel, o detalhe e o
// formulário. O status vem pronto do backend (coluna gravada pelo processo da requisição).

const notificar = (type, message, timeout = 3000) =>
  Notify.create({
    type,
    message,
    timeout,
    actions: [{ icon: 'close', color: 'white' }],
  })

const mensagemErro = (error, fallback) => {
  const data = error?.response?.data
  const primeiro = data?.errors ? Object.values(data.errors).flat()[0] : null
  return primeiro || data?.message || error?.message || fallback
}

export const STATUS = {
  A: { label: 'Aguardando', cor: 'grey-7', icone: 'hourglass_empty' },
  S: { label: 'Em separação', cor: 'orange-8', icone: 'qr_code_scanner' },
  P: { label: 'Separado parcial', cor: 'amber-9', icone: 'rule' },
  T: { label: 'Separado total', cor: 'green-7', icone: 'inventory' },
  D: { label: 'Separado com divergências', cor: 'deep-orange-7', icone: 'report' },
  E: { label: 'Entregue', cor: 'blue-7', icone: 'task_alt' },
  C: { label: 'Cancelada', cor: 'red-5', icone: 'block' },
}

// não atendida não é status gravado: é a finalizada com divergências (D) sem nenhuma parte
// separada (a loja que atende não tinha); a API manda o naoatendida
export const NAO_ATENDIDA = {
  label: 'Não atendida',
  cor: 'blue-grey-6',
  icone: 'remove_shopping_cart',
}
export const statusDe = (r) => (r?.naoatendida ? NAO_ATENDIDA : STATUS[r?.status] || STATUS.A)

export const MOTIVOS = [
  { value: 'E', label: 'Estoque do sistema errado' },
  { value: 'A', label: 'Avariado / vencido' },
  { value: 'N', label: 'Não localizado' },
  { value: 'M', label: 'Enviado a mais' },
  { value: 'S', label: 'Sem saldo' },
]

// curto: o destino nas linhas das listas e no filtro
export const DESTINOS = [
  {
    value: 'R',
    label: 'Cliente retira na loja que atende',
    curto: 'cliente retira',
    icone: 'storefront',
    cor: 'blue-7',
  },
  {
    value: 'E',
    label: 'Entregar no endereço do cliente',
    curto: 'entrega no endereço',
    icone: 'local_shipping',
    cor: 'orange-8',
  },
  {
    value: 'L',
    label: 'Reposição: vai para a minha loja',
    curto: 'reposição',
    icone: 'warehouse',
    cor: 'green-7',
  },
]

export const DESTINO = Object.fromEntries(DESTINOS.map((d) => [d.value, d]))

// Prioridade (TASK-258): o número é o do banco e ordena o painel, a mais urgente primeiro.
// Depois de criada, só muda pelo botão Prioridade do detalhe, com o motivo.
export const PRIORIDADES = [
  { value: 4, label: 'Urgente', cor: 'red-5' },
  { value: 3, label: 'Alta', cor: 'orange-5' },
  { value: 2, label: 'Normal', cor: 'grey-6' },
  { value: 1, label: 'Baixa', cor: 'grey-5' },
]

export const PRIORIDADE = Object.fromEntries(PRIORIDADES.map((p) => [p.value, p]))

// o destino escolhido no formulário pré-seleciona a prioridade: o cliente que vem buscar
// espera, a entrega tem data combinada, a reposição é a rotina
export const PRIORIDADE_DO_DESTINO = { R: 4, E: 3, L: 2 }

// As lojas que entram na requisição (painel, formulário e gerador): as que controlam estoque e
// não guardam mercadoria com defeito. Campo ausente = o PDV ainda não sincronizou; aparece como antes
export const lojaDaRequisicao = (l) => l.controlaestoque !== false && !l.defeito

// Quem retira ou recebe: o telefone i é do nome i (só os dígitos, com o DDD). O form sempre
// tem pelo menos uma linha.
const contatos = (nomes, telefones) => {
  const lista = nomes?.length ? [...nomes] : ['']
  return { nomes: lista, telefones: lista.map((_, i) => telefones?.[i] || '') }
}

// listagem: sem filtro padrão, todas as requisições
const POR_PAGINA = 50

const filtrosVazios = () => ({
  codrequisicao: null,
  status: null,
  destino: null,
  prioridade: null,
  codnegocio: null,
  de: null,
  ate: null,
  codestoquelocalrequisitante: null,
  codestoquelocalrequisitada: null,
  codpessoa: null,
  busca: null,
  telefone: null,
  codigoretirada: null,
})

const formVazio = () => ({
  codrequisicao: null,
  codestoquelocalrequisitante: null,
  codestoquelocalrequisitada: null,
  destino: 'R',
  prioridade: PRIORIDADE_DO_DESTINO.R,
  codpessoa: null,
  ...contatos(),
  endereco: '',
  codnegocio: null,
  observacoes: '',
  itens: [],
})

export const requisicaoStore = defineStore('requisicao', () => {
  // painel
  const codestoquelocal = ref(null)
  const lista = ref([])
  const carregandoPainel = ref(false)
  // o voltar das outras telas cai na loja que estava no painel; sem ela, ele abre na do PDV
  const enderecoPainel = computed(() =>
    codestoquelocal.value ? `/requisicao/painel/${codestoquelocal.value}` : '/requisicao/painel',
  )

  // listagem
  const filtros = ref(filtrosVazios())
  const requisicoes = ref([])
  const carregandoListagem = ref(false)
  let solicitacao = 0

  const params = computed(() =>
    Object.fromEntries(
      Object.entries(filtros.value).filter(([, valor]) => valor !== null && valor !== ''),
    ),
  )
  const filtrosAtivos = computed(() => Object.keys(params.value).length)

  // detalhe
  const requisicao = ref(null)
  const carregando = ref(false)

  // formulário
  const form = ref(formVazio())
  const salvando = ref(false)
  const isNovo = computed(() => !form.value.codrequisicao)

  async function carregarPainel() {
    const loja = codestoquelocal.value
    if (!loja) return true
    carregandoPainel.value = true
    try {
      const { data } = await api.get('v1/requisicao/painel', {
        params: { codestoquelocal: loja },
      })
      // a resposta atrasada de outra aba não sobrescreve a loja que está na tela
      if (loja === codestoquelocal.value) lista.value = data
      return true
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível carregar as requisições'))
      return false
    } finally {
      carregandoPainel.value = false
    }
  }

  // Devolve se tem mais página. Resposta atrasada de um filtro antigo é descartada,
  // senão ela sobrescreveria a lista do filtro novo.
  async function carregarListagem(pagina = 1) {
    const id = ++solicitacao
    carregandoListagem.value = true
    try {
      const { data } = await api.get('v1/requisicao', {
        params: { ...params.value, page: pagina },
      })
      if (id !== solicitacao) return false
      requisicoes.value = pagina === 1 ? data : requisicoes.value.concat(data)
      return data.length === POR_PAGINA
    } catch (error) {
      if (id === solicitacao) {
        notificar('negative', mensagemErro(error, 'Impossível carregar as requisições'))
      }
      return false
    } finally {
      if (id === solicitacao) carregandoListagem.value = false
    }
  }

  function limparFiltros() {
    filtros.value = filtrosVazios()
  }

  async function carregar(codrequisicao) {
    carregando.value = true
    try {
      const { data } = await api.get(`v1/requisicao/${codrequisicao}`)
      requisicao.value = data.data
      return true
    } catch (error) {
      requisicao.value = null
      notificar('negative', mensagemErro(error, 'Impossível carregar a requisição'))
      return false
    } finally {
      carregando.value = false
    }
  }

  // itens do negócio informado, para marcar na etapa dos produtos (nascem desmarcados:
  // o normal é requisitar só o que faltou na loja que vendeu)
  const itensNegocio = ref([])
  const carregandoNegocio = ref(false)
  const codnegocioCarregado = ref(null)

  function novo(dados = {}) {
    form.value = { ...formVazio(), ...dados }
    itensNegocio.value = []
    codnegocioCarregado.value = null
  }

  // Etapa do negócio: o cliente da venda fechada é o padrão de quem retira ou recebe e os
  // itens vendidos são a lista para marcar. Busca só quando o número muda. comLoja: a
  // requisição nasce do negócio (botão do PDV) e a loja dele é quem pede
  async function carregarNegocio({ comLoja = false } = {}) {
    const codnegocio = form.value.codnegocio
    if (!codnegocio) {
      itensNegocio.value = []
      codnegocioCarregado.value = null
      return true
    }
    if (codnegocioCarregado.value === codnegocio) return true
    carregandoNegocio.value = true
    try {
      const { data } = await api.get(`v1/requisicao/rascunho/negocio/${codnegocio}`)
      if (comLoja) form.value.codestoquelocalrequisitante = data.codestoquelocal
      form.value.codpessoa = data.codpessoa
      Object.assign(form.value, contatos(data.nomes, data.telefones))
      form.value.endereco = data.endereco || ''
      itensNegocio.value = (data.itens || []).map((i) => ({ ...i, marcado: false }))
      codnegocioCarregado.value = codnegocio
      return true
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível trazer o negócio'))
      return false
    } finally {
      carregandoNegocio.value = false
    }
  }

  // o que ficou marcado vira os itens da requisição
  function aplicarSelecaoNegocio() {
    form.value.itens = itensNegocio.value
      .filter((i) => i.marcado && i.quantidade > 0)
      // eslint-disable-next-line no-unused-vars
      .map(({ marcado, ...item }) => item)
  }

  // o negócio gravado conta como já trazido: passar pela etapa dele não troca quem retira
  // pelo cliente da venda; só trocar o número traz de novo
  function editar(r) {
    itensNegocio.value = []
    codnegocioCarregado.value = r.codnegocio
    form.value = {
      codrequisicao: r.codrequisicao,
      codestoquelocalrequisitante: r.codestoquelocalrequisitante,
      codestoquelocalrequisitada: r.codestoquelocalrequisitada,
      destino: r.destino,
      prioridade: r.prioridade,
      codpessoa: r.codpessoa,
      ...contatos(r.nomes, r.telefones),
      endereco: r.endereco || '',
      codnegocio: r.codnegocio,
      observacoes: r.observacoes || '',
      itens: r.itens
        .filter((i) => i.quantidade > 0)
        .map((i) => ({
          codprodutobarra: i.codprodutobarra,
          quantidade: i.quantidade,
          produto: i.produto,
        })),
    }
  }

  // o mesmo produto bipado de novo soma na linha que já existe
  function adicionarItem(produto, quantidade) {
    // a tela mostra a lista invertida: o rebipado vai para o fim, para subir ao topo
    const indice = form.value.itens.findIndex((i) => i.codprodutobarra === produto.codprodutobarra)
    if (indice >= 0) {
      const [existente] = form.value.itens.splice(indice, 1)
      existente.quantidade = Number(existente.quantidade) + Number(quantidade)
      form.value.itens.push(existente)
      return
    }
    form.value.itens.push({ codprodutobarra: produto.codprodutobarra, quantidade, produto })
  }

  function removerItem(indice) {
    form.value.itens.splice(indice, 1)
  }

  // devolve a requisição gravada (a tela navega para o detalhe)
  async function salvar() {
    salvando.value = true
    try {
      const { codrequisicao, itens, nomes, telefones, ...cabecalho } = form.value
      // a reposição não tem quem retire ou receba
      const temCliente = cabecalho.destino !== 'L'
      const dados = {
        ...cabecalho,
        nomes: temCliente ? nomes : [],
        telefones: temCliente ? telefones : [],
        itens: itens.map((i) => ({
          codprodutobarra: i.codprodutobarra,
          quantidade: i.quantidade,
        })),
      }
      const { data } = isNovo.value
        ? await api.post('v1/requisicao', dados)
        : await api.put(`v1/requisicao/${codrequisicao}`, dados)
      requisicao.value = data.data
      notificar('positive', isNovo.value ? 'Requisição criada' : 'Requisição alterada')
      // o que não saiu sozinho na impressora (dispositivo ou loja sem impressora)
      for (const aviso of data.avisos || []) notificar('warning', aviso)
      return data.data
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível salvar a requisição'))
      return null
    } finally {
      salvando.value = false
    }
  }

  // troca só a prioridade; o motivo vai para a auditoria (o histórico do detalhe)
  async function trocarPrioridade(r, prioridade, motivo) {
    try {
      const { data } = await api.put(`v1/requisicao/${r.codrequisicao}/prioridade`, {
        prioridade,
        motivo,
      })
      requisicao.value = data.data
      notificar('positive', `Prioridade ${PRIORIDADE[prioridade]?.label}`)
      return true
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível trocar a prioridade'))
      return false
    }
  }

  // desfaz o Finalizar com divergências (Gerente da loja que atende ou Administrador); o motivo vai para a auditoria
  async function reabrir(r, motivo) {
    try {
      const { data } = await api.post(`v1/requisicao/${r.codrequisicao}/reabrir`, { motivo })
      requisicao.value = data.data
      notificar('positive', 'Requisição reaberta')
      return true
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível reabrir a requisição'))
      return false
    }
  }

  async function cancelar(r) {
    try {
      const { data } = await api.post(`v1/requisicao/${r.codrequisicao}/cancelar`)
      requisicao.value = data.data
      notificar('positive', 'Requisição cancelada')
      return true
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível cancelar a requisição'))
      return false
    }
  }

  // via: requisitante (com o código) ou requisitada (separação); impressora vazia = a padrão
  async function imprimir(r, via, impressora = null) {
    try {
      const { data } = await api.post(`v1/requisicao/${r.codrequisicao}/imprimir`, {
        via,
        impressora,
      })
      notificar('positive', data.mensagem)
      return true
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível imprimir'))
      return false
    }
  }

  // tira a justificativa do item (achou o produto, tem saldo); o enviado a mais sai da requisição
  async function tirarJustificativa(item) {
    try {
      const { data } = await api.delete(
        `v1/requisicao-item/${item.codrequisicaoitem}/justificativa`,
      )
      requisicao.value = data.data
      notificar('positive', 'Justificativa retirada')
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível tirar a justificativa'))
    }
  }

  // justificativa de quem atende (motivo + observação + substituto), item a item
  async function justificarItem(item, dados) {
    try {
      const { data } = await api.put(`v1/requisicao-item/${item.codrequisicaoitem}`, dados)
      requisicao.value = data.data
      notificar('positive', 'Justificativa gravada')
      return true
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível gravar a justificativa'))
      return false
    }
  }

  async function incluirEnviadoAMais(r, codprodutobarra) {
    try {
      const { data } = await api.post(`v1/requisicao/${r.codrequisicao}/item`, {
        codprodutobarra,
      })
      requisicao.value = data.data
      notificar('positive', 'Incluído como enviado a mais')
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível incluir'))
    }
  }

  async function finalizar(r) {
    try {
      const { data } = await api.post(`v1/requisicao/${r.codrequisicao}/finalizar`)
      requisicao.value = data.data
      notificar('positive', 'Requisição finalizada')
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível finalizar'), 8000)
    }
  }

  // entrega das partes separadas: códigos de retirada (ou justificativa) e quem recebeu
  async function entregar(r, dados) {
    try {
      const { data } = await api.post(`v1/requisicao/${r.codrequisicao}/entregar`, dados)
      requisicao.value = data.data
      notificar('positive', 'Entrega registrada')
      return true
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível registrar a entrega'))
      return false
    }
  }

  async function reprocessar(r) {
    try {
      const { data } = await api.post(`v1/requisicao/${r.codrequisicao}/reprocessar`)
      requisicao.value = data.data
      notificar('positive', 'Requisição reprocessada')
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível reprocessar'))
    }
  }

  // A janela do PDF, igual à DANFE da NFC-e: o botão do cabeçalho chama de
  // volta a tela, que pergunta a impressora antes de mandar o papel, e a janela
  // do PDF fecha.
  function verPdf(r, via, formato, aoImprimir = null) {
    return abrirPdf(
      api,
      `v1/requisicao/${r.codrequisicao}/pdf`,
      { via, formato },
      {
        title: `Requisição #${r.codrequisicao}`,
        size: formato === 'a5' ? 'a4' : 'cupom',
        onImprimir: aoImprimir,
        fecharAoImprimir: true,
      },
    )
  }

  return {
    codestoquelocal,
    lista,
    carregandoPainel,
    enderecoPainel,
    filtros,
    filtrosAtivos,
    requisicoes,
    carregandoListagem,
    carregarListagem,
    limparFiltros,
    requisicao,
    carregando,
    form,
    salvando,
    isNovo,
    carregarPainel,
    carregar,
    novo,
    itensNegocio,
    carregandoNegocio,
    carregarNegocio,
    aplicarSelecaoNegocio,
    editar,
    adicionarItem,
    removerItem,
    salvar,
    trocarPrioridade,
    reabrir,
    cancelar,
    imprimir,
    verPdf,
    justificarItem,
    incluirEnviadoAMais,
    finalizar,
    tirarJustificativa,
    reprocessar,
    entregar,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(requisicaoStore, import.meta.hot))
}
