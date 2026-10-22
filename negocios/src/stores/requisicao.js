import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { Notify } from 'quasar'
import { abrirPdf } from '@components/abrirPdf'

// Store do domínio "requisição entre lojas" (TASK-55, doc-6): serve o painel, o detalhe e o
// formulário. O status vem pronto do backend (coluna gravada pelo processo da requisição).

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

export const STATUS = {
  A: { label: 'Aguardando', cor: 'grey-7', icone: 'hourglass_empty' },
  S: { label: 'Em separação', cor: 'orange-8', icone: 'qr_code_scanner' },
  P: { label: 'Separado parcial', cor: 'amber-9', icone: 'rule' },
  T: { label: 'Separado total', cor: 'green-7', icone: 'inventory' },
  D: { label: 'Separado com divergências', cor: 'deep-orange-7', icone: 'report' },
  E: { label: 'Entregue', cor: 'blue-7', icone: 'task_alt' },
  C: { label: 'Cancelada', cor: 'red-5', icone: 'block' },
}

export const MOTIVOS = [
  { value: 'E', label: 'Estoque do sistema errado' },
  { value: 'A', label: 'Avariado / vencido' },
  { value: 'N', label: 'Não localizado' },
  { value: 'M', label: 'Enviado a mais' },
  { value: 'S', label: 'Sem saldo' },
]

export const DESTINOS = [
  { value: 'R', label: 'Cliente retira na loja que atende' },
  { value: 'E', label: 'Entregar no endereço do cliente' },
  { value: 'L', label: 'Reposição: vai para a minha loja' },
]

const formVazio = () => ({
  codrequisicao: null,
  codestoquelocalrequisitante: null,
  codestoquelocalrequisitada: null,
  destino: 'R',
  codpessoa: null,
  nome: '',
  telefone: '',
  endereco: '',
  codnegocio: null,
  observacoes: '',
  itens: [],
})

export const requisicaoStore = defineStore(
  'requisicao',
  () => {
    // painel
    const lado = ref('atender')
    const codestoquelocal = ref(null)
    const finalizadas = ref(false)
    const lista = ref([])
    const carregandoPainel = ref(false)

    // detalhe
    const requisicao = ref(null)
    const carregando = ref(false)

    // formulário
    const form = ref(formVazio())
    const salvando = ref(false)
    const isNovo = computed(() => !form.value.codrequisicao)

    async function carregarPainel() {
      if (!codestoquelocal.value) return true
      carregandoPainel.value = true
      try {
        const { data } = await api.get('v1/requisicao/painel', {
          params: {
            codestoquelocal: codestoquelocal.value,
            lado: lado.value,
            finalizadas: finalizadas.value ? 1 : 0,
          },
        })
        lista.value = data
        return true
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível carregar as requisições'))
        return false
      } finally {
        carregandoPainel.value = false
      }
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

    function novo(dados = {}) {
      form.value = { ...formVazio(), ...dados }
    }

    // Requisitar a partir da venda fechada: cliente e itens vêm prontos e editáveis
    async function novoDoNegocio(codnegocio, dados = {}) {
      novo(dados)
      try {
        const { data } = await api.get(`v1/requisicao/rascunho/negocio/${codnegocio}`)
        form.value = { ...form.value, ...data, destino: 'R' }
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível trazer os itens do negócio'))
      }
    }

    // Sugerir abaixo do mínimo: só acrescenta à lista o que ainda não está nela
    const sugerindo = ref(false)
    async function sugerir() {
      sugerindo.value = true
      try {
        const { data } = await api.get('v1/requisicao/sugestao', {
          params: {
            codestoquelocalrequisitante: form.value.codestoquelocalrequisitante,
            codestoquelocalrequisitada: form.value.codestoquelocalrequisitada,
          },
        })
        const presentes = new Set(form.value.itens.map((i) => i.codprodutobarra))
        const novos = data.filter((i) => !presentes.has(i.codprodutobarra))
        form.value.itens.push(...novos)
        notificar(
          novos.length ? 'positive' : 'info',
          novos.length
            ? `${novos.length} produto(s) abaixo do mínimo acrescentados`
            : 'Nada novo abaixo do mínimo com saldo na outra loja',
        )
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível sugerir'))
      } finally {
        sugerindo.value = false
      }
    }

    function editar(r) {
      form.value = {
        codrequisicao: r.codrequisicao,
        codestoquelocalrequisitante: r.codestoquelocalrequisitante,
        codestoquelocalrequisitada: r.codestoquelocalrequisitada,
        destino: r.destino,
        codpessoa: r.codpessoa,
        nome: r.nome || '',
        telefone: r.telefone || '',
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
      const existente = form.value.itens.find((i) => i.codprodutobarra === produto.codprodutobarra)
      if (existente) {
        existente.quantidade = Number(existente.quantidade) + Number(quantidade)
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
        const { codrequisicao, itens, ...cabecalho } = form.value
        const dados = {
          ...cabecalho,
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
        notificar('negative', mensagemErro(error, 'Impossível finalizar'))
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

    function verPdf(r, via, formato) {
      return abrirPdf(
        api,
        `v1/requisicao/${r.codrequisicao}/pdf`,
        { via, formato },
        { title: `Requisição #${r.codrequisicao}`, size: formato === 'a5' ? 'a4' : 'cupom' },
      )
    }

    return {
      lado,
      codestoquelocal,
      finalizadas,
      lista,
      carregandoPainel,
      requisicao,
      carregando,
      form,
      salvando,
      isNovo,
      carregarPainel,
      carregar,
      novo,
      novoDoNegocio,
      sugerindo,
      sugerir,
      editar,
      adicionarItem,
      removerItem,
      salvar,
      cancelar,
      imprimir,
      verPdf,
      justificarItem,
      incluirEnviadoAMais,
      finalizar,
      reprocessar,
    }
  },
  {
    persist: {
      pick: ['lado', 'codestoquelocal', 'finalizadas'],
    },
  },
)

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(requisicaoStore, import.meta.hot))
}
