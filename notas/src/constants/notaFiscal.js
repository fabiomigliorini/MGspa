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
