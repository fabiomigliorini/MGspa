import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'
import { Dialog } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Store do DOMÍNIO cultura. UMA store para TODAS as telas de cultura (lista,
// detalhe, variedades, tabela de desconto) — não uma store por tela. Todas leem
// e escrevem aqui, então editar numa reflete na outra em tempo real.
//
// Dona de tudo que é cultura: a própria cultura + suas variedades + suas faixas
// de desconto + os KPIs do detalhe + o cache do IconeCultura. CRUD escrito na
// mão, explícito, sem motor genérico (KISS).
const resumoVazio = () => ({
  nsafras: 0,
  area: 0,
  colhidokg: 0,
  sacas: 0,
  produtividade: 0,
  variedades: [],
})
const emVoo = new Map()

export const useCulturaStore = defineStore('cultura', () => {
  // ======================= CULTURA (raiz) =======================
  const culturas = ref([]) // lista (CulturasPage)
  const cultura = ref(null) // aberta no detalhe (CulturaDetailPage)
  const resumo = ref(resumoVazio())
  const safras = ref([])
  const formCultura = ref({}) // registro em edição
  const dialogCultura = ref(false)
  const salvandoCultura = ref(false)

  async function carregarCulturas() {
    try {
      const { data } = await api.get('v1/cultura')
      culturas.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }

  async function carregarCultura(cod) {
    try {
      const [c, r, sf] = await Promise.all([
        api.get(`v1/cultura/${cod}`),
        api.get(`v1/cultura/${cod}/resumo`),
        api.get('v1/safra', { params: { codcultura: cod, sort: '-anoplantio' } }),
      ])
      cultura.value = c.data.data ?? c.data
      resumo.value = r.data
      safras.value = sf.data.data ?? sf.data
      cache.value[cod] = cultura.value // alimenta o cache do IconeCultura
    } catch (e) {
      notifyError(e)
    }
  }

  function novaCultura(defaults = {}) {
    formCultura.value = { ...defaults }
    dialogCultura.value = true
  }
  function editarCultura(c) {
    formCultura.value = { ...c }
    dialogCultura.value = true
  }
  async function salvarCultura() {
    if (salvandoCultura.value) return
    salvandoCultura.value = true
    try {
      const f = formCultura.value
      if (f.codcultura) await api.put(`v1/cultura/${f.codcultura}`, f)
      else await api.post('v1/cultura', f)
      notifySuccess('Cultura salva!')
      dialogCultura.value = false
      await carregarCulturas()
      if (cultura.value?.codcultura === f.codcultura) await carregarCultura(f.codcultura)
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoCultura.value = false
    }
  }
  async function inativarCultura(c) {
    try {
      if (c.inativo) await api.delete(`v1/cultura/${c.codcultura}/inativo`)
      else await api.post(`v1/cultura/${c.codcultura}/inativo`)
      await carregarCulturas()
      if (cultura.value?.codcultura === c.codcultura) await carregarCultura(c.codcultura)
    } catch (e) {
      notifyError(e)
    }
  }
  // Só remove — a tela de detalhe confirma e navega de volta.
  async function excluirCultura(cod) {
    await api.delete(`v1/cultura/${cod}`)
  }

  // ======================= VARIEDADE (filha) =======================
  const variedades = ref([])
  const formVariedade = ref({})
  const dialogVariedade = ref(false)
  const salvandoVariedade = ref(false)

  async function carregarVariedades(codcultura) {
    try {
      const { data } = await api.get('v1/variedade', { params: { codcultura } })
      variedades.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }
  function novaVariedade(codcultura) {
    formVariedade.value = { codcultura }
    dialogVariedade.value = true
  }
  function editarVariedade(v) {
    formVariedade.value = { ...v }
    dialogVariedade.value = true
  }
  async function salvarVariedade() {
    if (salvandoVariedade.value) return
    salvandoVariedade.value = true
    try {
      const f = formVariedade.value
      if (f.codvariedade) await api.put(`v1/variedade/${f.codvariedade}`, f)
      else await api.post('v1/variedade', f)
      notifySuccess('Variedade salva!')
      dialogVariedade.value = false
      await carregarVariedades(f.codcultura)
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoVariedade.value = false
    }
  }
  async function inativarVariedade(v) {
    try {
      if (v.inativo) await api.delete(`v1/variedade/${v.codvariedade}/inativo`)
      else await api.post(`v1/variedade/${v.codvariedade}/inativo`)
      await carregarVariedades(v.codcultura)
    } catch (e) {
      notifyError(e)
    }
  }
  function excluirVariedade(v) {
    Dialog.create({
      title: 'Excluir',
      message: 'Excluir esta variedade?',
      cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
      ok: { label: 'Excluir', color: 'red-5', flat: true },
    }).onOk(async () => {
      try {
        await api.delete(`v1/variedade/${v.codvariedade}`)
        notifySuccess('Excluído!')
        await carregarVariedades(v.codcultura)
      } catch (e) {
        notifyError(e)
      }
    })
  }

  // ======================= TABELA DE DESCONTO (filha) =======================
  const faixas = ref([])
  const formFaixa = ref({})
  const dialogFaixa = ref(false)
  const salvandoFaixa = ref(false)

  async function carregarFaixas(codcultura, tipo) {
    try {
      const { data } = await api.get('v1/tabela-desconto', {
        params: { codcultura, tipo, sort: 'faixainicio' },
      })
      faixas.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }
  function novaFaixa(codcultura, tipo) {
    formFaixa.value = { codcultura, tipo }
    dialogFaixa.value = true
  }
  function editarFaixa(f) {
    formFaixa.value = { ...f }
    dialogFaixa.value = true
  }
  async function salvarFaixa() {
    if (salvandoFaixa.value) return
    salvandoFaixa.value = true
    try {
      const f = formFaixa.value
      const payload = {
        codcultura: f.codcultura,
        tipo: f.tipo,
        faixainicio: f.faixainicio,
        faixafim: f.faixafim,
        percentualdesconto: f.percentualdesconto,
      }
      if (f.codtabeladesconto) await api.put(`v1/tabela-desconto/${f.codtabeladesconto}`, payload)
      else await api.post('v1/tabela-desconto', payload)
      notifySuccess('Faixa salva!')
      dialogFaixa.value = false
      await carregarFaixas(f.codcultura, f.tipo)
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoFaixa.value = false
    }
  }
  function excluirFaixa(f) {
    Dialog.create({
      title: 'Excluir',
      message: 'Excluir esta faixa?',
      cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
      ok: { label: 'Excluir', color: 'red-5', flat: true },
    }).onOk(async () => {
      try {
        await api.delete(`v1/tabela-desconto/${f.codtabeladesconto}`)
        notifySuccess('Excluído!')
        await carregarFaixas(f.codcultura, f.tipo)
      } catch (e) {
        notifyError(e)
      }
    })
  }

  // ======================= TRIBUTOS DA CULTURA (filha) =======================
  // Config fiscal por cultura (tblculturatributo): quais tributos incidem
  // (FETHAB/IAGRO/SENAR/Funrural) e como calculam. É o que o motor
  // ContratoCalculoService lê pra deduzir o líquido das fixações.
  const culturatributos = ref([])
  const formTributo = ref({})
  const dialogTributo = ref(false)
  const salvandoTributo = ref(false)
  const unidadesReferencia = ref([]) // opções p/ o select de UPF (base UNIDADE)

  async function carregarCulturaTributos(codcultura) {
    try {
      const { data } = await api.get('v1/cultura-tributo', {
        params: { codcultura, sort: 'ordem' },
      })
      culturatributos.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }
  async function carregarUnidadesReferencia() {
    if (unidadesReferencia.value.length) return
    try {
      const { data } = await api.get('v1/unidade-referencia', { params: { sort: 'codigo' } })
      unidadesReferencia.value = (data.data ?? data).map((u) => ({
        value: u.codunidadereferencia,
        label: `${u.codigo} — ${u.descricao}`,
      }))
    } catch (e) {
      notifyError(e)
    }
  }
  function novoTributo(codcultura) {
    const prox = culturatributos.value.reduce((m, t) => Math.max(m, Number(t.ordem) || 0), 0) + 1
    formTributo.value = {
      codcultura,
      base: 'VALOR',
      grupofethab: false,
      funrural: false,
      ordem: prox,
    }
    dialogTributo.value = true
  }
  function editarTributo(t) {
    formTributo.value = { ...t }
    dialogTributo.value = true
  }
  async function salvarTributo() {
    if (salvandoTributo.value) return
    const f = formTributo.value
    if (!f.codtributo) {
      notifyError('Selecione o tributo.')
      return
    }
    salvandoTributo.value = true
    try {
      const payload = {
        codcultura: f.codcultura,
        codtributo: f.codtributo,
        base: f.base,
        codunidadereferencia: f.base === 'UNIDADE' ? f.codunidadereferencia : null,
        percentual: f.percentual,
        grupofethab: !!f.grupofethab,
        funrural: !!f.funrural,
        ordem: f.ordem ?? 0,
      }
      if (f.codculturatributo) await api.put(`v1/cultura-tributo/${f.codculturatributo}`, payload)
      else await api.post('v1/cultura-tributo', payload)
      notifySuccess('Tributo salvo!')
      dialogTributo.value = false
      await carregarCulturaTributos(f.codcultura)
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoTributo.value = false
    }
  }
  function excluirTributo(t) {
    Dialog.create({
      title: 'Excluir',
      message: `Remover ${t.Tributo?.codigo || 'este tributo'} desta cultura?`,
      cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
      ok: { label: 'Excluir', color: 'red-5', flat: true },
    }).onOk(async () => {
      try {
        await api.delete(`v1/cultura-tributo/${t.codculturatributo}`)
        notifySuccess('Excluído!')
        await carregarCulturaTributos(t.codcultura)
      } catch (e) {
        notifyError(e)
      }
    })
  }

  // ======================= Cache p/ IconeCultura =======================
  // Busca uma vez (GET v1/cultura/{cod}) e serve do cache; emVoo evita
  // requisições duplicadas quando várias linhas pedem a mesma cultura.
  const cache = ref({})
  async function buscar(codcultura) {
    if (!codcultura) return null
    if (cache.value[codcultura]) return cache.value[codcultura]
    if (emVoo.has(codcultura)) return emVoo.get(codcultura)

    const promessa = api
      .get(`v1/cultura/${codcultura}`)
      .then(({ data }) => {
        const obj = data.data ?? data
        cache.value[codcultura] = obj
        return obj
      })
      .finally(() => emVoo.delete(codcultura))

    emVoo.set(codcultura, promessa)
    return promessa
  }

  return {
    // cultura (raiz)
    culturas,
    cultura,
    resumo,
    safras,
    formCultura,
    dialogCultura,
    salvandoCultura,
    carregarCulturas,
    carregarCultura,
    novaCultura,
    editarCultura,
    salvarCultura,
    inativarCultura,
    excluirCultura,
    // variedade
    variedades,
    formVariedade,
    dialogVariedade,
    salvandoVariedade,
    carregarVariedades,
    novaVariedade,
    editarVariedade,
    salvarVariedade,
    inativarVariedade,
    excluirVariedade,
    // tabela de desconto
    faixas,
    formFaixa,
    dialogFaixa,
    salvandoFaixa,
    carregarFaixas,
    novaFaixa,
    editarFaixa,
    salvarFaixa,
    excluirFaixa,
    // tributos da cultura
    culturatributos,
    formTributo,
    dialogTributo,
    salvandoTributo,
    unidadesReferencia,
    carregarCulturaTributos,
    carregarUnidadesReferencia,
    novoTributo,
    editarTributo,
    salvarTributo,
    excluirTributo,
    // cache IconeCultura
    cache,
    buscar,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useCulturaStore, import.meta.hot))
}
