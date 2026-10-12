import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { notifySuccess, notifyError } from 'src/utils/notify'
import { useMaquinetaPeriodoStore } from 'src/stores/maquinetaPeriodoStore'

// Domínio maquineta: listagem (por filial e adquirente, como o painel do portador), cadastro
// (manual, Stone integrada, SafraPay pelo QR), parear de novo, juntar e inativar. A lista abre a
// maquineta; as ações ficam no cabeçalho dela.

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

    // filial → adquirente → maquinetas; a compartilhada fica na filial do cadastro
    const filiais = computed(() => {
      const porFilial = new Map()
      for (const m of items.value) {
        const f = porFilial.get(m.codfilial) ?? {
          codfilial: m.codfilial,
          filial: m.filial,
          adquirentes: new Map(),
        }
        porFilial.set(m.codfilial, f)
        const a = f.adquirentes.get(m.codpessoa) ?? {
          codpessoa: m.codpessoa,
          adquirente: m.adquirente,
          maquinetas: [],
        }
        f.adquirentes.set(m.codpessoa, a)
        a.maquinetas.push(m)
      }
      const nome = (x) => x ?? ''
      return [...porFilial.values()].map((f) => ({
        ...f,
        adquirentes: [...f.adquirentes.values()].sort((a, b) =>
          nome(a.adquirente).localeCompare(nome(b.adquirente)),
        ),
      }))
    })

    async function fetchItems() {
      loading.value = true
      try {
        const { data } = await api.get('v1/maquineta', { params: filters.value })
        items.value = data.data || []
      } catch (e) {
        notifyError(e, 'Erro ao carregar as maquinetas')
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

    // o cadastro alterado vale também para a tela da maquineta aberta
    function upsertLocal(item) {
      const idx = items.value.findIndex((i) => i.codmaquineta === item.codmaquineta)
      if (idx >= 0) items.value.splice(idx, 1, item)
      else items.value.unshift(item)
      useSelectCacheStore().invalidate('maquineta')
      const sPeriodo = useMaquinetaPeriodoStore()
      if (sPeriodo.maquineta?.codmaquineta === item.codmaquineta) sPeriodo.maquineta = item
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
        await fetchItems()
        useSelectCacheStore().invalidate('maquineta')
        const sPeriodo = useMaquinetaPeriodoStore()
        if (sPeriodo.maquineta) sPeriodo.recarregar()
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

    // devolve a maquineta que ficou (a tela da juntada vai para ela)
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
        return data.data
      } catch (e) {
        notifyError(e, 'Erro ao juntar maquinetas')
        return null
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
        return true
      } catch (e) {
        notifyError(e, 'Erro ao excluir')
        return false
      }
    }

    return {
      filters,
      items,
      loading,
      filiais,
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
