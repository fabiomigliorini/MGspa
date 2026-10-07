// As linhas do extrato do período da maquineta (TASK-188 M9.8), com o mesmo sinal do sistema
// (MaquinetaLoteService::sistema): o cartão que caiu no período (o contrário entra negativo) e o
// cancelamento feito durante o período (com o sinal invertido, na hora do cancelamento). Um
// cartão cancelado no mesmo período aparece duas vezes, como no extrato da maquineta.
export function linhasDoPeriodo(periodo) {
  const cod = periodo?.codmaquinetalote
  const ret = []
  for (const l of periodo?.lancamentos ?? []) {
    const sinal = l.operacao === 'DB' ? -1 : 1
    if (l.codmaquinetalote === cod) {
      ret.push({ chave: `${l.codpagamento}`, momento: l.transacao, valor: sinal * l.total, l })
    }
    if (l.codmaquinetalotecancelamento === cod) {
      ret.push({
        chave: `${l.codpagamento}-c`,
        momento: l.cancelamento ?? l.transacao,
        valor: -sinal * l.total,
        cancelamento: true,
        l,
      })
    }
  }
  return ret.sort((a, b) => new Date(a.momento) - new Date(b.momento))
}
