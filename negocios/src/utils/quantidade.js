// Fração da quantidade pela unidade de medida do produto (TASK-53). O catálogo do PDV, a
// pesquisa em grade e os itens do modelo de vale trazem, junto da sigla, as casas decimais da
// unidade: 0 é só inteiro, 2 aceita 0,53 MT, 3 aceita 0,712 KG. Sem elas (catálogo baixado antes
// do campo existir) não trava: vale o máximo da quantidade, 3 casas, como sempre foi.
const DECIMAIS_MAXIMO = 3

// a mensagem de recusa, ou null quando a quantidade cabe nas casas da unidade
export const fracaoRecusada = (quantidade, unidade) => {
  const decimais = unidade?.decimais ?? DECIMAIS_MAXIMO
  const q = Math.abs(parseFloat(quantidade)) * Math.pow(10, decimais)
  if (isNaN(q) || Math.abs(q - Math.round(q)) < 1e-6) {
    return null
  }
  const sigla = unidade?.sigla ?? 'A unidade'
  if (decimais == 0) {
    return `${sigla} não aceita fração`
  }
  return `${sigla} aceita só ${decimais} ${decimais == 1 ? 'casa' : 'casas'} depois da vírgula`
}
