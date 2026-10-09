// Livro de ocorrencias (TASK-205): espelho dos codigos de
// api/app/Mg/Ocorrencia/OcorrenciaService.php

export const TIPO = {
  ITEM_EXCLUIDO: 1,
  QUANTIDADE_DIMINUIDA: 2,
  PRECO_DIMINUIDO: 3,
  PAGAMENTO_EXCLUIDO: 4,
  NEGOCIO_CANCELADO: 10,
  PAGAMENTO_ESTORNADO: 11,
  VALE_ESTORNADO: 12,
  DESCONTO_ACIMA: 13,
  NEGOCIO_ESQUECIDO: 14,
}

export const TIPOS = {
  [TIPO.ITEM_EXCLUIDO]: 'Item excluído',
  [TIPO.QUANTIDADE_DIMINUIDA]: 'Quantidade diminuída',
  [TIPO.PRECO_DIMINUIDO]: 'Preço diminuído',
  [TIPO.PAGAMENTO_EXCLUIDO]: 'Pagamento excluído',
  [TIPO.NEGOCIO_CANCELADO]: 'Negócio cancelado',
  [TIPO.PAGAMENTO_ESTORNADO]: 'Pagamento estornado',
  [TIPO.VALE_ESTORNADO]: 'Vale estornado',
  [TIPO.DESCONTO_ACIMA]: 'Desconto acima do permitido',
  [TIPO.NEGOCIO_ESQUECIDO]: 'Negócio esquecido',
}

export const MOTIVO_OUTRO = 9

export const MOTIVOS = {
  1: 'Bipou errado',
  2: 'Cliente desistiu',
  3: 'Preço diferente da gôndola',
  4: 'Produto com defeito',
  5: 'Valor digitado errado',
  6: 'Cliente trocou a forma de pagamento',
  [MOTIVO_OUTRO]: 'Outro',
}

// motivos que o caixa escolhe, por contexto
export const MOTIVOS_ITEM = [1, 2, 3, 4, MOTIVO_OUTRO]
export const MOTIVOS_PAGAMENTO = [5, 6, MOTIVO_OUTRO]
