import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { uid } from 'quasar'
import { db } from 'boot/db'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import { calcularCarga } from 'src/utils/desconto'
import { notifyError } from 'src/utils/notify'

// Etapas por sentido — controlam as colunas do board e a ordem de pesagem.
// ENTRADA chega cheio (pesa PBT antes); SAIDA chega vazio (pesa tara antes).
export const ETAPAS_POR_SENTIDO = {
  ENTRADA: ['PBT', 'CLASSIFICACAO', 'TARA', 'FINALIZADO'],
  SAIDA: ['TARA', 'PBT', 'FISCAL', 'FINALIZADO'],
  TRANSFERENCIA: ['PBT', 'TARA', 'FINALIZADO'],
}

export const SENTIDOS = [
  { value: 'ENTRADA', label: 'Recebimento', icon: 'local_shipping', color: 'green-7' },
  { value: 'SAIDA', label: 'Expedição', icon: 'outbound', color: 'green-8' },
  { value: 'TRANSFERENCIA', label: 'Transferência', icon: 'swap_horiz', color: 'blue-grey-7' },
]

// Store da Carga unificada (pátio) — lê/grava no Dexie (offline-first) e dispara
// a sincronização em background. O extrato (saldos) é gerado no servidor; aqui
// cacheamos os saldos por unidade p/ exibir/avisar offline.
export const useCargaStore = defineStore('carga', () => {
  const sincronizacao = useSincronizacaoStore()

  const cargas = ref([])
  const safras = ref([])
  const plantios = ref([])
  const culturas = ref([])
  const variedades = ref([])
  const faixas = ref([])
  const veiculos = ref([])
  const unidades = ref([])
  const contratos = ref([])
  const saldosUnidades = ref([]) // snapshot do servidor [{codunidadearmazenadora, saldokg, ...}]
  const codsafraAtiva = ref(null)
  const sentidoAtivo = ref('ENTRADA')

  const veiculosAtivos = computed(() => veiculos.value.filter((v) => !v.inativo))
  const veiculoPorId = (codveiculo) =>
    veiculos.value.find((v) => v.codveiculo === codveiculo) || null
  const unidadesAtivas = computed(() => unidades.value.filter((u) => !u.inativo))
  const contratosAtivos = computed(() => contratos.value.filter((c) => !c.inativo))

  const safraAtiva = computed(
    () => safras.value.find((s) => s.codsafra === codsafraAtiva.value) || null,
  )
  const culturaAtiva = computed(
    () => culturas.value.find((c) => c.codcultura === safraAtiva.value?.codcultura) || null,
  )
  const faixasDaSafra = computed(() =>
    faixas.value.filter((f) => f.codcultura === safraAtiva.value?.codcultura),
  )
  const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)

  const plantiosDaSafra = computed(() =>
    plantios.value
      .filter((p) => p.codsafra === codsafraAtiva.value && !p.inativo)
      .map((p) => ({
        ...p,
        rotulo: `${p.talhao ?? 'Talhão ' + p.codplantio}${
          p.Variedade?.variedade ? ' — ' + p.Variedade.variedade : ''
        }`,
      })),
  )

  const etapasDoSentido = computed(() => ETAPAS_POR_SENTIDO[sentidoAtivo.value] || [])

  // Cargas do sentido ativo agrupadas por etapa (board).
  const cargasPorEtapa = computed(() => {
    const grupos = {}
    for (const e of etapasDoSentido.value) grupos[e] = []
    for (const c of cargas.value) {
      if (c.inativo || c.sentido !== sentidoAtivo.value) continue
      if (grupos[c.etapa]) grupos[c.etapa].push(c)
    }
    return grupos
  })

  // Colhido (kg líquido) por plantio — soma os pontos PLANTIO (origem) das
  // cargas finalizadas/ativas. Base do cálculo de produtividade offline.
  const colhidoPorPlantio = computed(() => {
    const mapa = {}
    for (const c of cargas.value) {
      if (c.inativo || c.etapa !== 'FINALIZADO') continue
      for (const p of c.pontos || []) {
        if (p.contatipo === 'PLANTIO' && p.codplantio) {
          mapa[p.codplantio] = (mapa[p.codplantio] || 0) + (Number(p.liquido) || 0)
        }
      }
    }
    return mapa
  })

  // Resumo de produtividade da safra (por plantio + totais) — usado na home e no
  // detalhe da safra. Colhido vem das contas PLANTIO (colhidoPorPlantio).
  const produtividade = computed(() => {
    const porPlantio = plantiosDaSafra.value.map((p) => {
      const colhidoKg = colhidoPorPlantio.value[p.codplantio] || 0
      const area = Number(p.areaplantada) || 0
      const sc = colhidoKg / pesosaca.value
      return { ...p, colhidoKg, sacas: sc, produtividade: area > 0 ? sc / area : 0 }
    })
    const areaTotal = porPlantio.reduce((s, p) => s + (Number(p.areaplantada) || 0), 0)
    const colhidoKg = porPlantio.reduce((s, p) => s + p.colhidoKg, 0)
    const sc = colhidoKg / pesosaca.value
    return {
      porPlantio,
      areaTotal,
      colhidoKg,
      sacas: sc,
      produtividadeMedia: areaTotal > 0 ? sc / areaTotal : 0,
    }
  })

  // ---- Saldos offline (snapshot do servidor + pendências locais) ----
  // Soma o líquido das cargas locais ainda NÃO sincronizadas (finalizadas) que
  // movem uma conta, com o sinal (UNIDADE: +destino/-origem; CONTRATO sempre +).
  function deltaPendente(contatipo, campoCod, cod) {
    let delta = 0
    for (const c of cargas.value) {
      if (c.inativo || c.sincronizado || c.etapa !== 'FINALIZADO') continue
      for (const p of c.pontos || []) {
        if (p.contatipo !== contatipo || p[campoCod] !== cod) continue
        const sinal = contatipo === 'UNIDADE' && p.papel === 'ORIGEM' ? -1 : 1
        delta += sinal * (Number(p.liquido) || 0)
      }
    }
    return delta
  }

  function saldoUnidadeOffline(cod) {
    const snap = saldosUnidades.value.find((s) => s.codunidadearmazenadora === cod)
    return (
      (snap ? Number(snap.saldokg) || 0 : 0) +
      deltaPendente('UNIDADE', 'codunidadearmazenadora', cod)
    )
  }

  // Saldo a entregar de um contrato (kg): snapshot do servidor menos o que as
  // cargas locais pendentes já comprometeram. Infinity = volume em aberto.
  function saldoContratoOffline(cod) {
    const c = contratos.value.find((x) => x.codcontrato === cod)
    if (!c) return Infinity
    if (c.volumeemaberto) return Infinity
    const base = c.saldokg != null ? Number(c.saldokg) : Infinity
    if (!isFinite(base)) return Infinity
    return base - deltaPendente('CONTRATO', 'codcontrato', cod)
  }

  function rotuloContrato(codcontrato) {
    const c = contratos.value.find((x) => x.codcontrato === codcontrato)
    if (!c) return `Contrato ${codcontrato}`
    const pessoa = c.Pessoa?.fantasia || c.Pessoa?.pessoa || ''
    return pessoa ? `${c.contrato} — ${pessoa}` : `${c.contrato}`
  }

  async function carregarReferencias() {
    safras.value = await db.safra.toArray()
    culturas.value = await db.cultura.toArray()
    variedades.value = await db.variedade.toArray()
    faixas.value = await db.tabeladesconto.toArray()
    plantios.value = await db.plantio.toArray()
    veiculos.value = await db.veiculo.toArray()
    unidades.value = await db.unidadearmazenadora.toArray()
    contratos.value = await db.contrato.toArray()

    if (!codsafraAtiva.value && safras.value.length) {
      const ativa = safras.value.find((s) => !s.inativo) || safras.value[0]
      codsafraAtiva.value = ativa.codsafra
    }
  }

  async function carregarCargas() {
    if (!codsafraAtiva.value) {
      cargas.value = []
      return
    }
    const arr = await db.carga.where('codsafra').equals(codsafraAtiva.value).toArray()
    cargas.value = arr.sort((a, b) => (a.data < b.data ? 1 : -1))
  }

  async function definirSafra(codsafra) {
    codsafraAtiva.value = codsafra
    await carregarCargas()
  }

  function definirSentido(sentido) {
    sentidoAtivo.value = sentido
  }

  async function sincronizar() {
    await sincronizacao.sincronizar()
    await carregarReferencias()
    await carregarCargas()
    saldosUnidades.value = sincronizacao.saldosUnidades
  }

  // Nova carga do sentido informado (ou do board atual). Começa na 1ª etapa.
  function nova(sentido = null) {
    const s = sentido || sentidoAtivo.value
    return {
      uuid: uid(),
      codcarga: null,
      codsafra: codsafraAtiva.value,
      sentido: s,
      etapa: ETAPAS_POR_SENTIDO[s][0],
      data: new Date().toISOString(),
      codveiculo: null,
      placa: null,
      placacarreta: null,
      codpessoamotorista: null,
      motorista: null,
      pbt: null,
      tara: null,
      bruto: null,
      umidade: null,
      impureza: null,
      avariados: null,
      descontoumidade: null,
      descontoimpureza: null,
      descontoavariados: null,
      desconto: null,
      liquido: null,
      observacao: null,
      pontos: [],
      sincronizado: 0,
    }
  }

  // Grava a carga (recalcula local p/ exibir offline) e tenta sincronizar.
  async function salvar(carga) {
    carga.pontos = (carga.pontos || []).filter((p) => {
      if (p.contatipo === 'PLANTIO') return !!p.codplantio
      if (p.contatipo === 'UNIDADE') return !!p.codunidadearmazenadora
      if (p.contatipo === 'CONTRATO') return !!p.codcontrato
      return false
    })
    Object.assign(carga, calcularCarga(carga, faixasDaSafra.value), { sincronizado: 0 })
    const plain = JSON.parse(JSON.stringify(carga))
    await db.carga.put(plain)
    await carregarCargas()
    sincronizacao
      .enviarCarga(JSON.parse(JSON.stringify(carga)))
      .then(() => carregarCargas())
      .catch((e) => {
        // Offline (ERR_NETWORK): fica pendente e sincroniza depois, em silêncio.
        // Rejeição do servidor (422: excede contrato, rateio não fecha): avisa.
        if (e?.code !== 'ERR_NETWORK') notifyError(e)
      })
    return carga
  }

  async function inativar(carga) {
    carga.inativo = new Date().toISOString()
    await salvar(carga)
  }

  async function adicionarVeiculo(veiculo) {
    await db.veiculo.put({ ...veiculo, sincronizado: Date.now() })
    veiculos.value = await db.veiculo.toArray()
  }

  return {
    SENTIDOS,
    ETAPAS_POR_SENTIDO,
    cargas,
    safras,
    plantios,
    culturas,
    variedades,
    faixas,
    veiculos,
    unidades,
    contratos,
    saldosUnidades,
    codsafraAtiva,
    sentidoAtivo,
    veiculosAtivos,
    veiculoPorId,
    unidadesAtivas,
    contratosAtivos,
    safraAtiva,
    culturaAtiva,
    faixasDaSafra,
    pesosaca,
    plantiosDaSafra,
    etapasDoSentido,
    cargasPorEtapa,
    colhidoPorPlantio,
    produtividade,
    saldoUnidadeOffline,
    saldoContratoOffline,
    rotuloContrato,
    carregarReferencias,
    carregarCargas,
    definirSafra,
    definirSentido,
    sincronizar,
    nova,
    salvar,
    inativar,
    adicionarVeiculo,
  }
})
