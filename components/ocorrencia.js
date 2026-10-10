// Livro de ocorrencias (TASK-205): espelho dos codigos de
// api/app/Mg/Ocorrencia/OcorrenciaService.php

export const TIPO = {
  ITEM_EXCLUIDO: 1,
  QUANTIDADE_DIMINUIDA: 2,
  PRECO_ABAIXO: 3,
  PRECO_ACIMA: 4,
  VALE_EXCLUIDO: 5,
  PAGAMENTO_APAGADO: 6,
  PARCELA_APAGADA: 7,
  SEM_FINANCEIRO: 8,
  NEGOCIO_CANCELADO: 10,
  PAGAMENTO_ESTORNADO: 11,
  VALE_ESTORNADO: 12,
  DESCONTO_ACIMA: 13,
  NEGOCIO_ESQUECIDO: 14,
  DATA_ALTERADA: 15,
  DATA_CANCELAMENTO_ALTERADA: 16,
  CORRIGIDO_CONFERENCIA: 17,
  REGISTRO_INDEVIDO: 18,
  INCLUIDO_CONFERENCIA: 19,
}

export const TIPOS = {
  [TIPO.ITEM_EXCLUIDO]: 'Item excluído',
  [TIPO.QUANTIDADE_DIMINUIDA]: 'Quantidade diminuída',
  [TIPO.PRECO_ABAIXO]: 'Preço abaixo do cadastro',
  [TIPO.PRECO_ACIMA]: 'Preço acima do cadastro',
  [TIPO.VALE_EXCLUIDO]: 'Vale compras excluído',
  [TIPO.PAGAMENTO_APAGADO]: 'Pagamento apagado',
  [TIPO.PARCELA_APAGADA]: 'Parcela a prazo apagada',
  [TIPO.SEM_FINANCEIRO]: 'Saída sem financeiro',
  [TIPO.NEGOCIO_CANCELADO]: 'Negócio cancelado',
  [TIPO.PAGAMENTO_ESTORNADO]: 'Pagamento estornado',
  [TIPO.VALE_ESTORNADO]: 'Vale estornado',
  [TIPO.DESCONTO_ACIMA]: 'Desconto acima do permitido',
  [TIPO.NEGOCIO_ESQUECIDO]: 'Negócio esquecido',
  [TIPO.DATA_ALTERADA]: 'Data alterada',
  [TIPO.DATA_CANCELAMENTO_ALTERADA]: 'Data do cancelamento alterada',
  [TIPO.CORRIGIDO_CONFERENCIA]: 'Corrigido na conferência',
  [TIPO.REGISTRO_INDEVIDO]: 'Registro indevido',
  [TIPO.INCLUIDO_CONFERENCIA]: 'Incluído na conferência',
}
