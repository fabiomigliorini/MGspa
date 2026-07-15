import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'
import { Dialog } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Store do DOMÍNIO safra. UMA store para TODAS as telas de safra (lista e
// detalhe) — não uma store por tela. Todas leem e escrevem aqui, então editar
// numa reflete na outra.
//
// Dona de tudo que é safra: a lista (SafrasPage), a safra aberta no detalhe
// (SafraDetailPage), os KPIs comerciais (GET v1/safra/{cod}/comercial), os
// plantios (sub-entidade) e o estado de form/dialog/salvando de edição. CRUD
// escrito na mão, explícito, sem motor genérico (KISS).
//
// O que NÃO mora aqui (continua na SafraDetailPage): carga offline-first
// (useCargaStore), sincronização (useSincronizacaoStore), contratos
// (ContratosSafra) e as listas de referência (fazendas/variedades/talhões).
export const useSafraStore = defineStore('safra', () => {
  // ======================= SAFRA (raiz) =======================
  const safras = ref([]) // lista (SafrasPage)
  const safra = ref(null) // aberta no detalhe (SafraDetailPage)
  const comercial = ref(null) // KPIs comerciais (contratos da safra), via backend
  const formSafra = ref({}) // registro em edição
  const dialogSafra = ref(false)
  const salvandoSafra = ref(false)

  async function carregarSafras() {
    try {
      const { data } = await api.get('v1/safra')
      safras.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }

  async function carregarSafra(cod) {
    try {
      // A API embrulha objeto único em { data: {...} }; desembrulha.
      const { data } = await api.get(`v1/safra/${cod}`)
      safra.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }

  // KPI comercial degrada em silêncio (offline / sem dado); produção segue.
  async function carregarComercial(cod) {
    try {
      const { data } = await api.get(`v1/safra/${cod}/comercial`)
      comercial.value = data
    } catch {
      // ignora — comercial é opcional
    }
  }

  function novaSafra(defaults = {}) {
    formSafra.value = { ...defaults }
    dialogSafra.value = true
  }
  function editarSafra(s) {
    formSafra.value = { ...s }
    dialogSafra.value = true
  }
  async function salvarSafra() {
    if (salvandoSafra.value) return
    salvandoSafra.value = true
    try {
      const f = formSafra.value
      if (f.codsafra) await api.put(`v1/safra/${f.codsafra}`, f)
      else await api.post('v1/safra', f)
      notifySuccess('Safra salva!')
      dialogSafra.value = false
      await carregarSafras()
      if (safra.value?.codsafra === f.codsafra) await carregarSafra(f.codsafra)
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoSafra.value = false
    }
  }
  async function inativarSafra(s) {
    try {
      if (s.inativo) await api.delete(`v1/safra/${s.codsafra}/inativo`)
      else await api.post(`v1/safra/${s.codsafra}/inativo`)
      await carregarSafras()
      if (safra.value?.codsafra === s.codsafra) await carregarSafra(s.codsafra)
    } catch (e) {
      notifyError(e)
    }
  }
  // Só remove — a tela de detalhe confirma e navega de volta.
  async function excluirSafra(cod) {
    await api.delete(`v1/safra/${cod}`)
  }

  // ======================= PLANTIO (filho) =======================
  // Sub-entidade da safra (v1/safra/{cod}/plantio). A SafraDetailPage lê
  // `plantios` e chama as actions; a produtividade/colhido vem das cargas
  // (useCargaStore) e é cruzada na própria página.
  const plantios = ref([])
  const plantio = ref(null) // plantio único aberto na PlantioDetailPage
  const formPlantio = ref({})
  const dialogPlantio = ref(false)
  const salvandoPlantio = ref(false)

  async function carregarPlantios(codsafra) {
    try {
      const { data } = await api.get(`v1/safra/${codsafra}/plantio`)
      plantios.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }
  // Plantio único (página de detalhe). Traz Safra.Cultura/Fazenda/Variedade + geometria.
  async function carregarPlantio(codsafra, codplantio) {
    try {
      const { data } = await api.get(`v1/safra/${codsafra}/plantio/${codplantio}`)
      plantio.value = data.data ?? data
    } catch (e) {
      notifyError(e)
    }
  }
  function novoPlantio(defaults = {}) {
    formPlantio.value = { ...defaults }
    dialogPlantio.value = true
  }
  function editarPlantio(p) {
    formPlantio.value = { ...p }
    dialogPlantio.value = true
  }
  async function salvarPlantio(codsafra) {
    if (salvandoPlantio.value) return
    salvandoPlantio.value = true
    try {
      const f = formPlantio.value
      if (f.codplantio) await api.put(`v1/safra/${codsafra}/plantio/${f.codplantio}`, f)
      else await api.post(`v1/safra/${codsafra}/plantio`, f)
      notifySuccess('Plantio salvo!')
      dialogPlantio.value = false
      await carregarPlantios(codsafra)
      await carregarComercial(codsafra)
    } catch (e) {
      notifyError(e)
    } finally {
      salvandoPlantio.value = false
    }
  }
  async function inativarPlantio(codsafra, p) {
    try {
      if (p.inativo) await api.delete(`v1/safra/${codsafra}/plantio/${p.codplantio}/inativo`)
      else await api.post(`v1/safra/${codsafra}/plantio/${p.codplantio}/inativo`)
      await carregarPlantios(codsafra)
      await carregarComercial(codsafra)
    } catch (e) {
      notifyError(e)
    }
  }
  // Exclui de fato (sem dialog) + refresca lista/comercial; propaga erro (409 FK)
  // pro caller — usado pela PlantioDetailPage, que confirma e navega de volta.
  async function removerPlantio(codsafra, codplantio) {
    await api.delete(`v1/safra/${codsafra}/plantio/${codplantio}`)
    await carregarPlantios(codsafra)
    await carregarComercial(codsafra)
  }
  function excluirPlantio(codsafra, p) {
    Dialog.create({
      title: 'Excluir',
      message: 'Excluir este plantio?',
      cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
      ok: { label: 'Excluir', color: 'red-5', flat: true },
    }).onOk(async () => {
      try {
        await removerPlantio(codsafra, p.codplantio)
        notifySuccess('Excluído!')
      } catch (e) {
        notifyError(e)
      }
    })
  }

  // Grava só o ha colhido (slider do card) — patcha o plantio local sem recarregar tudo.
  async function salvarHacolhido(codsafra, codplantio, hacolhido) {
    try {
      const { data } = await api.post(`v1/safra/${codsafra}/plantio/${codplantio}/hacolhido`, {
        hacolhido,
      })
      const novo = data.data ?? data
      const i = plantios.value.findIndex((p) => p.codplantio === codplantio)
      if (i >= 0) plantios.value[i] = novo
    } catch (e) {
      notifyError(e)
    }
  }

  return {
    // safra (raiz)
    safras,
    safra,
    comercial,
    formSafra,
    dialogSafra,
    salvandoSafra,
    carregarSafras,
    carregarSafra,
    carregarComercial,
    novaSafra,
    editarSafra,
    salvarSafra,
    inativarSafra,
    excluirSafra,
    // plantio
    plantios,
    plantio,
    formPlantio,
    dialogPlantio,
    salvandoPlantio,
    carregarPlantios,
    carregarPlantio,
    novoPlantio,
    editarPlantio,
    salvarPlantio,
    salvarHacolhido,
    inativarPlantio,
    excluirPlantio,
    removerPlantio,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useSafraStore, import.meta.hot))
}
