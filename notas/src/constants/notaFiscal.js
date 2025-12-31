/**
 * Constantes e opções reutilizáveis para Notas Fiscais
 */

export const MODELO_OPTIONS = [
  { label: 'NF-e (55)', value: '55' },
  { label: 'NFC-e (65)', value: '65' }
]

export const STATUS_OPTIONS = [
  { label: 'Lançada', value: 'LAN' },
  { label: 'Em Digitação', value: 'DIG' },
  { label: 'Não Autorizada', value: 'ERR' },
  { label: 'Autorizada', value: 'AUT' },
  { label: 'Cancelada', value: 'CAN' },
  { label: 'Inutilizada', value: 'INU' }
]

export const EMITIDA_OPTIONS = [
  { label: 'Nossa Emissão', value: true },
  { label: 'Emitida por Terceiro', value: false }
]

export const OPERACAO_OPTIONS = [
  { label: 'Entrada', value: 0 },
  { label: 'Saída', value: 1 }
]

/**
 * Retorna a label do status baseado no valor
 */
export const getStatusLabel = (status) => {
  const option = STATUS_OPTIONS.find(opt => opt.value === status)
  return option ? option.label : status
}

/**
 * Retorna a cor do status para badges/chips
 */
export const getStatusColor = (status) => {
  const colors = {
    LAN: 'blue-grey',
    DIG: 'blue',
    ERR: 'deep-orange',
    AUT: 'positive',
    CAN: 'negative',
    INU: 'warning'
  }
  return colors[status] || 'grey'
}

/**
 * Retorna a label do modelo baseado no valor
 */
export const getModeloLabel = (modelo) => {
  const option = MODELO_OPTIONS.find(opt => opt.value === String(modelo))
  return option ? option.label : `Modelo ${modelo}`
}

/**
 * Retorna se a nota está bloqueada para edição/exclusão
 */
export const isNotaBloqueada = (status) => {
  return ['AUT', 'CAN', 'INU'].includes(status)
}

/**
 * Retorna o ícone apropriado para o tipo de pagamento
 */
export const getPagamentoIcon = (tipo) => {
  const icons = {
    1: 'payments', // Dinheiro
    2: 'receipt', // Cheque
    3: 'credit_card', // Cartão de Crédito
    4: 'credit_card', // Cartão de Débito
    5: 'store', // Crédito Loja
    10: 'restaurant', // Vale Alimentação
    11: 'restaurant', // Vale Refeição
    12: 'card_giftcard', // Vale Presente
    13: 'local_gas_station', // Vale Combustível
    15: 'receipt_long', // Boleto Bancário
    16: 'account_balance', // Depósito Bancário
    17: 'pix', // PIX
    18: 'account_balance_wallet', // Transferência/Carteira Digital
    19: 'stars', // Programa de fidelidade
    90: 'money_off', // Sem pagamento
    99: 'payment', // Outros
  }
  return icons[tipo] || 'payment'
}

/**
 * Retorna a cor apropriada para o tipo de pagamento
 */
export const getPagamentoColor = (tipo) => {
  const colors = {
    1: 'green', // Dinheiro
    2: 'blue-grey', // Cheque
    3: 'deep-orange', // Cartão de Crédito
    4: 'blue', // Cartão de Débito
    5: 'purple', // Crédito Loja
    10: 'orange', // Vale Alimentação
    11: 'amber', // Vale Refeição
    12: 'pink', // Vale Presente
    13: 'red', // Vale Combustível
    15: 'indigo', // Boleto Bancário
    16: 'cyan', // Depósito Bancário
    17: 'teal', // PIX
    18: 'light-blue', // Transferência/Carteira Digital
    19: 'yellow-9', // Programa de fidelidade
    90: 'grey', // Sem pagamento
    99: 'grey-7', // Outros
  }
  return colors[tipo] || 'primary'
}

/**
 * Retorna a label do tipo de frete
 */
export const getFreteLabel = (frete) => {
  const labels = {
    0: 'Emitente (Terceirizado)',
    1: 'Destinatário (Terceirizado)',
    2: 'Terceiro (Nem Emitente, Nem Destinatário)',
    3: 'Emitente (Próprio)',
    4: 'Destinatario (Próprio)',
    9: 'Sem Frete',
  }
  return labels[frete] || '-'
}

/**
 * Tipos de Pagamento da NFe
 */
export const TIPO_PAGAMENTO_OPTIONS = [
  { label: 'Dinheiro', value: 1 },
  { label: 'Cheque', value: 2 },
  { label: 'Cartão de Crédito', value: 3 },
  { label: 'Cartão de Débito', value: 4 },
  { label: 'Crédito Loja', value: 5 },
  { label: 'Vale Alimentação', value: 10 },
  { label: 'Vale Refeição', value: 11 },
  { label: 'Vale Presente', value: 12 },
  { label: 'Vale Combustível', value: 13 },
  { label: 'Boleto Bancário', value: 15 },
  { label: 'Depósito Bancário', value: 16 },
  { label: 'PIX', value: 17 },
  { label: 'Transferência/Carteira Digital', value: 18 },
  { label: 'Programa de Fidelidade', value: 19 },
  { label: 'Sem Pagamento', value: 90 },
  { label: 'Outros', value: 99 },
]

/**
 * Bandeiras de Cartão
 */
export const BANDEIRA_CARTAO_OPTIONS = [
  { label: 'Visa', value: 1 },
  { label: 'Mastercard', value: 2 },
  { label: 'American Express', value: 3 },
  { label: 'Sorocred', value: 4 },
  { label: 'Diners Club', value: 5 },
  { label: 'Elo', value: 6 },
  { label: 'Hipercard', value: 7 },
  { label: 'Aura', value: 8 },
  { label: 'Cabal', value: 9 },
  { label: 'Outros', value: 99 },
]

/**
 * Retorna label do tipo de pagamento
 */
export const getTipoPagamentoLabel = (tipo) => {
  const option = TIPO_PAGAMENTO_OPTIONS.find(opt => opt.value === tipo)
  return option ? option.label : `Tipo ${tipo}`
}

/**
 * Retorna label da bandeira do cartão
 */
export const getBandeiraLabel = (bandeira) => {
  const option = BANDEIRA_CARTAO_OPTIONS.find(opt => opt.value === bandeira)
  return option ? option.label : `Bandeira ${bandeira}`
}

/**
 * Verifica se o tipo de pagamento requer bandeira de cartão
 */
export const tipoPagamentoRequerBandeira = (tipo) => {
  return [3, 4].includes(tipo) // Crédito ou Débito
}
