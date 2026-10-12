import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Store da TELA Estoque & Extrato (store-per-screen): dona de TODOS os dados do
// dashboard — lista de safras p/ o seletor, safra selecionada, KPIs comerciais,
// saldos por unidade, extrato de movimentos, e os cadastros (unidades,
// contratos, plantios) que alimentam os selects do ajuste manual. Também é dona
// do form/dialog do movimento e do seu CRUD. A página LÊ daqui (storeToRefs) e
// chama as actions; sem api.get direto na página. CRUD na mão, sem motor
// genérico (KISS).
export const useExtratoStore = defineStore('extrato', () => {
  // ---- Estado ----
  const safras = ref([])
  const codsafra = ref(null)
  const kpis = ref(null)
  const saldos = ref([])
  const extrato = ref([])
  const unidades = ref([])
  const contratos = ref([])
  const plantios = ref([])
  const carregando = ref(false)

  // form/dialog do ajuste manual
  const dialog = ref(false)
  const salvando = ref(false)
  const form = ref({})

  // ---- Getters derivados ----
  const safraSel = computed(() => safras.value.find((s) => s.codsafra === codsafra.value) || null)
  const pesosaca = computed(() => safraSel.value?.Cultura?.pesosaca || 60)
  // Retirada de silo não tem desconto (o servidor grava −quantidade).
  const retirada = computed(
    () => form.value.contatipo === 'UNIDADE' && form.value.papel === 'ORIGEM',
  )
  const liquidoForm = computed(
    () => Number(form.value.bruto || 0) - (retirada.value ? 0 : Number(form.value.desconto || 0)),
  )

  // Todas as páginas (o Laravel pagina em 50): senão o extrato e os selects
  // paravam no 50º registro.
  async function todasPaginas(url, params = {}) {
    const todos = []
    let page = 1
    let last = 1
    do {
      const { data } = await api.get(url, { params: { ...params, page } })
      todos.push(...(data.data ?? data))
      last = data?.meta?.last_page ?? data?.last_page ?? 1
      page++
    } while (page <= last)
    return todos
  }

  // ---- GET ----
  async function carregarSafras() {
    const { data } = await api.get('v1/safra')
    safras.value = data.data ?? data
    if (!codsafra.value && safras.value.length) {
      codsafra.value = (safras.value.find((s) => !s.inativo) || safras.value[0]).codsafra
    }
  }

  async function carregarDados() {
    if (!codsafra.value) return
    carregando.value = true
    try {
      const [k, s, e] = await Promise.all([
        api.get(`v1/safra/${codsafra.value}/comercial`),
        api.get('v1/movimento-grao/saldos-unidades', { params: { codsafra: codsafra.value } }),
        todasPaginas('v1/movimento-grao', {
          codsafra: codsafra.value,
          sort: '-data,-codmovimentograo',
        }),
      ])
      kpis.value = k.data
      saldos.value = s.data
      extrato.value = e
    } catch (err) {
      notifyError(err)
    } finally {
      carregando.value = false
    }
  }

  async function carregarCadastros() {
    ;[unidades.value, contratos.value] = await Promise.all([
      todasPaginas('v1/unidade-armazenadora'),
      todasPaginas('v1/contrato'),
    ])
  }

  // Recarrega os plantios da safra selecionada (selects do ajuste) e os dados do
  // dashboard. Chamado pela página no watch de codsafra.
  async function selecionarSafra(cod) {
    if (!cod) return
    plantios.value = await todasPaginas(`v1/safra/${cod}/plantio`)
    await carregarDados()
  }

  // ---- Ajuste manual (CRUD movimento) ----
  function novoAjuste() {
    form.value = {
      codsafra: codsafra.value,
      papel: 'DESTINO',
      contatipo: 'UNIDADE',
      codplantio: null,
      codunidadearmazenadora: null,
      codcontrato: null,
      bruto: 0,
      desconto: 0,
      observacao: null,
    }
    dialog.value = true
  }

  async function salvarAjuste() {
    if (salvando.value) return
    salvando.value = true
    try {
      const desconto = retirada.value ? 0 : form.value.desconto
      await api.post('v1/movimento-grao', { ...form.value, desconto, liquido: liquidoForm.value })
      notifySuccess('Ajuste lançado')
      dialog.value = false
      await carregarDados()
    } catch (e) {
      notifyError(e)
    } finally {
      salvando.value = false
    }
  }

  async function estornar(m) {
    try {
      await api.post(`v1/movimento-grao/${m.codmovimentograo}/inativo`)
      notifySuccess('Lançamento estornado')
      await carregarDados()
    } catch (e) {
      notifyError(e)
    }
  }

  return {
    // estado
    safras,
    codsafra,
    kpis,
    saldos,
    extrato,
    unidades,
    contratos,
    plantios,
    carregando,
    dialog,
    salvando,
    form,
    // getters
    safraSel,
    pesosaca,
    retirada,
    liquidoForm,
    // actions
    carregarSafras,
    carregarDados,
    carregarCadastros,
    selecionarSafra,
    novoAjuste,
    salvarAjuste,
    estornar,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useExtratoStore, import.meta.hot))
}
