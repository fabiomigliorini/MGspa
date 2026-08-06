// Casamento de entidades entre períodos.
//
// `codperiodocolaborador` e `codindicador` são PKs de tabelas period-scoped: o
// mesmo colaborador tem um ID diferente em cada período. Para manter o usuário
// na mesma tela ao trocar de período é preciso reencontrar o registro pela
// chave de NEGÓCIO, que é estável.
//
// Sem dependências de propósito (nem Vue, nem Quasar, nem store): é o que
// permite testar em node, sem browser.

/** Mesmo colaborador em outro período — `codcolaborador` é o vínculo estável. */
export const acharColaboradorEquivalente = (lista, codcolaborador) => {
  if (codcolaborador === null || codcolaborador === undefined) return null
  return (lista || []).find((c) => String(c.codcolaborador) === String(codcolaborador)) || null
}

/**
 * Identidade de negócio de um indicador. `tipo` sozinho não basta: uma unidade
 * tem N setores e um setor tem N vendedores/caixas.
 */
export const chaveIndicador = (ind) => {
  if (!ind || !ind.tipo) return null
  return [
    ind.tipo,
    ind.codunidadenegocio ?? '',
    ind.codsetor ?? '',
    ind.codcolaborador ?? '',
  ].join('|')
}

/** Mesmo indicador em outro período. */
export const acharIndicadorEquivalente = (lista, indicador) => {
  const chave = chaveIndicador(indicador)
  if (!chave) return null
  return (lista || []).find((i) => chaveIndicador(i) === chave) || null
}
