/**
 * Integração da maquineta (tblmaquineta.integracao): nulo = manual, P = PagarMe, S = Saurus.
 * No filtro e no formulário, 'M' representa a manual.
 */
export const MAQUINETA_INTEGRACAO = Object.freeze({
  M: { label: 'Manual', color: 'grey-7' },
  P: { label: 'Stone integrada', color: 'green-8' },
  S: { label: 'SafraPay integrada', color: 'blue-8' },
})

export const MAQUINETA_INTEGRACAO_OPTIONS = Object.entries(MAQUINETA_INTEGRACAO).map(
  ([value, v]) => ({ value, label: v.label }),
)

export function maquinetaIntegracaoLabel(integracao) {
  return MAQUINETA_INTEGRACAO[integracao || 'M']?.label ?? '—'
}

export function maquinetaIntegracaoColor(integracao) {
  return MAQUINETA_INTEGRACAO[integracao || 'M']?.color ?? 'grey-6'
}
