// Validade do cache de cadastros do pátio (Dexie). O sincronizar só refaz o pull
// pesado quando o cache passou do TTL; salvar um cadastro no próprio agro precisa
// derrubar essa validade, senão o registro recém-criado não aparece no pátio.
// Fica fora da store pra ser usado pelo api.js sem import circular.
const CHAVE = 'agro:ultimaSincronizacao'

// Recursos que o pull de referências baixa. `safra` cobre o plantio aninhado
// (safra/{codsafra}/plantio) e `contrato` as fixações/pagamentos.
const RECURSOS_REFERENCIA =
  /^\/?v1\/(cultura|variedade|parametro-classificacao|fazenda|talhao|safra|contrato|veiculo|unidade-armazenadora)(\/|\?|$)/

export function ehRecursoDeReferencia(url) {
  return RECURSOS_REFERENCIA.test(url || '')
}

// localStorage pode lançar (aba privada, site data bloqueado). Aqui isso não pode
// derrubar um salvar — no pior caso o cache só espera o TTL.
export function lerUltimaSincronizacao() {
  try {
    return Number(localStorage.getItem(CHAVE)) || null
  } catch {
    return null
  }
}

export function gravarUltimaSincronizacao(ts) {
  try {
    localStorage.setItem(CHAVE, String(ts))
  } catch {
    // sem storage: o próximo sync refaz o pull
  }
}

export function invalidarReferencias() {
  try {
    localStorage.removeItem(CHAVE)
  } catch {
    // sem storage: não havia validade gravada
  }
}
