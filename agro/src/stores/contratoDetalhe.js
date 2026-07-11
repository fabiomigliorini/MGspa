import { defineStore, acceptHMRUpdate } from 'pinia'
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
  const anexosErro = ref(false)
  const carregando = ref(false)

  // ---- Getters (reconciliação físico/fiscal/financeiro) ----
  // Unidade de trabalho = KG. O contrato negocia em sacas (quantidade) + R$/saca;
  // o embarque grava kg. Ponte: pesosaca da cultura (default 60).
  const pesosaca = computed(() => n(contrato.value?.pesosaca) || 60)
  const volumeemaberto = computed(() => !!contrato.value?.volumeemaberto)
  // Contrato barter = troca de insumos por grão (settlement em insumos): fixação e
  // parcelas viram opcionais e nenhum saldo é cobrado como pendência. O flag vive
  // no contrato (tblcontrato.barter); o tipo derivado reflete BARTER.
  const barter = computed(() => !!contrato.value?.barter)
  const contratado = computed(() => n(contrato.value?.quantidade)) // sc negociadas
  const contratadokg = computed(() => n(contrato.value?.contratadokg))
  const carregadokg = computed(() => n(contrato.value?.carregadokg))
  const carregadosc = computed(() => n(contrato.value?.carregadosc))
  // saldo em kg; null/sem teto quando volumeemaberto (leva o saldo do silo).
  const saldokg = computed(() =>
    volumeemaberto.value ? null : Math.max(0, contratadokg.value - carregadokg.value),
  )
  const valornf = computed(() => n(contrato.value?.valornf))

  // Ordem estável: data da fixação asc, depois codcontratofixacao asc (não
  // depende da ordem que o backend devolve — editar não reordena os cards).
  const fixacoes = computed(() =>
    (contrato.value?.ContratoFixacaoS || [])
      .filter((f) => !f.inativo)
      .sort((a, b) => {
        const da = (a.data || '').slice(0, 10)
        const db = (b.data || '').slice(0, 10)
        if (da !== db) return da < db ? -1 : 1
        return n(a.codcontratofixacao) - n(b.codcontratofixacao)
      }),
  )
  // Recebimentos = achatado das fixações (o ledger por fixação vem do backend:
  // recebido / saldoreceber / diferenca / quitado / statusrecebimento).
  const recebimentos = computed(() =>
    fixacoes.value.flatMap((f) => (f.ContratoPagamentoS || []).filter((p) => !p.inativo)),
  )
  const notasPlano = computed(() =>
    (contrato.value?.ContratoNotaS || []).filter((nt) => !nt.inativo),
  )
  const entregas = computed(() => contrato.value?.MovimentoGraoS || [])

  const fixado = computed(() => n(contrato.value?.fixado))
  const afixar = computed(() => contratado.value - fixado.value)
  // Preço médio R$/saca FIRME (só o câmbio travado; US$ flutuante entra como 0).
  const precoMedio = computed(() => {
    const q = fixacoes.value.reduce((s, f) => s + n(f.quantidade), 0)
    const v = fixacoes.value.reduce((s, f) => s + n(f.totalbrl), 0)
    return q > 0 ? v / q : 0
  })
  // precoMedio é R$/saca; o carregado físico está em sacas derivadas.
  const valorCarregado = computed(() => carregadosc.value * precoMedio.value)
  // Total recebido no contrato = Σ recebido de cada fixação (ledger do backend).
  const pago = computed(() => fixacoes.value.reduce((s, f) => s + n(f.recebido), 0))

  // R$ firme fixado (Σ totalbrl) = câmbio travado + fixações BRL (US$ flutuante = 0).
  const valorFixadoBruto = computed(() => fixacoes.value.reduce((s, f) => s + n(f.totalbrl), 0))
  const liquidoBrl = computed(() => fixacoes.value.reduce((s, f) => s + n(f.liquidobrl), 0))
  // A receber = Σ saldo a receber das fixações (líquido − recebido, só o que falta).
  const saldoReceber = computed(() =>
    fixacoes.value.reduce((s, f) => s + Math.max(0, n(f.saldoreceber)), 0),
  )

  // Símbolo por moeda (só apresentação; toda a agregação vem do backend).
  const SIMBOLOS_MOEDA = { BRL: 'R$', USD: 'US$', EUR: '€' }
  function simboloMoeda(iso) {
    return SIMBOLOS_MOEDA[iso] || iso || 'R$'
  }
  // Resumo do fixado por MOEDA (câmbio travado migra p/ R$; total, sacas, preço
  // médio por saca) — PRONTO do backend (ContratoResource::fixadoResumo).
  const fixadoResumo = computed(() => contrato.value?.fixadoresumo || [])

  // `ehUsd` é a fonte única do predicado "é US$?" (exportada p/ os cards).
  function ehUsd(f) {
    return !!f && (f.estrangeira ?? (f.moeda || 'BRL') !== 'BRL')
  }

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
      anexosErro.value = false
    } catch {
      // Falha de carga NÃO é "sem anexos": sinaliza pra UI diferenciar.
      anexos.value = []
      anexosErro.value = true
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
  // Liga/desliga barter (settlement em insumos). Espelha alternarInativo: POST
  // liga / DELETE desliga; muda 1 campo sem reenviar o contrato inteiro.
  async function definirBarter(valor) {
    if (valor) {
      await api.post(`v1/contrato/${cod.value}/barter`)
    } else {
      await api.delete(`v1/contrato/${cod.value}/barter`)
    }
    await carregar()
  }
  async function excluirContrato() {
    await api.delete(`v1/contrato/${cod.value}`)
  }

  // ---- Fixação (exclui aqui; criar/editar passa pelo FixacaoImpostosDialog) ----
  async function excluirFixacao(f) {
    await api.delete(`v1/contrato/${cod.value}/fixacao/${f.codcontratofixacao}`)
    await carregar()
  }

  // ---- Travas de câmbio (aninhadas na fixação; recalculam os totais no backend) ----
  async function salvarCambio(codfixacao, form) {
    const pk = form.codcontratofixacaocambio
    const base = `v1/contrato/${cod.value}/fixacao/${codfixacao}/cambio`
    if (pk) {
      await api.put(`${base}/${pk}`, form)
    } else {
      await api.post(base, form)
    }
    await carregar()
  }
  async function excluirCambio(codfixacao, c) {
    await api.delete(
      `v1/contrato/${cod.value}/fixacao/${codfixacao}/cambio/${c.codcontratofixacaocambio}`,
    )
    await carregar()
  }

  // ---- Recebimentos (aninhados na fixação) + quitação ----
  async function salvarRecebimento(codfixacao, form) {
    const pk = form.codcontratopagamento
    const base = `v1/contrato/${cod.value}/fixacao/${codfixacao}/pagamento`
    if (pk) {
      await api.put(`${base}/${pk}`, form)
    } else {
      await api.post(base, form)
    }
    await carregar()
  }
  async function excluirRecebimento(codfixacao, p) {
    await api.delete(
      `v1/contrato/${cod.value}/fixacao/${codfixacao}/pagamento/${p.codcontratopagamento}`,
    )
    await carregar()
  }
  // Quitar/reabrir a fixação (recebida mesmo com diferencinha).
  async function quitarFixacao(codfixacao, quitar) {
    if (quitar) {
      await api.post(`v1/contrato/${cod.value}/fixacao/${codfixacao}/quitar`)
    } else {
      await api.delete(`v1/contrato/${cod.value}/fixacao/${codfixacao}/quitar`)
    }
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
    anexosErro,
    carregando,
    // getters
    pesosaca,
    volumeemaberto,
    barter,
    contratado,
    contratadokg,
    carregadokg,
    carregadosc,
    saldokg,
    valornf,
    fixacoes,
    recebimentos,
    notasPlano,
    entregas,
    fixado,
    afixar,
    precoMedio,
    valorCarregado,
    pago,
    valorFixadoBruto,
    liquidoBrl,
    saldoReceber,
    fixadoResumo,
    simboloMoeda,
    difNf,
    difPago,
    bate,
    // contrato dolarizado (predicado)
    ehUsd,
    // actions
    carregar,
    carregarAnexos,
    alternarInativo,
    definirBarter,
    excluirContrato,
    excluirFixacao,
    salvarCambio,
    excluirCambio,
    salvarRecebimento,
    excluirRecebimento,
    quitarFixacao,
    salvarNota,
    excluirNota,
    enviarAnexo,
    excluirAnexo,
    baixarAnexo,
    urlAnexoDownload,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useContratoDetalheStore, import.meta.hot))
}
