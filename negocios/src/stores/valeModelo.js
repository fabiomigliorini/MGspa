import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/boot/axios'
import { Notify } from 'quasar'

// Store do dominio "modelo de vale compras" (catalogo do kit escolar).
// Serve as duas telas do dominio: a listagem com filtros e o formulario de
// novo/editar. CRUD explicito na mao, sem motor generico.

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

// inativo: 1 = so ativos (default), 2 = so inativos, 9 = todos
const filtrosVazios = () => ({
  modelo: null,
  codpessoafavorecido: null,
  valorde: null,
  valorate: null,
  inativo: 1,
})

const formVazio = () => ({
  codvalemodelo: null,
  modelo: '',
  codpessoafavorecido: null,
  observacoes: null,
  valoravulso: 0,
  itens: [],
})

export const valeModeloStore = defineStore(
  'valeModelo',
  () => {
    const filtros = ref(filtrosVazios())
    const modelos = ref([])
    const paginacao = ref(null)
    const carregando = ref(false)

    const form = ref(formVazio())
    const carregandoForm = ref(false)
    const salvando = ref(false)

    const isNovo = computed(() => !form.value.codvalemodelo)

    const filtrosAtivos = computed(() => {
      const f = filtros.value
      let total = 0
      if (f.modelo) total++
      if (f.codpessoafavorecido) total++
      if (f.valorde !== null && f.valorde !== '') total++
      if (f.valorate !== null && f.valorate !== '') total++
      if (f.inativo !== 1) total++
      return total
    })

    // O valor tem tres partes e o backend recalcula duas delas na gravacao;
    // aqui a tela mostra os mesmos numeros enquanto se edita.
    //   valorProdutos = soma dos itens do kit
    //   valoravulso   = digitado (e o que permite modelo sem produto)
    //   valorTotal    = a face do vale
    const valorProdutos = computed(() =>
      form.value.itens.reduce(
        (soma, i) => soma + Number(i.quantidade || 0) * Number(i.valorunitario || 0),
        0,
      ),
    )
    const valorTotal = computed(() => valorProdutos.value + Number(form.value.valoravulso || 0))

    async function carregar(pagina = 1) {
      carregando.value = true
      try {
        const f = filtros.value
        const params = { page: pagina, inativo: f.inativo }
        if (f.modelo) params.modelo = f.modelo
        if (f.codpessoafavorecido) params.codpessoafavorecido = f.codpessoafavorecido
        if (f.valorde !== null && f.valorde !== '') params.valorde = f.valorde
        if (f.valorate !== null && f.valorate !== '') params.valorate = f.valorate

        const { data } = await api.get('v1/vale-modelo', { params })
        modelos.value = pagina === 1 ? data.data : modelos.value.concat(data.data)
        paginacao.value = data.meta
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível carregar os modelos de vale'))
      } finally {
        carregando.value = false
      }
    }

    async function carregarMais() {
      const meta = paginacao.value
      if (!meta || meta.current_page >= meta.last_page || carregando.value) return false
      await carregar(meta.current_page + 1)
      return true
    }

    function limparFiltros() {
      filtros.value = filtrosVazios()
    }

    function novo() {
      form.value = formVazio()
    }

    async function carregarForm(codvalemodelo) {
      carregandoForm.value = true
      try {
        const { data } = await api.get(`v1/vale-modelo/${codvalemodelo}`)
        form.value = data.data
        return true
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível abrir o modelo de vale'))
        return false
      } finally {
        carregandoForm.value = false
      }
    }

    // O item nasce ja com produto: escolher no select e o que acrescenta a
    // linha, entao nunca existe linha pela metade para validar.
    function itemAcrescentar(opcao) {
      const existente = form.value.itens.find((i) => i.codprodutobarra === opcao.value)
      if (existente) {
        existente.quantidade = Number(existente.quantidade || 0) + 1
        return
      }
      form.value.itens.push({
        codvalemodeloprodutobarra: null,
        codprodutobarra: opcao.value,
        produto: opcao.label,
        barras: opcao.barras,
        quantidade: 1,
        valorunitario: Number(opcao.preco || 0),
      })
    }

    function itemRemover(indice) {
      form.value.itens.splice(indice, 1)
    }

    async function salvar() {
      if (salvando.value) return false
      salvando.value = true
      try {
        const f = form.value
        const payload = {
          modelo: f.modelo,
          codpessoafavorecido: f.codpessoafavorecido,
          observacoes: f.observacoes,
          valoravulso: f.valoravulso || 0,
          itens: f.itens.map((i) => ({
            codvalemodeloprodutobarra: i.codvalemodeloprodutobarra,
            codprodutobarra: i.codprodutobarra,
            quantidade: i.quantidade,
            valorunitario: i.valorunitario,
          })),
        }
        if (f.codvalemodelo) {
          await api.put(`v1/vale-modelo/${f.codvalemodelo}`, payload)
        } else {
          await api.post('v1/vale-modelo', payload)
        }
        notificar('positive', 'Modelo de vale salvo')
        await carregar(1)
        return true
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível salvar o modelo de vale'))
        return false
      } finally {
        salvando.value = false
      }
    }

    async function alternarInativo(modelo) {
      try {
        const url = `v1/vale-modelo/${modelo.codvalemodelo}/inativo`
        if (modelo.inativo) {
          await api.delete(url)
        } else {
          await api.post(url)
        }
        await carregar(1)
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível alterar a situação'))
      }
    }

    async function excluir(modelo) {
      try {
        await api.delete(`v1/vale-modelo/${modelo.codvalemodelo}`)
        notificar('positive', 'Modelo de vale excluído')
        await carregar(1)
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível excluir o modelo de vale'))
      }
    }

    return {
      filtros,
      filtrosAtivos,
      modelos,
      paginacao,
      carregando,
      form,
      carregandoForm,
      salvando,
      isNovo,
      valorProdutos,
      valorTotal,
      carregar,
      carregarMais,
      limparFiltros,
      novo,
      carregarForm,
      itemAcrescentar,
      itemRemover,
      salvar,
      alternarInativo,
      excluir,
    }
  },
  {
    persist: { pick: ['filtros'] },
  },
)

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(valeModeloStore, import.meta.hot))
}
