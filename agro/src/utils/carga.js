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
// Dexie/board usam. O servidor entrega colunas cruas + relações em PascalCase
// (CargaPontoS, TabelaClassificacao) e as leituras já na chave `classificacao`;
// aqui achatamos pro mesmo formato de `nova()` em stores/carga.js.
//
// Dois campos derivados ficam de fora de propósito:
//  - `percentual` do ponto: o CargaDialog reconstrói ao abrir (normalizarPontos).
//  - `rotulo` do ponto: o carga store resolve no carregarCargas a partir das
//    caches (plantios/unidades/contratos), fonte igual à do dialog — evita
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
    codtabelaclassificacao: cs.codtabelaclassificacao ?? null,
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
