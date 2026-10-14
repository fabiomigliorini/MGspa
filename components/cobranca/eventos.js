// Avisos entre as peças do wizard de cobrança e quem as usa (no lugar do mitt de cada app):
// 'cobrancaAtualizada' ({ codnegocio, pixCob }) quando uma cobrança integrada muda e
// 'cobrancaConcluida' ({ tipo, dados }) quando o banco/maquineta confirmou.
// avisar() devolve a promise de todos os ouvintes: quem precisa da tela já recarregada antes
// do próximo passo espera por ela (o dialog só confere o pago depois que o negócio recarregou).
const ouvintes = {}

// devolve a função que cancela a inscrição
export const ouvir = (evento, fn) => {
  ;(ouvintes[evento] ??= new Set()).add(fn)
  return () => ouvintes[evento].delete(fn)
}

export const avisar = (evento, dados) =>
  Promise.all([...(ouvintes[evento] ?? [])].map((fn) => fn(dados)))
