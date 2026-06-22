import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from 'src/services/api'

// Store da TELA de detalhe do contrato (store-per-screen): é o dono do registro,
// dos KPIs derivados e de TODAS as operações de dados (GET/POST/PUT/DELETE).
// A página e os cards filhos LEEM daqui (storeToRefs) e chamam as actions —
// nada de :contrato/:cod por prop entre página e cards. As actions fazem a
// chamada e recarregam o contrato; notify/confirm de UI fica nos componentes
// (o store é especialista em GERIR os dados, não na experiência de tela).
function n(v) {
  return Number(v) || 0
}

export const useContratoDetalheStore = defineStore('contratoDetalhe', () => {
  // ---- Estado ----
  const cod = ref(null)
  const contrato = ref(null)
  const anexos = ref([])
  const carregando = ref(false)

  // ---- Getters (reconciliação físico/fiscal/financeiro) ----
  // Unidade de trabalho = KG. O contrato negocia em sacas (quantidade) + R$/saca;
  // o embarque grava kg. Ponte: pesosaca da cultura (default 60).
  const pesosaca = computed(() => n(contrato.value?.pesosaca) || 60)
  const volumeemaberto = computed(() => !!contrato.value?.volumeemaberto)
  const contratado = computed(() => n(contrato.value?.quantidade)) // sc negociadas
  const contratadokg = computed(() => n(contrato.value?.contratadokg))
  const carregadokg = computed(() => n(contrato.value?.carregadokg))
  const carregadosc = computed(() => n(contrato.value?.carregadosc))
  // saldo em kg; null/sem teto quando volumeemaberto (leva o saldo do silo).
  const saldokg = computed(() =>
    volumeemaberto.value ? null : Math.max(0, contratadokg.value - carregadokg.value),
  )
  const valornf = computed(() => n(contrato.value?.valornf))

  const fixacoes = computed(() =>
    (contrato.value?.ContratoFixacaoS || []).filter((f) => !f.inativo),
  )
  const pagamentos = computed(() =>
    (contrato.value?.ContratoPagamentoS || []).filter((p) => !p.inativo),
  )
  const notasPlano = computed(() =>
    (contrato.value?.ContratoNotaS || []).filter((nt) => !nt.inativo),
  )
  const entregas = computed(() => contrato.value?.MovimentoGraoS || [])

  const fixado = computed(() => n(contrato.value?.fixado))
  const afixar = computed(() => contratado.value - fixado.value)
  // Preço médio ponderado das fixações ativas (FIXO também tem fixação-espelho).
  const precoMedio = computed(() => {
    const q = fixacoes.value.reduce((s, f) => s + n(f.quantidade), 0)
    const v = fixacoes.value.reduce((s, f) => s + n(f.quantidade) * n(f.precoreal), 0)
    return q > 0 ? v / q : 0
  })
  // precoMedio é R$/saca; o carregado físico está em sacas derivadas.
  const valorCarregado = computed(() => carregadosc.value * precoMedio.value)
  // Líquido médio/sc do contrato (motor fiscal) — base p/ sugerir valor de parcela.
  const liquidoSc = computed(() => n(contrato.value?.calculo?.liquido))

  const previsto = computed(() => pagamentos.value.reduce((s, p) => s + n(p.valor), 0))
  const pago = computed(() => pagamentos.value.reduce((s, p) => s + n(p.valorrecebido), 0))

  // "Bate?" — valor carregado x NFs x pago (tolerância de centavos)
  const difNf = computed(() => valornf.value - valorCarregado.value)
  const difPago = computed(() => pago.value - valornf.value)
  const bate = computed(
    () => Math.abs(difNf.value) < 1 && Math.abs(difPago.value) < 1 && valornf.value > 0,
  )

  // ---- GET ----
  async function carregar(codArg) {
    if (codArg != null) cod.value = Number(codArg)
    carregando.value = true
    try {
      const { data } = await api.get(`v1/contrato/${cod.value}`)
      contrato.value = data.data ?? data
    } finally {
      carregando.value = false
    }
  }

  async function carregarAnexos() {
    try {
      const { data } = await api.get(`v1/contrato/${cod.value}/anexo`)
      anexos.value = data
    } catch {
      anexos.value = []
    }
  }

  // ---- Contrato (cabeçalho) ----
  async function alternarInativo() {
    if (contrato.value.inativo) {
      await api.delete(`v1/contrato/${cod.value}/inativo`)
    } else {
      await api.post(`v1/contrato/${cod.value}/inativo`)
    }
    await carregar()
  }
  async function excluirContrato() {
    await api.delete(`v1/contrato/${cod.value}`)
  }

  // ---- Fixação (exclui aqui; criar/editar passa pelo MgFixacaoImpostosDialog) ----
  async function excluirFixacao(f) {
    await api.delete(`v1/contrato/${cod.value}/fixacao/${f.codcontratofixacao}`)
    await carregar()
  }

  // ---- Pagamento (parcelas) ----
  async function salvarPagamento(form) {
    const pk = form.codcontratopagamento
    if (pk) {
      await api.put(`v1/contrato/${cod.value}/pagamento/${pk}`, form)
    } else {
      await api.post(`v1/contrato/${cod.value}/pagamento`, form)
    }
    await carregar()
  }
  async function excluirPagamento(p) {
    await api.delete(`v1/contrato/${cod.value}/pagamento/${p.codcontratopagamento}`)
    await carregar()
  }
  async function confirmarRecebimento(form) {
    await api.post(`v1/contrato/${cod.value}/pagamento/${form.codcontratopagamento}/confirmar`, {
      datarecebido: form.datarecebido,
      valorrecebido: form.valorrecebido,
      codportador: form.codportador,
    })
    await carregar()
  }

  // ---- Nota (plano de NF) ----
  async function salvarNota(form) {
    const pk = form.codcontratonota
    if (pk) {
      await api.put(`v1/contrato/${cod.value}/nota/${pk}`, form)
    } else {
      await api.post(`v1/contrato/${cod.value}/nota`, form)
    }
    await carregar()
  }
  async function excluirNota(nt) {
    await api.delete(`v1/contrato/${cod.value}/nota/${nt.codcontratonota}`)
    await carregar()
  }

  // ---- Anexos (PDFs/imagens) ----
  async function enviarAnexo(file) {
    const fd = new FormData()
    fd.append('arquivo', file)
    fd.append('label', file.name)
    await api.post(`v1/contrato/${cod.value}/anexo`, fd)
    await carregarAnexos()
  }
  async function excluirAnexo(a) {
    await api.delete(`v1/contrato/${cod.value}/anexo/${a.nome}`)
    await carregarAnexos()
  }
  async function baixarAnexo(nome, config = {}) {
    const { data } = await api.get(`v1/contrato/${cod.value}/anexo/${nome}/download`, {
      responseType: 'blob',
      ...config,
    })
    return data
  }
  function urlAnexoDownload(nome) {
    return `v1/contrato/${cod.value}/anexo/${nome}/download`
  }

  return {
    // estado
    cod,
    contrato,
    anexos,
    carregando,
    // getters
    pesosaca,
    volumeemaberto,
    contratado,
    contratadokg,
    carregadokg,
    carregadosc,
    saldokg,
    valornf,
    fixacoes,
    pagamentos,
    notasPlano,
    entregas,
    fixado,
    afixar,
    precoMedio,
    valorCarregado,
    liquidoSc,
    previsto,
    pago,
    difNf,
    difPago,
    bate,
    // actions
    carregar,
    carregarAnexos,
    alternarInativo,
    excluirContrato,
    excluirFixacao,
    salvarPagamento,
    excluirPagamento,
    confirmarRecebimento,
    salvarNota,
    excluirNota,
    enviarAnexo,
    excluirAnexo,
    baixarAnexo,
    urlAnexoDownload,
  }
})
