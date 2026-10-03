// Portadores (doc-4): o painel /portador (todos os portadores por filial, saldo da espécie,
// situação da gaveta, transferências a confirmar), o cadastro (criar, editar, inativar, excluir;
// Financeiro e Admin) e a importação do OFX. O período de cada portador é do periodoStore
// (@components).
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { useAuth } from 'src/composables/useAuth'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { periodoStore } from '@components/stores/periodoStore'
import { portadorTipoLabel } from 'src/constants/portadorTipo'

const vazio = () => ({
  codportador: null,
  portador: '',
  tipo: null,
  codbanco: null,
  codfilial: null,
  agencia: null,
  agenciadigito: null,
  conta: null,
  contadigito: null,
  convenio: null,
  carteira: null,
  carteiravariacao: null,
  pixdict: '',
  emiteboleto: false,
})

// Financeiro e Admin veem todas as filiais; os demais começam pela sua
const filtrosVazios = () => {
  const auth = useAuth()
  return {
    codfilial: auth.temPermissao('Financeiro') ? null : (auth.usuario.value?.codfilial ?? null),
    inativos: false,
  }
}

export const usePortadorStore = defineStore('portador', () => {
  // ---- painel ----
  const filtros = ref(filtrosVazios())
  const painel = ref([])
  const carregando = ref(false)

  // agrupado por filial e, dentro dela, por tipo (o servidor já manda na ordem E, B, A, C, O),
  // com o total da espécie (só ela tem saldo nesta fase, R3)
  const filiais = computed(() => {
    const grupos = []
    painel.value.forEach((p) => {
      let g = grupos.find((x) => x.codfilial === p.codfilial)
      if (!g) {
        g = { codfilial: p.codfilial, filial: p.filial ?? 'Sem filial', total: 0, tipos: [] }
        grupos.push(g)
      }
      let t = g.tipos.find((x) => x.tipo === p.tipo)
      if (!t) {
        t = { tipo: p.tipo, label: portadorTipoLabel(p.tipo), portadores: [] }
        g.tipos.push(t)
      }
      t.portadores.push(p)
      if (p.saldo !== null && !p.inativo) g.total = Math.round((g.total + p.saldo) * 100) / 100
    })
    return grupos
  })

  async function buscarPainel() {
    carregando.value = true
    try {
      const { data } = await api.get('v1/portador/painel', {
        params: {
          codfilial: filtros.value.codfilial || undefined,
          inativos: filtros.value.inativos ? 1 : undefined,
        },
      })
      painel.value = data.data
    } catch (e) {
      notifyError(e, 'Erro ao buscar os portadores')
    } finally {
      carregando.value = false
    }
  }

  function limpar() {
    filtros.value = filtrosVazios()
  }

  // ---- cadastro ----
  const form = ref(vazio())
  const dialog = ref(false)
  const salvando = ref(false)
  const isNovo = computed(() => !form.value.codportador)

  function novo() {
    form.value = vazio()
    dialog.value = true
  }

  function editar(p) {
    form.value = Object.fromEntries(Object.keys(vazio()).map((k) => [k, p[k] ?? vazio()[k]]))
    form.value.emiteboleto = !!p.emiteboleto
    dialog.value = true
  }

  // o cadastro alterado vale também para a tela do portador aberta
  function aplicar(p) {
    const sPeriodo = periodoStore()
    if (sPeriodo.portador?.codportador === p.codportador) {
      sPeriodo.portador = { ...p, ehGaveta: p.gaveta }
    }
  }

  async function salvar() {
    salvando.value = true
    try {
      const { codportador, ...dados } = form.value
      dados.pixdict = dados.pixdict || null
      const { data } = codportador
        ? await api.put(`v1/portador/${codportador}`, dados)
        : await api.post('v1/portador', dados)
      aplicar(data.data)
      notifySuccess(codportador ? 'Portador atualizado' : 'Portador criado')
      dialog.value = false
      buscarPainel()
    } catch (e) {
      notifyError(e, 'Erro ao salvar o portador')
    } finally {
      salvando.value = false
    }
  }

  async function alternarInativo(p) {
    try {
      const { data } = p.inativo
        ? await api.delete(`v1/portador/${p.codportador}/inativo`)
        : await api.post(`v1/portador/${p.codportador}/inativo`)
      aplicar(data.data)
      notifySuccess(data.data.inativo ? 'Portador inativado' : 'Portador reativado')
    } catch (e) {
      notifyError(e, 'Erro ao alterar o status')
    }
  }

  // com movimento o servidor recusa (só inativar)
  async function excluir(p) {
    try {
      await api.delete(`v1/portador/${p.codportador}`)
      notifySuccess('Portador excluído')
      buscarPainel()
      return true
    } catch (e) {
      notifyError(e, 'Erro ao excluir')
      return false
    }
  }

  // ---- OFX ----
  const dialogOfx = ref(false)

  return {
    filtros,
    painel,
    carregando,
    filiais,
    buscarPainel,
    limpar,
    form,
    dialog,
    salvando,
    isNovo,
    novo,
    editar,
    salvar,
    alternarInativo,
    excluir,
    dialogOfx,
  }
})
