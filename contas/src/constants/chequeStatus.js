/**
 * Status do cheque (tblcheque.indstatus) — descrição + cor do badge.
 * Espelha as constantes do legado MGLara (Cheque::$indstatus_descricao).
 */
export const CHEQUE_STATUS = Object.freeze({
  1: { label: 'À Repassar', color: 'blue-7' },
  2: { label: 'Repassado', color: 'orange-7' },
  3: { label: 'Devolvido', color: 'red-7' },
  4: { label: 'Em Cobrança', color: 'deep-orange-9' },
  5: { label: 'Liquidado', color: 'green-7' },
})

export const CHEQUE_STATUS_OPTIONS = Object.entries(CHEQUE_STATUS).map(([value, v]) => ({
  value: Number(value),
  label: v.label,
}))

export function chequeStatusLabel(indstatus) {
  return CHEQUE_STATUS[indstatus]?.label ?? '—'
}

export function chequeStatusColor(indstatus) {
  return CHEQUE_STATUS[indstatus]?.color ?? 'grey-6'
}
