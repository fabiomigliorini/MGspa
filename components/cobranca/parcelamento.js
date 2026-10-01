// Cálculo único de parcelamento (cartão e prazo).
// Acima de maximoParcelasSemJuros aplica juros simples de `taxa` % ao mês sobre o valor.
// Para de gerar quando a parcela fica abaixo do mínimo (a 1ª parcela sempre existe).
export function calcularParcelas({
  valor,
  maximoParcelas,
  maximoParcelasSemJuros = maximoParcelas,
  valorMinimoParcela = 0,
  taxa = 1.5,
}) {
  const planos = []
  for (let i = 1; i <= maximoParcelas; i++) {
    let valorjuros = 0
    let valorparcela = Math.round((valor / i) * 100) / 100
    if (i > maximoParcelasSemJuros) {
      valorjuros = Math.round(taxa * i * valor) / 100
      valorparcela = Math.round(((valor + valorjuros) / i) * 100) / 100
      valorjuros = Math.round((valorparcela * i - valor) * 100) / 100
    }
    if (i > 1 && valorparcela < valorMinimoParcela) {
      break
    }
    planos.push({ parcelas: i, valorjuros, valorparcela })
  }
  return planos
}
