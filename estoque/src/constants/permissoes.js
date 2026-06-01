/**
 * Grupos de usuário (permissões) do sistema MG Papelaria.
 * Sempre usar essas constantes em rotas e guards — nunca string literal.
 */
export const PERMISSOES = Object.freeze({
  ADMINISTRADOR: 'Administrador',
  GERENTE: 'Gerente',
  ESTOQUE: 'Estoque',
  COMPRAS: 'Compras',
  FINANCEIRO: 'Financeiro',
  CAIXA: 'Caixa',
  COBRANCA: 'Cobranca',
  PUBLICO: 'Publico',
  CONTADOR: 'Contador',
  RH: 'Recursos Humanos',
})

/**
 * Grupos que dão acesso geral ao app Estoque.
 * (Administrador sempre passa via `temAlgumaPermissao`.)
 */
export const PERMISSOES_ESTOQUE = [PERMISSOES.GERENTE, PERMISSOES.ESTOQUE, PERMISSOES.COMPRAS]
