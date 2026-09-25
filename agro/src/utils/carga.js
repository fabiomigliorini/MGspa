// Domínio da Carga (pátio): constantes do fluxo, helpers puros e mapeamento do
// servidor. Fica FORA da store pra ser importável por componentes de exibição
// (item de lista, resumo, progresso) sem puxar o Pinia — e sem import circular
// (a store importa daqui).

// Etapas por sentido — controlam a ordem de pesagem e a barra de progresso.
// ENTRADA chega cheio (pesa PBT antes); SAIDA chega vazio (pesa tara antes).
export const ETAPAS_POR_SENTIDO = {
  ENTRADA: ['PBT', 'CLASSIFICACAO', 'TARA', 'FINALIZADO'],
  SAIDA: ['TARA', 'PBT', 'FISCAL', 'FINALIZADO'],
  TRANSFERENCIA: ['PBT', 'TARA', 'FINALIZADO'],
}

export const ETAPA_FINAL = 'FINALIZADO'

// Etapas "no pátio" (união dos fluxos, sem a final). Usado no pull do servidor:
// o endpoint só filtra etapa por igualdade, então varre uma a uma.
export const ETAPAS_ABERTAS = ['PBT', 'TARA', 'CLASSIFICACAO', 'FISCAL']

export const SENTIDOS = [
  { value: 'ENTRADA', label: 'Recebimento', icon: 'local_shipping', color: 'green-7' },
  { value: 'SAIDA', label: 'Expedição', icon: 'outbound', color: 'green-8' },
  { value: 'TRANSFERENCIA', label: 'Transferência', icon: 'swap_horiz', color: 'blue-grey-7' },
]

// `curto` é o rótulo da barra de progresso: com 4 etapas num drawer de 300px cada
// segmento fica com ~60px, e o nome inteiro sairia cortado por reticências.
export const ETAPA_META = {
  PBT: {
    label: 'Peso Bruto',
    curto: 'Bruto',
    acao: 'Pesar bruto',
    icon: 'scale',
    color: 'orange-8',
  },
  TARA: {
    label: 'Tara',
    curto: 'Tara',
    acao: 'Pesar tara',
    icon: 'monitor_weight',
    color: 'teal-7',
  },
  CLASSIFICACAO: {
    label: 'Classificação',
    curto: 'Classif.',
    acao: 'Classificar',
    icon: 'science',
    color: 'deep-purple-6',
  },
  FISCAL: {
    label: 'Nota Fiscal',
    curto: 'Fiscal',
    acao: 'Notas fiscais',
    icon: 'receipt_long',
    color: 'deep-orange-7',
  },
  FINALIZADO: {
    label: 'Finalizado',
    curto: 'Final',
    acao: 'Finalizar',
    icon: 'task_alt',
    color: 'green-7',
  },
}

// Tipo (contatipo) padrão da origem/destino por sentido — usado ao semear a
// carga nova, ao trocar o sentido e ao clicar "+" em origem/destino.
// Recebimento entra do talhão pra unidade; expedição sai da unidade pro
// contrato; transferência unidade↔unidade.
export const CONTATIPO_PADRAO = {
  ENTRADA: { ORIGEM: 'PLANTIO', DESTINO: 'UNIDADE' },
  SAIDA: { ORIGEM: 'UNIDADE', DESTINO: 'CONTRATO' },
  TRANSFERENCIA: { ORIGEM: 'UNIDADE', DESTINO: 'UNIDADE' },
}

// Ícone/cor por tipo de ponto (contatipo). Mora aqui, junto de SENTIDOS e
// ETAPA_META, porque quem EXIBE o ponto (bloco de origem/destino, resumo)
// precisa do mesmo par que o SelectContaTipo usa nas opções — sem duplicar o
// mapa em cada tela.
export const CONTATIPO_META = {
  PLANTIO: { value: 'PLANTIO', label: 'Talhão', icon: 'grass', color: 'brown-5' },
  UNIDADE: { value: 'UNIDADE', label: 'Unidade', icon: 'warehouse', color: 'amber-7' },
  CONTRATO: { value: 'CONTRATO', label: 'Contrato', icon: 'description', color: 'teal-7' },
}

// Ordem das opções do select (DESTINO não recebe grão de volta pro talhão —
// o filtro fica no componente, que conhece o papel).
export const CONTATIPOS = [
  CONTATIPO_META.PLANTIO,
  CONTATIPO_META.UNIDADE,
  CONTATIPO_META.CONTRATO,
]

export function sentidoMeta(sentido) {
  return SENTIDOS.find((s) => s.value === sentido) || SENTIDOS[0]
}

export function contatipoMeta(contatipo) {
  return CONTATIPO_META[contatipo] || CONTATIPO_META.UNIDADE
}

export function etapasDaCarga(carga) {
  return ETAPAS_POR_SENTIDO[carga?.sentido] || []
}

export function indiceEtapa(carga) {
  return etapasDaCarga(carga).indexOf(carga?.etapa)
}

export function proximaEtapa(carga) {
  const ordem = etapasDaCarga(carga)
  const i = indiceEtapa(carga)
  return i >= 0 && i < ordem.length - 1 ? ordem[i + 1] : null
}

export function cargaFinalizada(carga) {
  return carga?.etapa === ETAPA_FINAL
}

// Já passou pela balança? A ordem das etapas diverge por sentido (ENTRADA pesa
// PBT antes, SAIDA pesa tara antes), então trocar depois de pesar reposiciona a
// carga no fluxo novo. Um helper com este nome existiu e sustentava a trava
// DURA (não deixava trocar); a TASK-141 abriu a troca até FINALIZAR e ele saiu.
// Voltou pra pedir CONFIRMAÇÃO, não pra travar de novo: sem peso a troca segue
// direta, com peso o operador confirma sabendo a consequência.
export function cargaPesada(carga) {
  return carga?.pbt != null || carga?.tara != null
}

export function iconeCarga(carga) {
  return sentidoMeta(carga?.sentido).icon
}

// Cor do avatar (espelha iconeNegocio do negocios): cancelada → cinza, rejeitada
// pelo servidor → vermelho, finalizada → verde, senão a cor do sentido.
export function corIconeCarga(carga) {
  if (carga?.inativo) return 'grey-5'
  if (carga?.syncerro) return 'negative'
  if (cargaFinalizada(carga)) return 'green-7'
  return sentidoMeta(carga?.sentido).color
}

// Ponto (origem/destino) novo. `percentual` (rateio da carga) é campo só-do-front:
// o kg (`liquido`) é derivado do líquido calculado da carga na hora de salvar.
export function novoPonto(papel, contatipo) {
  return {
    papel,
    contatipo,
    codplantio: null,
    codunidadearmazenadora: null,
    codcontrato: null,
    percentual: 100,
    liquido: null,
    rotulo: null,
    numeronf: null,
    valornf: null,
  }
}

// Ponto "completo" = tem a entidade escolhida. Sem ela o ponto não pode ser
// gravado (o backend rejeita) e não conta no colhido/saldo.
export function pontoCompleto(p) {
  if (p.contatipo === 'PLANTIO') return !!p.codplantio
  if (p.contatipo === 'UNIDADE') return !!p.codunidadearmazenadora
  if (p.contatipo === 'CONTRATO') return !!p.codcontrato
  return false
}

// Divide 100% igualmente entre as linhas do grupo (resto na última) — soma = 100.
// Puro por design: usado tanto pelo CargaForm (normalizarPontos, cargas antigas
// sem percentual) quanto pelo bloco de edição de origem/destino (add/remove linha).
export function distribuirPercentual(grupo) {
  const n = grupo.length
  if (!n) return
  const base = Math.floor((100 / n) * 10) / 10
  let acumulado = 0
  grupo.forEach((p, idx) => {
    if (idx === n - 1) {
      p.percentual = Math.round((100 - acumulado) * 10) / 10
    } else {
      p.percentual = base
      acumulado += base
    }
  })
}

// Pontos de um papel (ORIGEM/DESTINO) — mesmo filtro usado no form e no bloco de
// origem/destino, pra não duplicar o `.filter` em cada lugar que precisa da lista.
export function pontosPorPapel(carga, papel) {
  return (carga?.pontos || []).filter((p) => p.papel === papel)
}

// Soma de % de um grupo de pontos — exibição (caption "Soma: X%") e validação
// (rateio precisa fechar 100% pra finalizar) usam a mesma conta.
export function somaPercentual(pontos) {
  return (pontos || []).reduce((s, p) => s + (Number(p.percentual) || 0), 0)
}

// Soma "bate" 100% (com folga de arredondamento).
export function somaPercBate(pontos) {
  return Math.abs(somaPercentual(pontos) - 100) < 0.5
}

// Carga nova já abre com 1 origem + 1 destino no tipo padrão do sentido. Só
// semeia o que faltar.
export function semearPontos(carga) {
  const s = carga.sentido
  if (!carga.pontos.some((p) => p.papel === 'ORIGEM')) {
    carga.pontos.push(novoPonto('ORIGEM', CONTATIPO_PADRAO[s]?.ORIGEM || 'UNIDADE'))
  }
  if (!carga.pontos.some((p) => p.papel === 'DESTINO')) {
    carga.pontos.push(novoPonto('DESTINO', CONTATIPO_PADRAO[s]?.DESTINO || 'UNIDADE'))
  }
}

// Troca o sentido de uma carga ainda não pesada: volta pra 1ª etapa do novo
// fluxo e re-semeia o TIPO dos pontos que o operador ainda não preencheu (os
// completos ficam — ele pode ter escolhido de propósito).
export function aplicarSentido(carga, sentido) {
  carga.sentido = sentido
  carga.etapa = ETAPAS_POR_SENTIDO[sentido][0]
  for (const p of carga.pontos || []) {
    if (pontoCompleto(p)) continue
    p.contatipo = CONTATIPO_PADRAO[sentido]?.[p.papel] || 'UNIDADE'
    p.codplantio = null
    p.codunidadearmazenadora = null
    p.codcontrato = null
    p.rotulo = null
  }
  semearPontos(carga)
}

// Rateia o líquido da carga entre os pontos de cada papel a partir do %. O resto
// vai na última linha pra soma bater exata (evita o 422 "rateio não fecha").
// Antes de pesar (liquido null) não há kg pra ratear.
export function ratearPontos(carga) {
  const liq = Number(carga.liquido)
  for (const papel of ['ORIGEM', 'DESTINO']) {
    const grupo = (carga.pontos || []).filter((p) => p.papel === papel)
    if (!grupo.length) continue
    if (!(liq > 0)) {
      grupo.forEach((p) => {
        p.liquido = null
      })
      continue
    }
    let acumulado = 0
    grupo.forEach((p, idx) => {
      if (idx === grupo.length - 1) {
        p.liquido = Math.round(liq - acumulado)
      } else {
        const kg = Math.round((liq * (Number(p.percentual) || 0)) / 100)
        p.liquido = kg
        acumulado += kg
      }
    })
  }
}

// "Talhão 12 · Silo 1" — os pontos que identificam a carga na listagem: de onde
// veio (entrada) ou pra onde vai (saída).
export function pontosResumo(carga) {
  const origem = (carga.pontos || []).filter((p) => p.papel === 'ORIGEM')
  const destino = (carga.pontos || []).filter((p) => p.papel === 'DESTINO')
  const lista = (carga.sentido === 'SAIDA' ? destino : origem).map((p) => p.rotulo).filter(Boolean)
  return lista.length ? lista.join(' · ') : 'Sem origem/destino'
}

// Número em pt-BR; vazio vira travessão (é exibição, não cálculo).
export function fmtNumero(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

// Instante atual em wall-clock LOCAL 'YYYY-MM-DD HH:mm:ss' — MESMO formato que o
// MgInputData (dateToIso) emite e que o backend devolve (serializeDate = Y-m-d H:i:s,
// sem offset). Gravar UTC (new Date().toISOString()) jogava a carga da noite pro dia
// seguinte no recorte slice(0,10) do board. Assim carga criada, editada e puxada do
// servidor viram a MESMA string.
export function agoraLocal() {
  const d = new Date()
  const p = (n) => String(n).padStart(2, '0')
  return (
    `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ` +
    `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
  )
}

// Mapeia uma carga vinda do servidor (GET /v1/carga) para o shape offline que o
// Dexie/listagem usam. O servidor entrega colunas cruas + relações em PascalCase
// (CargaPontoS) e as leituras já na chave `classificacao`;
// aqui achatamos pro mesmo formato de `nova()` em stores/carga.js.
//
// Dois campos derivados ficam de fora de propósito:
//  - `percentual` do ponto: o CargaForm reconstrói ao abrir (normalizarPontos).
//  - `rotulo` do ponto: o carga store resolve no carregarCargas a partir das
//    caches (plantios/unidades/contratos), fonte igual à do formulário — evita
//    depender das relações aninhadas snake/Pascal do servidor.

function normalizarPontoDoServidor(sp) {
  return {
    papel: sp.papel,
    contatipo: sp.contatipo,
    codplantio: sp.codplantio ?? null,
    codunidadearmazenadora: sp.codunidadearmazenadora ?? null,
    codcontrato: sp.codcontrato ?? null,
    percentual: null,
    liquido: sp.liquido ?? null,
    rotulo: null,
    numeronf: sp.numeronf ?? null,
    valornf: sp.valornf ?? null,
    chavenf: sp.chavenf ?? null,
  }
}

// Carga do servidor -> shape que os componentes de EXIBIÇÃO esperam (`pontos`
// com `rotulo` pronto). Não confundir com normalizarCargaDoServidor, logo
// abaixo: aquela produz o shape OFFLINE, que vai pro Dexie e descarta o rótulo
// de propósito (a store do pátio resolve pelas caches locais).
//
// Aqui o rótulo vem do backend (CargaPontoService::rotulo) porque as telas de
// consulta são online e não têm cache nenhum pra consultar.
export function normalizarCargaParaExibicao(cs) {
  return {
    ...cs,
    pontos: (cs.CargaPontoS || []).map((sp) => ({
      papel: sp.papel,
      contatipo: sp.contatipo,
      codplantio: sp.codplantio ?? null,
      codunidadearmazenadora: sp.codunidadearmazenadora ?? null,
      codcontrato: sp.codcontrato ?? null,
      liquido: sp.liquido ?? null,
      rotulo: sp.rotulo ?? null,
      numeronf: sp.numeronf ?? null,
      valornf: sp.valornf ?? null,
      chavenf: sp.chavenf ?? null,
    })),
  }
}

// Rótulos de um papel, juntos — "Talhão 12 · Talhão 14". Espelha
// CargaRelatorioService::rotulosDoPapel pra tela e PDF dizerem a mesma coisa.
export function rotulosDoPapel(carga, papel) {
  return (carga?.pontos || [])
    .filter((p) => p.papel === papel)
    .map((p) => p.rotulo)
    .filter(Boolean)
    .join(' · ')
}

export function normalizarCargaDoServidor(cs) {
  return {
    uuid: cs.uuid,
    codcarga: cs.codcarga ?? null,
    codsafra: cs.codsafra,
    sentido: cs.sentido,
    etapa: cs.etapa,
    data: cs.data,
    codveiculo: cs.codveiculo ?? null,
    placa: cs.placa ?? null,
    placacarreta: cs.placacarreta ?? null,
    codpessoamotorista: cs.codpessoamotorista ?? null,
    motorista: cs.motorista ?? null,
    pbt: cs.pbt ?? null,
    tara: cs.tara ?? null,
    bruto: cs.bruto ?? null,
    desconto: cs.desconto ?? null,
    liquido: cs.liquido ?? null,
    observacao: cs.observacao ?? null,
    inativo: cs.inativo ?? null,
    classificacao: (cs.classificacao || []).map((c) => ({
      codparametroclassificacao: c.codparametroclassificacao,
      leitura: c.leitura ?? null,
      desconto: c.desconto ?? null,
    })),
    pontos: (cs.CargaPontoS || []).map(normalizarPontoDoServidor),
  }
}
