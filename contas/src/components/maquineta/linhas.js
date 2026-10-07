// As linhas do período da maquineta (TASK-188 M9.8), com o mesmo sinal do sistema
// (MaquinetaLoteService::sistema).

// a régua do Dividir: o cartão que caiu no período (o contrário entra negativo) e o cancelamento
// feito durante o período (com o sinal invertido, na hora do cancelamento), em ordem de hora
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

const MEIO_DEBITO = 4
const MODALIDADES = { debito: 'Débito', vista: 'Crédito à vista', parcelado: 'Crédito parcelado' }
const ORDEM = Object.keys(MODALIDADES)

// a modalidade do relatório da maquineta (MaquinetaLoteService::modalidade)
const modalidade = (l) =>
  l.meio === MEIO_DEBITO ? 'debito' : (l.parcelas ?? 1) > 1 ? 'parcelado' : 'vista'
const bandeira = (l) => (l.bandeira ? (l.bandeiradescricao ?? 'Outros') : 'Sem bandeira')
const porHora = (a, b) => new Date(a.momento) - new Date(b.momento)
const soma = (linhas) => Math.round(linhas.reduce((s, x) => s + x.valor, 0) * 100) / 100

// o detalhe na ordem do papel: modalidade → bandeira (alfabética, sem bandeira por último) →
// hora. A venda cancelada no próprio período vem marcada `cancelada` (fica fora da quantidade e do
// valor do bloco); o cancelamento de venda de outro período e o estorno vão para `cancelamentos`,
// negativos
export function borderoDoPeriodo(periodo) {
  const cod = periodo?.codmaquinetalote
  const blocos = {}
  const cancelamentos = []
  for (const l of periodo?.lancamentos ?? []) {
    const daqui = l.codmaquinetalote === cod
    const canceladoAqui = l.codmaquinetalotecancelamento === cod
    const contrario = l.operacao === 'DB'
    if (!daqui || contrario) {
      if (daqui && canceladoAqui) continue
      cancelamentos.push({
        chave: `${l.codpagamento}-c`,
        momento: daqui ? l.transacao : (l.cancelamento ?? l.transacao),
        valor: -(daqui ? 1 : contrario ? -1 : 1) * l.total,
        outroPeriodo: !daqui,
        l,
      })
      continue
    }
    const mod = modalidade(l)
    const band = bandeira(l)
    const chave = `${mod}|${band}`
    blocos[chave] ??= {
      chave,
      ordem: [ORDEM.indexOf(mod), !l.bandeira, band],
      titulo: `${MODALIDADES[mod]} · ${band}`,
      linhas: [],
    }
    blocos[chave].linhas.push({
      chave: `${l.codpagamento}`,
      momento: l.transacao,
      valor: l.total,
      cancelada: canceladoAqui,
      l,
    })
  }
  const lista = Object.values(blocos)
    .sort((a, b) => {
      for (let i = 0; i < 3; i++) {
        if (a.ordem[i] !== b.ordem[i]) return a.ordem[i] < b.ordem[i] ? -1 : 1
      }
      return 0
    })
    .map((b) => {
      const valendo = b.linhas.filter((x) => !x.cancelada)
      return {
        ...b,
        linhas: b.linhas.sort(porHora),
        quantidade: valendo.length,
        valor: soma(valendo),
      }
    })
  return {
    blocos: lista,
    cancelados: lista.reduce((s, b) => s + b.linhas.length - b.quantidade, 0),
    cancelamentos: cancelamentos.sort(porHora),
  }
}
