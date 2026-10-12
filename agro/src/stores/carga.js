import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref, computed } from 'vue'
import { uid } from 'quasar'
import { db } from 'boot/db'
import { useSincronizacaoStore, ehFalhaDeRede } from 'src/stores/sincronizacao'
import { calcularCarga } from 'src/utils/desconto'
import {
  SENTIDOS,
  ETAPAS_POR_SENTIDO,
  ETAPA_FINAL,
  CONTATIPO_PADRAO,
  novoPonto,
  pontoCompleto,
  ratearPontos,
  agoraLocal,
  CAMPOS_MOTORISTA_SEM_CADASTRO,
} from 'src/utils/carga'
import { notifyError } from 'src/utils/notify'

// Constantes e helpers puros do domínio vivem em utils/carga.js (importáveis
// pelos componentes de exibição sem puxar o Pinia). Re-exportados aqui por
// conveniência de quem já importa da store.
export { SENTIDOS, ETAPAS_POR_SENTIDO, CONTATIPO_PADRAO, novoPonto, pontoCompleto, ratearPontos }

// A lista de finalizadas mostraria a safra inteira no drawer; corta nas últimas
// N (como o "Últimos" do PDV).
const LIMITE_FINALIZADAS_SEM_DATA = 30

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
  const parametros = ref([])
  const veiculos = ref([])
  const unidades = ref([])
  const contratos = ref([])
  const saldosUnidades = ref([]) // snapshot do servidor [{codunidadearmazenadora, saldokg, ...}]
  const codsafraAtiva = ref(null)
  const uuidAtivo = ref(null) // carga aberta no centro da tela (rota carga/:uuid)
  const dataFiltro = ref(null) // dia filtrado na listagem (ISO YYYY-MM-DD); vazio = todos

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
  const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)

  // Parâmetros de classificação da cultura da safra ATIVA, na ordem da cascata —
  // é o que o utils/desconto.js consome e o que o CargaForm renderiza.
  // Espelha o backend (ParametroClassificacaoService::daCultura): parâmetro
  // INATIVO fica de fora do cálculo, senão o preview local desconta, o servidor
  // não, e o líquido "pula" depois do sync.
  const parametrosDaSafra = computed(() => parametrosDaCultura(safraAtiva.value?.codcultura))

  // Idem, para uma cultura qualquer — o board precisa disso porque uma carga pode
  // ser de outra safra que não a ativa.
  function parametrosDaCultura(codcultura) {
    if (!codcultura) return []
    return parametros.value
      .filter((p) => p.codcultura === codcultura && !p.inativo)
      .sort((a, b) => (Number(a.ordem) || 0) - (Number(b.ordem) || 0))
  }

  // Safra/cultura/peso da saca de UMA carga. O pátio mistura safras (milho e
  // soja no mesmo dia), então quem exibe a carga lê daqui, não da safra ativa.
  function safraDaCarga(carga) {
    return safras.value.find((x) => x.codsafra === carga?.codsafra) || null
  }
  function culturaDaCarga(carga) {
    const codcultura = safraDaCarga(carga)?.codcultura
    return culturas.value.find((c) => c.codcultura === codcultura) || null
  }
  function pesosacaDaCarga(carga) {
    return culturaDaCarga(carga)?.pesosaca || 60
  }

  // Parâmetros que valem para UMA carga — pela cultura da safra dela.
  function parametrosDaCarga(carga) {
    return parametrosDaCultura(safraDaCarga(carga)?.codcultura)
  }

  function calcularLocal(carga) {
    return calcularCarga(carga, parametrosDaCarga(carga))
  }

  const safrasAtivas = computed(() => safras.value.filter((s) => !s.inativo))

  // Rótulo do plantio: "Talhão — Variedade" (a relação Variedade vem do servidor
  // no cache do plantio).
  function rotuloDoPlantio(p) {
    return `${p.talhao ?? 'Talhão ' + p.codplantio}${
      p.Variedade?.variedade ? ' — ' + p.Variedade.variedade : ''
    }`
  }

  // Plantios ativos de UMA safra, com rótulo — o PlantioMapaDialog escolhe a
  // safra pelo mapa, então não depende da safra ativa da listagem.
  function plantiosPorSafra(codsafra) {
    if (!codsafra) return []
    return plantios.value
      .filter((p) => p.codsafra === codsafra && !p.inativo)
      .map((p) => ({ ...p, rotulo: rotuloDoPlantio(p) }))
  }

  // Plantio por id, de qualquer safra (o ponto de uma carga pode apontar pra
  // outra safra que não a ativa).
  function plantioPorId(codplantio) {
    const p = plantios.value.find((x) => x.codplantio === codplantio)
    return p ? { ...p, rotulo: rotuloDoPlantio(p) } : null
  }

  const plantiosDaSafra = computed(() => plantiosPorSafra(codsafraAtiva.value))

  const cargaAtiva = computed(() => cargas.value.find((c) => c.uuid === uuidAtivo.value) || null)

  // Cargas ainda no pátio (qualquer sentido, qualquer dia). Um caminhão que
  // chegou ontem e ainda não pesou a tara PRECISA continuar aparecendo, por
  // isso a data não filtra aqui — só a lista de finalizadas.
  const cargasNoPatio = computed(() =>
    cargas.value.filter((c) => !c.inativo && c.etapa !== ETAPA_FINAL),
  )

  // Finalizadas do dia filtrado; sem data, as últimas N (de todas as safras).
  const cargasFinalizadas = computed(() => {
    const dia = dataFiltro.value
    const lista = cargas.value.filter(
      (c) => !c.inativo && c.etapa === ETAPA_FINAL && (!dia || String(c.data).slice(0, 10) === dia),
    )
    return dia ? lista : lista.slice(0, LIMITE_FINALIZADAS_SEM_DATA)
  })

  // Totais das finalizadas exibidas (as últimas N da lista ao lado). Sacas carga
  // a carga: milho e soja podem ter peso de saca diferente.
  const totaisFinalizadas = computed(() => {
    let bruto = 0
    let desconto = 0
    let liquido = 0
    let sacas = 0
    let descontoSacas = 0
    for (const c of cargasFinalizadas.value) {
      const ps = pesosacaDaCarga(c)
      bruto += Number(c.bruto) || 0
      desconto += Number(c.desconto) || 0
      liquido += Number(c.liquido) || 0
      sacas += (Number(c.liquido) || 0) / ps
      descontoSacas += (Number(c.desconto) || 0) / ps
    }
    return { bruto, desconto, liquido, sacas, descontoSacas }
  })

  // Siglas dos parâmetros conhecidos (o catálogo não tem coluna de sigla); demais
  // caem no fallback dos 4 primeiros caracteres.
  const ABREV = {
    Impureza: 'Imp',
    Umidade: 'Umid',
    Avariados: 'Avar',
    Esverdeados: 'Esv',
    Quebrados: 'Queb',
  }
  function abreviar(nome) {
    return !nome ? '?' : ABREV[nome] || nome.slice(0, 4)
  }

  // Nome do parâmetro offline-safe: parâmetro da cultura → nested do server
  // (só cargas puxadas) → cadastro em cache → código. Cargas locais só têm o cod.
  function nomeParametro(row, porCod) {
    return (
      porCod.get(row.codparametroclassificacao)?.parametroclassificacao ||
      row.ParametroClassificacao?.parametroclassificacao ||
      parametros.value.find((p) => p.codparametroclassificacao === row.codparametroclassificacao)
        ?.parametroclassificacao ||
      `#${row.codparametroclassificacao}`
    )
  }

  // Chips de classificação: só leituras preenchidas; `fora` = leitura acima da
  // tolerância (gera desconto). Vale p/ FATOR e NORMALIZADO — mesma condição de
  // percentualItem em utils/desconto.js.
  function chipsClassificacao(carga) {
    const itens = parametrosDaCarga(carga)
    const porCod = new Map(itens.map((i) => [i.codparametroclassificacao, i]))
    return (carga?.classificacao || [])
      .filter((c) => c.leitura !== null && c.leitura !== undefined && c.leitura !== '')
      .map((c) => {
        const item = porCod.get(c.codparametroclassificacao)
        return {
          key: c.codparametroclassificacao,
          label: abreviar(nomeParametro(c, porCod)),
          nome: nomeParametro(c, porCod),
          leitura: c.leitura,
          // Só marca "fora" quando há item resolvido (senão tolerância viraria 0 e
          // todo parâmetro inativo/sem catálogo apareceria falsamente vermelho).
          fora: item ? Number(c.leitura) > (Number(item.tolerancia) || 0) : false,
        }
      })
  }

  // Aviso (só ENTRADA, onde a classificação vale): cultura sem parâmetro ativo, que
  // faria o desconto sair 0 em silêncio.
  function avisoClassificacao(carga) {
    if (carga?.sentido !== 'ENTRADA') return null
    if (!parametrosDaCarga(carga).length) {
      return 'Cultura sem parâmetros de classificação — desconto não aplicado'
    }
    return null
  }

  // kg de um ponto = sua fatia do líquido da carga pelo % (fonte da verdade).
  // O `p.liquido` gravado é derivado e pode envelhecer (ex.: carga finalizada
  // antes do rateio existir); por isso derivamos na leitura. Fallback pro liquido
  // gravado só em cargas legadas sem percentual (modelo antigo em kg por ponto).
  function kgRateado(carga, p) {
    if (p.percentual != null && carga.liquido != null) {
      return Math.round((Number(carga.liquido) * (Number(p.percentual) || 0)) / 100)
    }
    return Number(p.liquido) || 0
  }

  // Colhido (kg líquido) por plantio — soma os pontos PLANTIO (origem) das
  // cargas finalizadas/ativas. Base do cálculo de produtividade offline.
  const colhidoPorPlantio = computed(() => {
    const mapa = {}
    for (const c of cargas.value) {
      if (c.inativo || c.etapa !== 'FINALIZADO') continue
      for (const p of c.pontos || []) {
        if (p.contatipo === 'PLANTIO' && p.codplantio) {
          mapa[p.codplantio] = (mapa[p.codplantio] || 0) + kgRateado(c, p)
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
        delta += sinal * kgRateado(c, p)
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

  // Rótulo de um ponto a partir das caches — mesma fonte do CargaForm. Usado pra
  // preencher o `rotulo` das cargas puxadas do servidor (que vêm sem ele).
  function rotuloPonto(p) {
    if (p.contatipo === 'PLANTIO') {
      return plantioPorId(p.codplantio)?.rotulo || null
    }
    if (p.contatipo === 'UNIDADE') {
      return (
        unidades.value.find((o) => o.codunidadearmazenadora === p.codunidadearmazenadora)
          ?.unidadearmazenadora || null
      )
    }
    if (p.contatipo === 'CONTRATO') {
      return rotuloContrato(p.codcontrato)
    }
    return null
  }

  async function carregarReferencias() {
    safras.value = await db.safra.toArray()
    culturas.value = await db.cultura.toArray()
    variedades.value = await db.variedade.toArray()
    parametros.value = await db.parametroclassificacao.toArray()
    plantios.value = await db.plantio.toArray()
    veiculos.value = await db.veiculo.toArray()
    unidades.value = await db.unidadearmazenadora.toArray()
    contratos.value = await db.contrato.toArray()

    if (!codsafraAtiva.value && safras.value.length) {
      const ativa = safras.value.find((s) => !s.inativo) || safras.value[0]
      codsafraAtiva.value = ativa.codsafra
    }
  }

  // Todas as safras: o pátio é físico e um caminhão de milho não pode sumir da
  // lista porque a última carga lançada foi de soja (TASK-109).
  async function carregarCargas() {
    const arr = await db.carga.toArray()
    // Auto-reparo: cargas cujo bruto/liquido foram zerados por uma resposta parcial
    // de sync (mas os pesos pbt/tara continuam lá) — recalcula localmente e regrava.
    // Roda 1x por carga afetada (depois liquido != null). Carga sem pesar tem
    // pbt/tara null, então não entra aqui.
    for (const c of arr) {
      if (c.liquido == null && c.pbt != null && c.tara != null) {
        Object.assign(c, calcularLocal(c))
        await db.carga.update(c.uuid, {
          bruto: c.bruto,
          desconto: c.desconto,
          liquido: c.liquido,
          classificacao: c.classificacao,
        })
      }
      // Cargas puxadas do servidor vêm com rotulo null — resolve das caches.
      for (const p of c.pontos || []) {
        if (!p.rotulo) p.rotulo = rotuloPonto(p)
      }
    }
    cargas.value = arr.sort((a, b) => (a.data < b.data ? 1 : -1))
  }

  // Puxa as cargas do servidor (best-effort: offline segue com o Dexie local) e
  // recarrega o board.
  async function puxarCargasDoDia() {
    await sincronizacao.puxarCargas(dataFiltro.value).catch(() => {})
    await carregarCargas()
  }

  // `codsafraAtiva` não filtra mais o pátio: é só a safra da home/KPIs.
  async function definirSafra(codsafra) {
    codsafraAtiva.value = codsafra
    await puxarCargasDoDia()
  }

  function abrir(uuid) {
    uuidAtivo.value = uuid || null
  }

  // `opts` (ex.: { force: true } vindo do botao "Sincronizar") repassa pro throttle
  // da store de sincronizacao. As re-leituras do Dexie sao baratas e rodam mesmo
  // se o ciclo falhar no meio: o que ja chegou no Dexie aparece na tela.
  async function sincronizar(opts) {
    try {
      await sincronizacao.sincronizar(opts)
    } finally {
      await carregarReferencias()
      await puxarCargasDoDia()
      saldosUnidades.value = sincronizacao.saldosUnidades
    }
  }

  // Nova carga (default Recebimento — o operador troca no modal de Operação
  // enquanto não pesou). Começa na 1ª etapa do sentido. A semeadura de
  // origem/destino padrão é feita no CargaForm (camada de UI).
  // Nasce SEM safra: o operador escolhe no modal (ou ela vem do talhão de
  // origem), e o Registrar não passa sem ela. Sem nenhuma safra sincronizada
  // retorna null — não haveria o que escolher.
  function nova(sentido = 'ENTRADA') {
    if (!safrasAtivas.value.length) return null
    const s = sentido
    return {
      uuid: uid(),
      codcarga: null,
      codsafra: null,
      sentido: s,
      etapa: ETAPAS_POR_SENTIDO[s][0],
      data: agoraLocal(),
      codveiculo: null,
      placa: null,
      placacarreta: null,
      placacarreta2: null,
      codpessoamotorista: null,
      motorista: null,
      ...Object.fromEntries(CAMPOS_MOTORISTA_SEM_CADASTRO.map((c) => [c, null])),
      pbt: null,
      tara: null,
      bruto: null,
      desconto: null,
      liquido: null,
      observacao: null,
      classificacao: [],
      pontos: [],
      sincronizado: 0,
      syncerro: null,
    }
  }

  // Grava a carga (recalcula local p/ exibir offline) e tenta sincronizar.
  // Trabalha numa CÓPIA — não muta o objeto do dialog (senão linhas ainda
  // incompletas sumiriam no meio do fluxo). Só pontos completos são gravados,
  // e o líquido por ponto é rateado a partir do % antes de persistir/enviar.
  async function salvar(carga) {
    const limpa = {
      ...carga,
      pontos: (carga.pontos || []).filter(pontoCompleto).map((p) => ({ ...p })),
      classificacao: (carga.classificacao || []).map((c) => ({ ...c })),
    }
    // TASK-180: `atual` (Dexie) é a última palavra do que já foi confirmado com o
    // servidor — mais segura que o `carga` recebido (cópia do formulário, que só é
    // atualizada por watcher separado). `revisao` marca esta gravação local pra
    // enviarCargaUma saber, na volta do POST, se o operador editou de novo enquanto
    // o envio estava no ar; `versao` é controle do servidor — o form nunca mexe.
    const atual = await db.carga.get(limpa.uuid)
    limpa.revisao = (atual?.revisao ?? 0) + 1
    limpa.versao = atual?.versao ?? limpa.versao ?? null
    // syncerro: null — reeditar/salvar limpa uma rejeição anterior e rearma o envio.
    Object.assign(limpa, calcularCarga(limpa, parametrosDaCarga(limpa)), {
      sincronizado: 0,
      syncerro: null,
    })
    ratearPontos(limpa)
    const plain = JSON.parse(JSON.stringify(limpa))
    await db.carga.put(plain)
    await carregarCargas()
    sincronizacao
      .enviarCargaPorUuid(limpa.uuid)
      .then(() => carregarCargas())
      .catch(async (e) => {
        // Rede (offline, timeout): fica pendente e o ciclo automático envia quando
        // a rede voltar, em silêncio.
        // Rejeição do servidor (422/500): marca `syncerro` p/ não re-tentar em loop,
        // reflete no board e avisa uma vez.
        if (ehFalhaDeRede(e)) {
          sincronizacao.online = false
        } else {
          const msg = e?.response?.data?.message || 'Rejeitado pelo servidor'
          await db.carga.update(limpa.uuid, { syncerro: msg })
          await carregarCargas()
          notifyError(e)
        }
      })
    return limpa
  }

  // Botão de sincronizar do resumo: faz as DUAS coisas num clique — destrava
  // ESTA carga (limpa a rejeição, que o ciclo normal pula de propósito) e roda o
  // ciclo completo forçado, igual ao botão da nuvem: empurra todas as pendências,
  // refaz o pull dos cadastros ignorando o TTL e atualiza os saldos.
  async function reenviar(carga) {
    await db.carga.update(carga.uuid, { sincronizado: 0, syncerro: null })
    await carregarCargas()
    await sincronizar({ force: true })
  }

  async function inativar(carga) {
    carga.inativo = agoraLocal()
    await salvar(carga)
  }

  // Descarta uma carga que nunca sincronizou (ex.: rejeitada, teste). Apaga do
  // Dexie sem passar pelo servidor — só faz sentido enquanto não há codcarga.
  async function descartarPendente(carga) {
    await db.carga.delete(carga.uuid)
    await carregarCargas()
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
    parametros,
    veiculos,
    unidades,
    contratos,
    saldosUnidades,
    codsafraAtiva,
    uuidAtivo,
    dataFiltro,
    veiculosAtivos,
    veiculoPorId,
    unidadesAtivas,
    contratosAtivos,
    safraAtiva,
    safrasAtivas,
    culturaAtiva,
    parametrosDaSafra,
    parametrosDaCultura,
    parametrosDaCarga,
    safraDaCarga,
    culturaDaCarga,
    pesosacaDaCarga,
    pesosaca,
    plantiosDaSafra,
    plantiosPorSafra,
    plantioPorId,
    cargaAtiva,
    cargasNoPatio,
    cargasFinalizadas,
    totaisFinalizadas,
    chipsClassificacao,
    avisoClassificacao,
    colhidoPorPlantio,
    produtividade,
    saldoUnidadeOffline,
    saldoContratoOffline,
    rotuloContrato,
    carregarReferencias,
    carregarCargas,
    definirSafra,
    abrir,
    sincronizar,
    nova,
    salvar,
    reenviar,
    inativar,
    descartarPendente,
    adicionarVeiculo,
  }
})

// HMR: sem isto o Pinia mantém a versão ANTIGA das actions no dev (mudanças em
// salvar/ratearPontos etc. só valeriam após hard refresh).
if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useCargaStore, import.meta.hot))
}
