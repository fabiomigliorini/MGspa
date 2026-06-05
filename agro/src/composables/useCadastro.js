import { reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// CRUD simples e reutilizavel pros cadastros online (cultura, variedade,
// fazenda, talhao, safra, tabela-desconto). Padrao do contas/estoque:
// lista + dialog novo/editar + inativar/ativar (POST/DELETE .../inativo) +
// excluir com confirmacao.
export function useCadastro(endpoint, pk, label = 'Registro') {
  // endpoints de domínio vivem sob v1/ (padrão do monorepo)
  endpoint = `v1/${endpoint}`
  const $q = useQuasar()

  const items = ref([])
  const carregando = ref(false)
  const salvando = ref(false)
  const dialog = ref(false)
  const isNovo = ref(true)
  const form = ref({})

  async function carregar(params = {}) {
    carregando.value = true
    try {
      const { data } = await api.get(endpoint, { params })
      items.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    } finally {
      carregando.value = false
    }
  }

  function abrirNovo(defaults = {}) {
    isNovo.value = true
    form.value = { ...defaults }
    dialog.value = true
  }

  function editar(item) {
    isNovo.value = false
    form.value = { ...item }
    dialog.value = true
  }

  async function salvar(transform) {
    if (salvando.value) return
    salvando.value = true
    try {
      const payload = transform ? transform({ ...form.value }) : form.value
      if (isNovo.value) {
        await api.post(endpoint, payload)
      } else {
        await api.put(`${endpoint}/${form.value[pk]}`, payload)
      }
      notifySuccess(`${label} salvo!`)
      dialog.value = false
      await carregar()
    } catch (e) {
      notifyError(e)
    } finally {
      salvando.value = false
    }
  }

  async function alternarInativo(item) {
    try {
      if (item.inativo) {
        await api.delete(`${endpoint}/${item[pk]}/inativo`)
      } else {
        await api.post(`${endpoint}/${item[pk]}/inativo`)
      }
      await carregar()
    } catch (e) {
      notifyError(e)
    }
  }

  function excluir(item) {
    $q.dialog({
      title: 'Excluir',
      message: `Excluir este ${label.toLowerCase()}?`,
      cancel: true,
      ok: { label: 'Excluir', color: 'red-5', unelevated: true },
    }).onOk(async () => {
      try {
        await api.delete(`${endpoint}/${item[pk]}`)
        notifySuccess('Excluído!')
        await carregar()
      } catch (e) {
        notifyError(e)
      }
    })
  }

  // reactive() desembrulha os refs — no template/script usa-se `cad.items`,
  // `cad.dialog`, `cad.form.campo` sem `.value`.
  return reactive({
    items,
    carregando,
    salvando,
    dialog,
    isNovo,
    form,
    carregar,
    abrirNovo,
    editar,
    salvar,
    alternarInativo,
    excluir,
  })
}
