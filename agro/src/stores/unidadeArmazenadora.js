import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Store do domínio unidade armazenadora (entidade única). Uma store para a tela:
// lista + registro em edição + CRUD explícito (KISS, sem motor genérico).
export const useUnidadeArmazenadoraStore = defineStore('unidadeArmazenadora', () => {
  const unidades = ref([])
  const carregando = ref(false)
  const dialog = ref(false)
  const form = ref({})
  const salvando = ref(false)

  async function carregar() {
    carregando.value = true
    try {
      const { data } = await api.get('v1/unidade-armazenadora', { params: { inativo: 9 } })
      unidades.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    } finally {
      carregando.value = false
    }
  }

  function novo() {
    form.value = {
      codunidadearmazenadora: null,
      unidadearmazenadora: '',
      tipo: 'PROPRIO',
      codpessoa: null,
      capacidadesacas: null,
      observacao: null,
    }
    dialog.value = true
  }
  function editar(u) {
    form.value = JSON.parse(JSON.stringify(u))
    dialog.value = true
  }
  async function salvar() {
    if (salvando.value) return
    salvando.value = true
    try {
      const f = form.value
      if (f.codunidadearmazenadora) {
        await api.put(`v1/unidade-armazenadora/${f.codunidadearmazenadora}`, f)
      } else {
        await api.post('v1/unidade-armazenadora', f)
      }
      notifySuccess('Unidade salva')
      dialog.value = false
      await carregar()
    } catch (e) {
      notifyError(e)
    } finally {
      salvando.value = false
    }
  }
  async function alternarInativo(u) {
    try {
      if (u.inativo) {
        await api.delete(`v1/unidade-armazenadora/${u.codunidadearmazenadora}/inativo`)
      } else {
        await api.post(`v1/unidade-armazenadora/${u.codunidadearmazenadora}/inativo`)
      }
      await carregar()
    } catch (e) {
      notifyError(e)
    }
  }

  return {
    unidades,
    carregando,
    dialog,
    form,
    salvando,
    carregar,
    novo,
    editar,
    salvar,
    alternarInativo,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useUnidadeArmazenadoraStore, import.meta.hot))
}
