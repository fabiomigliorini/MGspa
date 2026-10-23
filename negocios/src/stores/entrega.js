import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { Notify } from 'quasar'

// Store do domínio "entrega do negócio" (TASK-55, doc-6): as listas Entregas a fazer e A
// receber, e as tentativas de entrega de um negócio. CRUD explícito na mão.

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

export const RESULTADOS = [
  { value: 'E', label: 'Entregue', cor: 'green-7', icone: 'task_alt' },
  { value: 'P', label: 'Entregue com problema', cor: 'deep-orange-7', icone: 'report' },
  { value: 'N', label: 'Não entregue', cor: 'red-5', icone: 'cancel' },
]

export const MOTIVOS = {
  P: [
    { value: 'E', label: 'Mercadoria errada' },
    { value: 'A', label: 'Avariada' },
    { value: 'F', label: 'Faltando item' },
  ],
  N: [
    { value: 'U', label: 'Cliente ausente' },
    { value: 'R', label: 'Recusou' },
    { value: 'L', label: 'Endereço não encontrado' },
  ],
}

const formVazio = () => ({ resultado: 'E', motivo: null, recebedor: '', observacao: '' })

export const entregaStore = defineStore(
  'entrega',
  () => {
    // listas
    const lado = ref('a-fazer')
    const codestoquelocal = ref(null)
    const pendentes = ref([])
    const carregandoPendentes = ref(false)

    // entregas de um negócio
    const entregas = ref([])
    const controla = ref(false)
    const entregue = ref(null)
    const form = ref(formVazio())
    const salvando = ref(false)

    async function carregarPendentes() {
      if (!codestoquelocal.value) return
      carregandoPendentes.value = true
      try {
        const { data } = await api.get(`v1/negocio/entrega/${lado.value}`, {
          params: { codestoquelocal: codestoquelocal.value },
        })
        pendentes.value = data
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível carregar as entregas'))
      } finally {
        carregandoPendentes.value = false
      }
    }

    async function carregarDoNegocio(codnegocio) {
      try {
        const { data } = await api.get(`v1/negocio/${codnegocio}/entrega`)
        entregas.value = data.data
        controla.value = data.entrega
        entregue.value = data.entregue
      } catch {
        entregas.value = []
        controla.value = false
        entregue.value = null
      }
    }

    function novo() {
      form.value = formVazio()
    }

    async function registrar(codnegocio) {
      salvando.value = true
      try {
        await api.post(`v1/negocio/${codnegocio}/entrega`, form.value)
        notificar('positive', 'Entrega registrada')
        await carregarDoNegocio(codnegocio)
        novo()
        return true
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível registrar a entrega'))
        return false
      } finally {
        salvando.value = false
      }
    }

    async function estornar(entrega) {
      try {
        await api.post(`v1/negocio-entrega/${entrega.codnegocioentrega}/inativo`)
        notificar('positive', 'Registro estornado')
        await carregarDoNegocio(entrega.codnegocio)
      } catch (error) {
        notificar('negative', mensagemErro(error, 'Impossível estornar'))
      }
    }

    return {
      lado,
      codestoquelocal,
      pendentes,
      carregandoPendentes,
      entregas,
      controla,
      entregue,
      form,
      salvando,
      carregarPendentes,
      carregarDoNegocio,
      novo,
      registrar,
      estornar,
    }
  },
  {
    persist: { pick: ['lado', 'codestoquelocal'] },
  },
)

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(entregaStore, import.meta.hot))
}
