import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { Notify } from 'quasar'

// Store do dominio "impressora" (TASK-253): o cadastro que substituiu o printers.json.
// impressora = nome da fila no CUPS, que e' o evento do Ably que o MGprint escuta.
// CRUD explicito na mao, sem motor generico.

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

export const TIPOS = [
  { value: 'T', label: 'Térmica 80 mm', icone: 'receipt_long' },
  { value: 'A', label: 'A5', icone: 'description' },
  { value: 'E', label: 'Etiqueta', icone: 'sell' },
]

const formVazio = () => ({
  codimpressora: null,
  impressora: '',
  descricao: '',
  tipo: 'T',
})

export const impressoraStore = defineStore('impressora', () => {
  const impressoras = ref([])
  const carregando = ref(false)

  const form = ref(formVazio())
  const dialog = ref(false)
  const salvando = ref(false)

  const isNovo = computed(() => !form.value.codimpressora)

  async function carregar() {
    carregando.value = true
    try {
      const { data } = await api.get('v1/impressora')
      impressoras.value = data.data
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível carregar as impressoras'))
    } finally {
      carregando.value = false
    }
  }

  function nova() {
    form.value = formVazio()
    dialog.value = true
  }

  function editar(impressora) {
    form.value = {
      codimpressora: impressora.codimpressora,
      impressora: impressora.impressora,
      descricao: impressora.descricao,
      tipo: impressora.tipo,
    }
    dialog.value = true
  }

  function substituir(impressora) {
    const i = impressoras.value.findIndex((x) => x.codimpressora === impressora.codimpressora)
    if (i >= 0) {
      impressoras.value.splice(i, 1, impressora)
    } else {
      impressoras.value.push(impressora)
    }
  }

  async function salvar() {
    salvando.value = true
    try {
      const { codimpressora, ...dados } = form.value
      const { data } = isNovo.value
        ? await api.post('v1/impressora', dados)
        : await api.put(`v1/impressora/${codimpressora}`, dados)
      substituir(data.data)
      dialog.value = false
      notificar('positive', isNovo.value ? 'Impressora cadastrada' : 'Impressora alterada')
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível salvar a impressora'))
    } finally {
      salvando.value = false
    }
  }

  async function alternarInativo(impressora) {
    try {
      const url = `v1/impressora/${impressora.codimpressora}/inativo`
      const { data } = impressora.inativo ? await api.delete(url) : await api.post(url)
      substituir(data.data)
      notificar('positive', data.data.inativo ? 'Impressora inativada' : 'Impressora ativada')
    } catch (error) {
      notificar('negative', mensagemErro(error, 'Impossível alterar a impressora'))
    }
  }

  return {
    impressoras,
    carregando,
    form,
    dialog,
    salvando,
    isNovo,
    carregar,
    nova,
    editar,
    salvar,
    alternarInativo,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(impressoraStore, import.meta.hot))
}
