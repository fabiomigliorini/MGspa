// Juros e multa de título a receber em atraso, conforme o legado MGsis/MGJuros.php:
// 4% ao mês pro rata, 2% de multa, depois de 3 dias de tolerância. Mesma regra na seleção de
// títulos do contas e no Receber título do PDV.
import { diasEntre, hoje } from '@components/utils/datas'

export const PARAMETROS_JUROS = { juros: 4, multa: 2, diasTolerancia: 3 }

const arredonda = (n) => Math.round(Number(n) * 100) / 100

export function diasAtraso(vencimento) {
  return vencimento ? diasEntre(vencimento, hoje()) : 0
}

// t = { vencimento, saldo, operacao }; operacao 'DB' = a receber
export function calcularJurosMulta(t, parametros = PARAMETROS_JUROS) {
  const dias = diasAtraso(t.vencimento)
  const valor = Number(t.saldo) || 0
  if (t.operacao === 'DB' && dias > parametros.diasTolerancia && valor > 0) {
    return {
      juros: arredonda(valor * (parametros.juros / 30 / 100) * dias),
      multa: arredonda(valor * (parametros.multa / 100)),
      dias,
    }
  }
  return { juros: 0, multa: 0, dias }
}
