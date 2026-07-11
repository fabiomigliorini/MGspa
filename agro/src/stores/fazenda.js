import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { Dialog } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { corTalhao, sugerirCor } from 'src/utils/coresTalhao'

// Store do DOMÍNIO fazenda. UMA store para TODAS as telas de fazenda (lista e
// detalhe) — não uma store por tela. Todas leem e escrevem aqui, então editar
// numa reflete na outra em tempo real.
//
// Dona de tudo que é fazenda: a própria fazenda + seus talhões (sub-entidade) +
// os KPIs do resumo + os dados de mapa (vizinhos). CRUD escrito na mão,
// explícito, sem motor genérico (KISS).
const resumoVazio = () => ({
  ntalhoes: 0,
  areatalhoes: 0,
  areaplantada: 0,
  sacas: 0,
  produtividade: 0,
  safras: [],
})

export const useFazendaStore = defineStore('fazenda', () => {
  // ======================= FAZENDA (raiz) =======================
  const fazendas = ref([]) // lista (FazendasPage)
  const fazenda = ref(null) // aberta no detalhe (FazendaDetailPage)
  const resumo = ref(resumoVazio())
  const formFazenda = ref({}) // registro em edição
  const dialogFazenda = ref(false)
  const salvandoFazenda = ref(false)

  // Lista: cada card da FazendasPage mostra os seus talhões com polígono.
  const todosTalhoes = ref([])
  function talhoesDaFazenda(codfazenda) {
    return todosTalhoes.value.filter((t) => t.codfazenda === codfazenda && t.geometria)
  }

  async function carregarFazendas() {
    try {
      const [{ data: fz }, { data: tlh }] = await Promise.all([
        api.get('v1/fazenda'),
        api.get('v1/talhao'),
      ])
      fazendas.value = fz.data ?? fz
      todosTalhoes.value = tlh.data ?? tlh
    } catch (e) {
      notifyError(e)
    }
  }

  async function carregarFazenda(cod) {
    try {
      const [f, r] = await Promise.all([
        api.get(`v1/fazenda/${cod}`),
        api.get(`v1/fazenda/${cod}/resumo`),
      ])
      fazenda.value = f.data.data ?? f.data
      resumo.value = r.data
    } catch (e) {
      notifyError(e)
    }
  }

  function novaFazenda(defaults = {}) {
    formFazenda.value = { ...defaults }
    dialogFazenda.value = true
  }
  function editarFazenda(f) {
    formFazenda.value = { ...f }
    dialogFazenda.value = true
  }
  async function salvarFazenda() {
    if (salvandoFazenda.value) return
    salvandoFazenda.value = true
    try {
      const f = formFazenda.value
      if (f.codfazenda) await api.put(`v1/fazenda/${f.codfazenda}`, f)
      else await api.post('v1/fazenda', f)
      notifySuccess('Fazenda salva!')
      dialogFazenda.value = false
      await carregarFazendas()
      if (fazenda.value?.codfazenda === f.codfazenda) await carregarFazenda(f.codfazenda)
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoFazenda.value = false
    }
  }
  async function inativarFazenda(f) {
    try {
      if (f.inativo) await api.delete(`v1/fazenda/${f.codfazenda}/inativo`)
      else await api.post(`v1/fazenda/${f.codfazenda}/inativo`)
      await carregarFazendas()
      if (fazenda.value?.codfazenda === f.codfazenda) await carregarFazenda(f.codfazenda)
    } catch (e) {
      notifyError(e)
    }
  }
  // Só remove — a tela de detalhe confirma e navega de volta.
  async function excluirFazenda(cod) {
    await api.delete(`v1/fazenda/${cod}`)
  }

  // ======================= TALHÃO (filho) =======================
  const talhoes = ref([]) // talhões da fazenda aberta (detalhe)
  // Polígonos das demais fazendas — contexto cinza no editor de talhão.
  const outrasFazendas = ref([])
  const formTalhao = ref({})
  const dialogTalhao = ref(false)
  const salvandoTalhao = ref(false)

  // Talhões com geometria (mapa do detalhe) e vizinhos de referência no editor
  // (todos com geo menos o que está aberto no form).
  const talhoesComGeo = computed(() => talhoes.value.filter((t) => t.geometria))
  const referenciaMapa = computed(() =>
    talhoesComGeo.value.filter((t) => t.codtalhao !== formTalhao.value.codtalhao),
  )

  async function carregarTalhoes(codfazenda) {
    try {
      const { data } = await api.get('v1/talhao', { params: { codfazenda } })
      talhoes.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }
  async function carregarOutrasFazendas(codfazenda) {
    try {
      const { data } = await api.get('v1/talhao/mapa', { params: { codfazenda } })
      outrasFazendas.value = (data.data ?? data).map((t) => ({
        codfazenda: t.codfazenda,
        fazenda: t.Fazenda?.fazenda,
        geometria: t.geometria,
      }))
    } catch (e) {
      notifyError(e)
    }
  }

  // Novo começa perto dos vizinhos com uma cor sugerida; editar abre o polígono
  // existente garantindo uma cor visível mesmo p/ talhões antigos sem cor salva.
  function novoTalhao(codfazenda) {
    const usadas = talhoes.value.map((t) => t.cor).filter(Boolean)
    formTalhao.value = {
      codfazenda,
      talhao: '',
      area: 0,
      geometria: null,
      latitude: null,
      longitude: null,
      cor: sugerirCor(usadas),
    }
    dialogTalhao.value = true
  }
  function editarTalhao(t) {
    formTalhao.value = { ...t, cor: corTalhao(t) }
    dialogTalhao.value = true
  }
  async function salvarTalhao() {
    if (salvandoTalhao.value) return
    salvandoTalhao.value = true
    try {
      const f = formTalhao.value
      if (f.codtalhao) await api.put(`v1/talhao/${f.codtalhao}`, f)
      else await api.post('v1/talhao', f)
      notifySuccess('Talhão salvo!')
      dialogTalhao.value = false
      await Promise.all([carregarTalhoes(f.codfazenda), carregarResumo(f.codfazenda)])
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoTalhao.value = false
    }
  }
  async function inativarTalhao(t) {
    try {
      if (t.inativo) await api.delete(`v1/talhao/${t.codtalhao}/inativo`)
      else await api.post(`v1/talhao/${t.codtalhao}/inativo`)
      await Promise.all([carregarTalhoes(t.codfazenda), carregarResumo(t.codfazenda)])
    } catch (e) {
      notifyError(e)
    }
  }
  function excluirTalhao(t) {
    Dialog.create({
      title: 'Excluir',
      message: 'Excluir este talhão?',
      cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
      ok: { label: 'Excluir', color: 'red-5', flat: true },
    }).onOk(async () => {
      try {
        await api.delete(`v1/talhao/${t.codtalhao}`)
        notifySuccess('Excluído!')
        await Promise.all([carregarTalhoes(t.codfazenda), carregarResumo(t.codfazenda)])
      } catch (e) {
        notifyError(e)
      }
    })
  }

  // Recarrega só o resumo (KPIs) da fazenda aberta — usado após mexer em talhões.
  async function carregarResumo(codfazenda) {
    try {
      const { data } = await api.get(`v1/fazenda/${codfazenda}/resumo`)
      resumo.value = data
    } catch (e) {
      notifyError(e)
    }
  }

  return {
    // fazenda (raiz)
    fazendas,
    fazenda,
    resumo,
    formFazenda,
    dialogFazenda,
    salvandoFazenda,
    todosTalhoes,
    talhoesDaFazenda,
    carregarFazendas,
    carregarFazenda,
    novaFazenda,
    editarFazenda,
    salvarFazenda,
    inativarFazenda,
    excluirFazenda,
    // talhão (filho)
    talhoes,
    outrasFazendas,
    formTalhao,
    dialogTalhao,
    salvandoTalhao,
    talhoesComGeo,
    referenciaMapa,
    carregarTalhoes,
    carregarOutrasFazendas,
    carregarResumo,
    novoTalhao,
    editarTalhao,
    salvarTalhao,
    inativarTalhao,
    excluirTalhao,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useFazendaStore, import.meta.hot))
}
