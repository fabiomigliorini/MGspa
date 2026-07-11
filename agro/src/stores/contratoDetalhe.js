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
  const pagamentos = computed(() =>
    (contrato.value?.ContratoPagamentoS || []).filter((p) => !p.inativo),
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
  // Líquido médio/sc do contrato (motor fiscal) — base p/ sugerir valor de parcela.
  const liquidoSc = computed(() => n(contrato.value?.calculo?.liquido))

  const previsto = computed(() => pagamentos.value.reduce((s, p) => s + n(p.valor), 0))
  const pago = computed(() => pagamentos.value.reduce((s, p) => s + n(p.valorrecebido), 0))

  // R$ firme fixado (Σ totalbrl) = câmbio travado + fixações BRL (US$ flutuante = 0).
  const valorFixadoBruto = computed(() => fixacoes.value.reduce((s, f) => s + n(f.totalbrl), 0))
  const liquidoBrl = computed(() => fixacoes.value.reduce((s, f) => s + n(f.liquidobrl), 0))
  const saldoPagar = computed(() => Math.max(0, valorFixadoBruto.value - previsto.value))

  // ===== Fixado POR MOEDA (fluxo de caixa: não mistura US$ com R$) =====
  // Cada linha = uma moeda com o total fixado (na moeda) e o saldo ainda a travar
  // câmbio. O R$ firme (totalbrl/liquidobrl) soma global; a moeda estrangeira fica
  // separada por iso. É o "tenho US$ X a receber E R$ Y travado".
  const SIMBOLOS_MOEDA = { BRL: 'R$', USD: 'US$', EUR: '€' }
  function simboloMoeda(iso) {
    return SIMBOLOS_MOEDA[iso] || iso || 'R$'
  }
  const fixadoPorMoeda = computed(() => {
    const m = {}
    fixacoes.value.forEach((f) => {
      const iso = f.moeda || 'BRL'
      if (!m[iso]) m[iso] = { iso, simbolo: simboloMoeda(iso), totalmoeda: 0, saldomoeda: 0 }
      m[iso].totalmoeda += n(f.totalmoeda)
      m[iso].saldomoeda += n(f.saldomoeda)
    })
    return Object.values(m).filter((x) => x.totalmoeda > 0)
  })
  // Só as moedas estrangeiras com saldo de câmbio ainda a travar.
  const saldoTravarPorMoeda = computed(() =>
    fixadoPorMoeda.value.filter((x) => x.iso !== 'BRL' && x.saldomoeda > 0.005),
  )

  // ===== Contrato dolarizado (multi-fixação + US$) =====
  // `ehUsd` é a fonte única do predicado "é US$?" (exportada p/ os cards). A parcela
  // acha sua fixação (moeda/preço) por `fixacaoDaParcela`; e o saldo de sacas a
  // parcelar de cada fixação vem pronto do ledger do backend (ContratoFixacaoResource).
  function ehUsd(f) {
    return !!f && (f.estrangeira ?? (f.moeda || 'BRL') !== 'BRL')
  }

  // Mapa codcontratofixacao -> fixação (p/ achar a moeda/preço de cada parcela).
  const fixacaoPorCod = computed(() => {
    const m = {}
    fixacoes.value.forEach((f) => {
      m[f.codcontratofixacao] = f
    })
    return m
  })
  function fixacaoDaParcela(p) {
    return fixacaoPorCod.value[p?.codcontratofixacao] || null
  }

  // Sacas disponíveis da fixação (o ledger de pagamento será refeito; por ora,
  // a quantidade cheia). TODO: recompor no refactor de pagamentos.
  function saldoSacasFixacao(f) {
    return n(f?.quantidade)
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
  // POST de uma parcela SEM recarregar: usado pelo gerador de lote, que faz um
  // único carregar() no fim (via @saved). Mantém o lote no padrão store-per-screen.
  async function criarPagamento(payload) {
    await api.post(`v1/contrato/${cod.value}/pagamento`, payload)
  }
  async function excluirPagamento(p) {
    await api.delete(`v1/contrato/${cod.value}/pagamento/${p.codcontratopagamento}`)
    await carregar()
  }
  async function confirmarRecebimento(form) {
    await api.post(`v1/contrato/${cod.value}/pagamento/${form.codcontratopagamento}/confirmar`, {
      datarecebido: form.datarecebido,
      // US$: o servidor computa valorrecebido a partir da cotação; BRL manda o valor.
      valorrecebido: form.valorrecebido,
      cotacaorecebido: form.cotacaorecebido,
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
    valorFixadoBruto,
    liquidoBrl,
    fixadoPorMoeda,
    saldoTravarPorMoeda,
    simboloMoeda,
    saldoPagar,
    difNf,
    difPago,
    bate,
    // contrato dolarizado (predicado + helpers de fixação por parcela)
    ehUsd,
    fixacaoDaParcela,
    saldoSacasFixacao,
    // actions
    carregar,
    carregarAnexos,
    alternarInativo,
    definirBarter,
    excluirContrato,
    excluirFixacao,
    salvarCambio,
    excluirCambio,
    salvarPagamento,
    criarPagamento,
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

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useContratoDetalheStore, import.meta.hot))
}
