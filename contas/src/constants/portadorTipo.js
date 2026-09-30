/**
 * Tipo do portador (tblportador.tipo) — descrição + cor do badge.
 * Espelha as constantes Portador::TIPO_* da API.
 */
export const PORTADOR_TIPO = Object.freeze({
  E: { label: 'Espécie', color: 'green-7' },
  B: { label: 'Banco', color: 'blue-7' },
  A: { label: 'Adquirente', color: 'purple-7' },
  C: { label: 'Cartão da empresa', color: 'orange-7' },
  O: { label: 'Outros', color: 'grey-7' },
})

export const PORTADOR_TIPO_OPTIONS = Object.entries(PORTADOR_TIPO).map(([value, v]) => ({
  value,
  label: v.label,
}))

export function portadorTipoLabel(tipo) {
  return PORTADOR_TIPO[tipo]?.label ?? '—'
}

export function portadorTipoColor(tipo) {
  return PORTADOR_TIPO[tipo]?.color ?? 'grey-6'
}
