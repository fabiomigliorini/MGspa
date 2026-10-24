import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Store do domínio "local de estoque" (TASK-55, etapa 2): serve a lista, o detalhe e o
// diálogo de criar/editar. CRUD explícito na mão.

const formVazio = () => ({
  codestoquelocal: null,
  estoquelocal: '',
  sigla: '',
  codfilial: null,
  deposito: false,
  controlaestoque: true,
  codimpressora: null,
})

export const useEstoqueLocalStore = defineStore('estoqueLocal', () => {
  const locais = ref([])
  const carregando = ref(false)

  const local = ref(null)
  const carregandoLocal = ref(false)

  const form = ref(formVazio())
  const dialog = ref(false)
  const salvando = ref(false)
  const isNovo = computed(() => !form.value.codestoquelocal)

  // indicadores de qualidade do estoque do local como requisitado (TASK-55, etapa 11)
  const indicadores = ref(null)
  const carregandoIndicadores = ref(false)

  async function carregarIndicadores(codestoquelocal, inicio, fim) {
    carregandoIndicadores.value = true
    try {
      const { data } = await api.get(`v1/estoque-local/${codestoquelocal}/indicadores`, {
        params: { inicio, fim },
      })
      indicadores.value = data
    } catch (e) {
      indicadores.value = null
      notifyError(e, 'Erro ao carregar os indicadores')
    } finally {
      carregandoIndicadores.value = false
    }
  }

  async function carregarLista() {
    carregando.value = true
    try {
      const { data } = await api.get('v1/estoque-local', { params: { sort: 'codestoquelocal' } })
      locais.value = data.data || []
    } catch (e) {
      notifyError(e, 'Erro ao carregar os locais de estoque')
    } finally {
      carregando.value = false
    }
  }

  async function carregarLocal(codestoquelocal) {
    carregandoLocal.value = true
    try {
      const { data } = await api.get(`v1/estoque-local/${codestoquelocal}`)
      local.value = data
    } catch (e) {
      local.value = null
      notifyError(e, 'Erro ao carregar o local de estoque')
    } finally {
      carregandoLocal.value = false
    }
  }

  function novo() {
    form.value = formVazio()
    dialog.value = true
  }

  function editar(registro) {
    form.value = {
      codestoquelocal: registro.codestoquelocal,
      estoquelocal: registro.estoquelocal,
      sigla: registro.sigla,
      codfilial: registro.codfilial,
      deposito: !!registro.deposito,
      controlaestoque: !!registro.controlaestoque,
      codimpressora: registro.codimpressora,
    }
    dialog.value = true
  }

  function substituir(registro) {
    const i = locais.value.findIndex((l) => l.codestoquelocal === registro.codestoquelocal)
    if (i >= 0) locais.value.splice(i, 1, registro)
    else locais.value.push(registro)
    if (local.value?.codestoquelocal === registro.codestoquelocal) local.value = registro
  }

  // devolve o registro salvo (o Index navega para o detalhe do novo)
  async function salvar() {
    salvando.value = true
    try {
      const { codestoquelocal, ...dados } = form.value
      dados.sigla = (dados.sigla || '').toUpperCase()
      const { data } = isNovo.value
        ? await api.post('v1/estoque-local', dados)
        : await api.put(`v1/estoque-local/${codestoquelocal}`, dados)
      substituir(data)
      dialog.value = false
      notifySuccess(isNovo.value ? 'Local de estoque cadastrado' : 'Local de estoque alterado')
      return data
    } catch (e) {
      notifyError(e, 'Erro ao salvar o local de estoque')
      return null
    } finally {
      salvando.value = false
    }
  }

  async function alternarInativo(registro) {
    try {
      const url = `v1/estoque-local/${registro.codestoquelocal}/inativo`
      const { data } = registro.inativo ? await api.delete(url) : await api.post(url)
      substituir(data)
      notifySuccess(data.inativo ? 'Local de estoque inativado' : 'Local de estoque ativado')
    } catch (e) {
      notifyError(e, 'Erro ao alterar a situação')
    }
  }

  return {
    locais,
    carregando,
    local,
    carregandoLocal,
    form,
    dialog,
    salvando,
    isNovo,
    indicadores,
    carregandoIndicadores,
    carregarIndicadores,
    carregarLista,
    carregarLocal,
    novo,
    editar,
    salvar,
    alternarInativo,
  }
})
