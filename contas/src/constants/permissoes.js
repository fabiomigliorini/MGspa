/**
 * Grupos de usuário (permissões) do sistema MG Papelaria.
 * Sempre usar essas constantes em rotas e guards — nunca string literal.
 */
export const PERMISSOES = Object.freeze({
  ADMINISTRADOR: 'Administrador',
  FINANCEIRO: 'Financeiro',
  CAIXA: 'Caixa',
  PUBLICO: 'Publico',
  CONTADOR: 'Contador',
  RH: 'Recursos Humanos',
})
