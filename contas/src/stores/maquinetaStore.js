import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { notifySuccess, notifyError } from 'src/utils/notify'

// Domínio maquineta: listagem, cadastro (manual, Stone integrada, SafraPay pelo QR),
// parear de novo, juntar e inativar.

const defaultFilters = () => ({
  texto: null,
  codfilial: null,
  codpessoa: null,
  integracao: null,
  inativo: false,
})

const modeloVazio = () => ({
  codmaquineta: null,
  integracao: 'M',
  apelido: '',
  serial: '',
  codfilial: null,
  compartilhada: false,
  codpessoa: null,
})

export const useMaquinetaStore = defineStore(
  'maquineta',
  () => {
    // ---- listagem ----
    const filters = ref(defaultFilters())
    const items = ref([])
    const loading = ref(false)
    const page = ref(1)
    const hasMore = ref(true)
    const adquirentes = ref([])

    const activeFiltersCount = computed(() => {
      const f = filters.value
      let count = 0
      if (f.texto) count++
      if (f.codfilial) count++
      if (f.codpessoa) count++
      if (f.integracao) count++
      if (f.inativo !== false) count++
      return count
    })

    async function fetchItems(reset = false) {
      if (reset) {
        page.value = 1
        hasMore.value = true
      }
      if (!hasMore.value || loading.value) return

      loading.value = true
      try {
        const params = { ...filters.value, page: page.value }
        const { data } = await api.get('v1/maquineta', { params })
        const rows = data.data || []
        items.value = reset ? rows : [...items.value, ...rows]
        hasMore.value = page.value < (data.meta?.last_page ?? page.value)
        page.value++
      } finally {
        loading.value = false
      }
    }

    async function carregarAdquirentes() {
      const { data } = await api.get('v1/maquineta/adquirente')
      adquirentes.value = data.map((a) => ({ value: a.codpessoa, label: a.fantasia }))
    }

    function clearFilters() {
      filters.value = defaultFilters()
    }

    function upsertLocal(item) {
      const idx = items.value.findIndex((i) => i.codmaquineta === item.codmaquineta)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
      useSelectCacheStore().invalidate('maquineta')
    }

    function removeLocal(codmaquineta) {
      items.value = items.value.filter((i) => i.codmaquineta !== codmaquineta)
      useSelectCacheStore().invalidate('maquineta')
    }

    // ---- cadastro ----
    const dialog = ref(false)
    const model = ref(modeloVazio())
    const registro = ref(null)
    const salvando = ref(false)
    const isNovo = computed(() => !model.value.codmaquineta)

    // SafraPay: QR do PDV Saurus que o pinpad lê
    const qr = ref({ pdv_uuid: null, qrcode: null })

    function abrirNovo() {
      model.value = modeloVazio()
      registro.value = null
      qr.value = { pdv_uuid: null, qrcode: null }
      dialog.value = true
    }

    function abrirEditar(row) {
      model.value = {
        codmaquineta: row.codmaquineta,
        integracao: row.integracao || 'M',
        apelido: row.apelido,
        serial: row.serial || '',
        codfilial: row.codfilial,
        compartilhada: !!row.compartilhada,
        codpessoa: row.codpessoa,
      }
      registro.value = row
      qr.value = { pdv_uuid: null, qrcode: null }
      dialog.value = true
    }

    function payload() {
      const m = model.value
      return {
        integracao: m.integracao === 'M' ? null : m.integracao,
        apelido: m.apelido,
        serial: m.serial || null,
        codfilial: m.codfilial,
        compartilhada: m.compartilhada,
        codpessoa: m.integracao === 'M' ? m.codpessoa : null,
      }
    }

    async function salvar() {
      salvando.value = true
      try {
        const { data } = isNovo.value
          ? await api.post('v1/maquineta', payload())
          : await api.put(`v1/maquineta/${model.value.codmaquineta}`, payload())
        upsertLocal(data.data)
        notifySuccess(isNovo.value ? 'Maquineta criada' : 'Maquineta atualizada')
        dialog.value = false
      } catch (e) {
        notifyError(e, 'Erro ao salvar maquineta')
      } finally {
        salvando.value = false
      }
    }

    async function gerarQrCode(codmaquineta = null) {
      salvando.value = true
      try {
        const { data } = await api.post('v1/maquineta/saurus/qrcode', {
          codmaquineta,
          pdv_uuid: qr.value.pdv_uuid,
          apelido: model.value.apelido,
          codfilial: model.value.codfilial,
        })
        qr.value = data
      } catch (e) {
        notifyError(e, 'Erro ao gerar o QR Code')
      } finally {
        salvando.value = false
      }
    }

    async function confirmarLeitura() {
      salvando.value = true
      try {
        const { data } = await api.post('v1/maquineta/saurus/confirmar', {
          pdv_uuid: qr.value.pdv_uuid,
        })
        notifySuccess(`Maquineta ${data.data.apelido} pareada`)
        dialog.value = false
        dialogParear.value = false
        // pinpad anterior do mesmo PDV Saurus foi inativado: recarrega
        await fetchItems(true)
        useSelectCacheStore().invalidate('maquineta')
      } catch (e) {
        notifyError(e, 'Erro ao confirmar a leitura')
      } finally {
        salvando.value = false
      }
    }

    // ---- parear de novo (SafraPay) ----
    const dialogParear = ref(false)

    async function abrirParear(row) {
      registro.value = row
      qr.value = { pdv_uuid: null, qrcode: null }
      dialogParear.value = true
      await gerarQrCode(row.codmaquineta)
    }

    // ---- juntar (manual criada por serial errado) ----
    const dialogJuntar = ref(false)
    const juntarDestino = ref(null)

    function abrirJuntar(row) {
      registro.value = row
      juntarDestino.value = null
      dialogJuntar.value = true
    }

    async function juntar() {
      salvando.value = true
      try {
        const { data } = await api.post(`v1/maquineta/${registro.value.codmaquineta}/juntar`, {
          codmaquinetadestino: juntarDestino.value,
        })
        removeLocal(registro.value.codmaquineta)
        upsertLocal(data.data)
        notifySuccess(`Juntada em ${data.data.apelido}`)
        dialogJuntar.value = false
      } catch (e) {
        notifyError(e, 'Erro ao juntar maquinetas')
      } finally {
        salvando.value = false
      }
    }

    // ---- status ----
    async function alternarInativo(row) {
      try {
        const { data } = row.inativo
          ? await api.delete(`v1/maquineta/${row.codmaquineta}/inativo`)
          : await api.post(`v1/maquineta/${row.codmaquineta}/inativo`)
        upsertLocal(data.data)
        notifySuccess(data.data.inativo ? 'Maquineta inativada' : 'Maquineta reativada')
      } catch (e) {
        notifyError(e, 'Erro ao alterar status')
      }
    }

    async function excluir(row) {
      try {
        await api.delete(`v1/maquineta/${row.codmaquineta}`)
        removeLocal(row.codmaquineta)
        notifySuccess('Maquineta excluída')
      } catch (e) {
        notifyError(e, 'Erro ao excluir')
      }
    }

    return {
      filters,
      items,
      loading,
      hasMore,
      adquirentes,
      activeFiltersCount,
      fetchItems,
      carregarAdquirentes,
      clearFilters,
      dialog,
      model,
      registro,
      salvando,
      isNovo,
      qr,
      abrirNovo,
      abrirEditar,
      salvar,
      gerarQrCode,
      confirmarLeitura,
      dialogParear,
      abrirParear,
      dialogJuntar,
      juntarDestino,
      abrirJuntar,
      juntar,
      alternarInativo,
      excluir,
    }
  },
  {
    persist: { pick: ['filters'] },
  },
)
