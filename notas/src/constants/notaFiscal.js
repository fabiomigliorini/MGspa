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

/**
 * CST ICMS - Código de Situação Tributária do ICMS
 */
export const ICMS_CST_OPTIONS = [
  { label: '00 - Tributada integralmente', value: '00' },
  { label: '10 - Tributada e com cobrança do ICMS por substituição tributária', value: '10' },
  { label: '20 - Com redução de base de cálculo', value: '20' },
  { label: '30 - Isenta ou não tributada e com cobrança do ICMS por substituição tributária', value: '30' },
  { label: '40 - Isenta', value: '40' },
  { label: '41 - Não tributada', value: '41' },
  { label: '50 - Suspensão', value: '50' },
  { label: '51 - Diferimento', value: '51' },
  { label: '60 - ICMS cobrado anteriormente por substituição tributária', value: '60' },
  { label: '70 - Com redução de base de cálculo e cobrança do ICMS por substituição tributária', value: '70' },
  { label: '90 - Outras', value: '90' },
]

/**
 * CSOSN - Código de Situação da Operação no Simples Nacional
 */
export const CSOSN_OPTIONS = [
  { label: '101 - Tributada pelo Simples Nacional com permissão de crédito', value: '101' },
  { label: '102 - Tributada pelo Simples Nacional sem permissão de crédito', value: '102' },
  { label: '103 - Isenção do ICMS no Simples Nacional para faixa de receita bruta', value: '103' },
  { label: '201 - Tributada pelo Simples Nacional com permissão de crédito e com cobrança do ICMS por substituição tributária', value: '201' },
  { label: '202 - Tributada pelo Simples Nacional sem permissão de crédito e com cobrança do ICMS por substituição tributária', value: '202' },
  { label: '203 - Isenção do ICMS no Simples Nacional para faixa de receita bruta e com cobrança do ICMS por substituição tributária', value: '203' },
  { label: '300 - Imune', value: '300' },
  { label: '400 - Não tributada pelo Simples Nacional', value: '400' },
  { label: '500 - ICMS cobrado anteriormente por substituição tributária ou por antecipação', value: '500' },
  { label: '900 - Outros', value: '900' },
]

/**
 * CST IPI - Código de Situação Tributária do IPI
 */
export const IPI_CST_OPTIONS = [
  { label: '00 - Entrada com Recuperação de Crédito', value: '00' },
  { label: '01 - Entrada Tributada com Alíquota Zero', value: '01' },
  { label: '02 - Entrada Isenta', value: '02' },
  { label: '03 - Entrada Não-Tributada', value: '03' },
  { label: '04 - Entrada Imune', value: '04' },
  { label: '05 - Entrada com Suspensão', value: '05' },
  { label: '49 - Outras Entradas', value: '49' },
  { label: '50 - Saída Tributada', value: '50' },
  { label: '51 - Saída Tributada com Alíquota Zero', value: '51' },
  { label: '52 - Saída Isenta', value: '52' },
  { label: '53 - Saída Não-Tributada', value: '53' },
  { label: '54 - Saída Imune', value: '54' },
  { label: '55 - Saída com Suspensão', value: '55' },
  { label: '99 - Outras Saídas', value: '99' },
]

/**
 * CST PIS - Código de Situação Tributária do PIS
 */
export const PIS_CST_OPTIONS = [
  { label: '01 - Operação Tributável com Alíquota Básica', value: '01' },
  { label: '02 - Operação Tributável com Alíquota Diferenciada', value: '02' },
  { label: '03 - Operação Tributável com Alíquota por Unidade de Medida de Produto', value: '03' },
  { label: '04 - Operação Tributável Monofásica - Revenda a Alíquota Zero', value: '04' },
  { label: '05 - Operação Tributável por Substituição Tributária', value: '05' },
  { label: '06 - Operação Tributável a Alíquota Zero', value: '06' },
  { label: '07 - Operação Isenta da Contribuição', value: '07' },
  { label: '08 - Operação sem Incidência da Contribuição', value: '08' },
  { label: '09 - Operação com Suspensão da Contribuição', value: '09' },
  { label: '49 - Outras Operações de Saída', value: '49' },
  { label: '50 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita Tributada no Mercado Interno', value: '50' },
  { label: '51 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita Não Tributada no Mercado Interno', value: '51' },
  { label: '52 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita de Exportação', value: '52' },
  { label: '53 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno', value: '53' },
  { label: '54 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas no Mercado Interno e de Exportação', value: '54' },
  { label: '55 - Operação com Direito a Crédito - Vinculada a Receitas Não-Tributadas no Mercado Interno e de Exportação', value: '55' },
  { label: '56 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno, e de Exportação', value: '56' },
  { label: '60 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita Tributada no Mercado Interno', value: '60' },
  { label: '61 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita Não-Tributada no Mercado Interno', value: '61' },
  { label: '62 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita de Exportação', value: '62' },
  { label: '63 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno', value: '63' },
  { label: '64 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas no Mercado Interno e de Exportação', value: '64' },
  { label: '65 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Não-Tributadas no Mercado Interno e de Exportação', value: '65' },
  { label: '66 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno, e de Exportação', value: '66' },
  { label: '67 - Crédito Presumido - Outras Operações', value: '67' },
  { label: '70 - Operação de Aquisição sem Direito a Crédito', value: '70' },
  { label: '71 - Operação de Aquisição com Isenção', value: '71' },
  { label: '72 - Operação de Aquisição com Suspensão', value: '72' },
  { label: '73 - Operação de Aquisição a Alíquota Zero', value: '73' },
  { label: '74 - Operação de Aquisição sem Incidência da Contribuição', value: '74' },
  { label: '75 - Operação de Aquisição por Substituição Tributária', value: '75' },
  { label: '98 - Outras Operações de Entrada', value: '98' },
  { label: '99 - Outras Operações', value: '99' },
]

/**
 * CST COFINS - Código de Situação Tributária do COFINS
 */
export const COFINS_CST_OPTIONS = [
  { label: '01 - Operação Tributável com Alíquota Básica', value: '01' },
  { label: '02 - Operação Tributável com Alíquota Diferenciada', value: '02' },
  { label: '03 - Operação Tributável com Alíquota por Unidade de Medida de Produto', value: '03' },
  { label: '04 - Operação Tributável Monofásica - Revenda a Alíquota Zero', value: '04' },
  { label: '05 - Operação Tributável por Substituição Tributária', value: '05' },
  { label: '06 - Operação Tributável a Alíquota Zero', value: '06' },
  { label: '07 - Operação Isenta da Contribuição', value: '07' },
  { label: '08 - Operação sem Incidência da Contribuição', value: '08' },
  { label: '09 - Operação com Suspensão da Contribuição', value: '09' },
  { label: '49 - Outras Operações de Saída', value: '49' },
  { label: '50 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita Tributada no Mercado Interno', value: '50' },
  { label: '51 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita Não Tributada no Mercado Interno', value: '51' },
  { label: '52 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita de Exportação', value: '52' },
  { label: '53 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno', value: '53' },
  { label: '54 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas no Mercado Interno e de Exportação', value: '54' },
  { label: '55 - Operação com Direito a Crédito - Vinculada a Receitas Não-Tributadas no Mercado Interno e de Exportação', value: '55' },
  { label: '56 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno, e de Exportação', value: '56' },
  { label: '60 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita Tributada no Mercado Interno', value: '60' },
  { label: '61 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita Não-Tributada no Mercado Interno', value: '61' },
  { label: '62 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita de Exportação', value: '62' },
  { label: '63 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno', value: '63' },
  { label: '64 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas no Mercado Interno e de Exportação', value: '64' },
  { label: '65 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Não-Tributadas no Mercado Interno e de Exportação', value: '65' },
  { label: '66 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno, e de Exportação', value: '66' },
  { label: '67 - Crédito Presumido - Outras Operações', value: '67' },
  { label: '70 - Operação de Aquisição sem Direito a Crédito', value: '70' },
  { label: '71 - Operação de Aquisição com Isenção', value: '71' },
  { label: '72 - Operação de Aquisição com Suspensão', value: '72' },
  { label: '73 - Operação de Aquisição a Alíquota Zero', value: '73' },
  { label: '74 - Operação de Aquisição sem Incidência da Contribuição', value: '74' },
  { label: '75 - Operação de Aquisição por Substituição Tributária', value: '75' },
  { label: '98 - Outras Operações de Entrada', value: '98' },
  { label: '99 - Outras Operações', value: '99' },
]
