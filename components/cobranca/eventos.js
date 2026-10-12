// Avisos entre as peças do wizard de cobrança e quem as usa (no lugar do mitt de cada app):
// 'cobrancaAtualizada' ({ codnegocio, pixCob }) quando uma cobrança integrada muda e
// 'cobrancaConcluida' ({ tipo, dados }) quando o banco/maquineta confirmou.
const ouvintes = {}

// devolve a função que cancela a inscrição
export const ouvir = (evento, fn) => {
  ;(ouvintes[evento] ??= new Set()).add(fn)
  return () => ouvintes[evento].delete(fn)
}

export const avisar = (evento, dados) => {
  ouvintes[evento]?.forEach((fn) => fn(dados))
}
