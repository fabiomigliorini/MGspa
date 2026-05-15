/**
 * Compara dados de cada campo do item da NFe com o cadastro do produto vinculado.
 *
 * Cada função retorna { ok, motivo }:
 *   - ok === true  → campo da NFe casa com o cadastro
 *   - ok === false → diverge
 *   - ok === null  → não há produto vinculado para comparar
 *
 * Espelha as comparações que o legado MGsis faz em view.php (text-success / text-error).
 */

const ORIGENS_IMPORTADAS = [1, 2, 6, 7]

function getProduto(item) {
  return item?.produtoBarra?.produto || null
}

function getVariacao(item) {
  return item?.produtoBarra?.variacao || null
}

function normNcm(v) {
  if (!v) return ''
  return String(v).replace(/\D/g, '')
}

function normCest(v) {
  if (!v) return ''
  return String(v).replace(/\D/g, '')
}

export function conformidadeNcm(item) {
  const produto = getProduto(item)
  if (!produto) return { ok: null, motivo: 'Sem produto vinculado' }
  const ncmNota = normNcm(item.ncm)
  const ncmProduto = normNcm(produto.ncm?.ncm)
  if (!ncmProduto) return { ok: null, motivo: 'Produto sem NCM cadastrado' }
  if (ncmNota === ncmProduto) {
    return { ok: true, motivo: `NCM ${ncmNota} confere com cadastro` }
  }
  return { ok: false, motivo: `NCM nota ${ncmNota} ≠ cadastro ${ncmProduto}` }
}

export function conformidadeCest(item) {
  const produto = getProduto(item)
  if (!produto) return { ok: null, motivo: 'Sem produto vinculado' }
  const cestNota = normCest(item.cest)
  const cestProduto = normCest(produto.cest?.cest)
  if (!cestNota && !cestProduto) {
    return { ok: true, motivo: 'Ambos sem CEST' }
  }
  if (!cestNota && cestProduto) {
    return { ok: false, motivo: `Nota sem CEST; cadastro tem ${cestProduto}` }
  }
  if (cestNota && !cestProduto) {
    return { ok: false, motivo: `Nota ${cestNota}; cadastro sem CEST` }
  }
  if (cestNota === cestProduto) {
    return { ok: true, motivo: `CEST ${cestNota} confere` }
  }
  return { ok: false, motivo: `CEST nota ${cestNota} ≠ cadastro ${cestProduto}` }
}

export function conformidadeOrigem(item) {
  const produto = getProduto(item)
  if (!produto) return { ok: null, motivo: 'Sem produto vinculado' }
  if (item.orig === null || item.orig === undefined) {
    return { ok: null, motivo: 'Origem não informada' }
  }
  const importadoNota = ORIGENS_IMPORTADAS.includes(Number(item.orig))
  const importadoCadastro = Boolean(produto.importado)
  if (importadoNota === importadoCadastro) {
    return {
      ok: true,
      motivo: importadoNota ? 'Importado em ambos' : 'Nacional em ambos',
    }
  }
  return {
    ok: false,
    motivo: importadoNota
      ? 'Nota indica importado; cadastro é nacional'
      : 'Nota indica nacional; cadastro é importado',
  }
}

function variacaoTemBarras(variacao, barras) {
  if (!variacao || !Array.isArray(variacao.produtoBarras)) return false
  if (!barras) return false
  return variacao.produtoBarras.some((b) => String(b.barras) === String(barras))
}

export function conformidadeCean(item) {
  const variacao = getVariacao(item)
  if (!variacao) return { ok: null, motivo: 'Sem produto vinculado' }
  if (!item.cean) return { ok: null, motivo: 'EAN não informado' }
  if (variacaoTemBarras(variacao, item.cean)) {
    return { ok: true, motivo: `EAN ${item.cean} cadastrado` }
  }
  return { ok: false, motivo: `EAN ${item.cean} não cadastrado` }
}

export function conformidadeCeantrib(item) {
  const variacao = getVariacao(item)
  if (!variacao) return { ok: null, motivo: 'Sem produto vinculado' }
  if (!item.ceantrib) return { ok: null, motivo: 'EAN Trib não informado' }
  if (variacaoTemBarras(variacao, item.ceantrib)) {
    return { ok: true, motivo: `EAN Trib ${item.ceantrib} cadastrado` }
  }
  return { ok: false, motivo: `EAN Trib ${item.ceantrib} não cadastrado` }
}

/**
 * Compara a tributação determinada pelo XML do item (analise.codtributacao,
 * calculada no backend) com a tributação do cadastro do produto.
 */
export function conformidadeCst(item, codtributacaoCalculada) {
  const produto = getProduto(item)
  if (!produto) return { ok: null, motivo: 'Sem produto vinculado' }
  if (!produto.codtributacao || !codtributacaoCalculada) {
    return { ok: null, motivo: 'Tributação não disponível' }
  }
  if (Number(produto.codtributacao) === Number(codtributacaoCalculada)) {
    return { ok: true, motivo: 'Tributação confere com cadastro' }
  }
  return {
    ok: false,
    motivo: `Tributação nota ${codtributacaoCalculada} ≠ cadastro ${produto.codtributacao}`,
  }
}

export function conformidades(item, codtributacaoCalculada) {
  return {
    ncm: conformidadeNcm(item),
    cest: conformidadeCest(item),
    origem: conformidadeOrigem(item),
    cean: conformidadeCean(item),
    ceantrib: conformidadeCeantrib(item),
    cst: conformidadeCst(item, codtributacaoCalculada),
  }
}

/**
 * Cor textual derivada de { ok } - útil para classes ":class".
 */
export function corConformidade(conf) {
  if (!conf) return ''
  if (conf.ok === true) return 'text-green-8'
  if (conf.ok === false) return 'text-red'
  return 'text-grey-7'
}
