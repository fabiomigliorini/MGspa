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

// Esqueleto: o app Agro ainda não restringe acesso por grupo — qualquer
// usuário autenticado entra (rotas sem `meta.permissions`). Quando houver
// um grupo dedicado (ex: 'Agro'), adicione aqui e gate as rotas.
