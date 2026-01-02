/**
 * Constantes e opções reutilizáveis para Notas Fiscais
 */

/**
 * Configurações de ícones e cores por ente tributário
 */
export const ENTE_CONFIG = {
  FEDERAL: {
    icon: 'account_balance',
    color: 'blue',
  },
  ESTADUAL: {
    icon: 'location_city',
    color: 'green',
  },
  MUNICIPAL: {
    icon: 'home_work',
    color: 'orange',
  },
}

/**
 * Retorna o ícone do ente tributário
 */
export const getEnteIcon = (ente) => {
  return ENTE_CONFIG[ente]?.icon || 'gavel'
}

/**
 * Retorna a cor do ente tributário
 */
export const getEnteColor = (ente) => {
  return ENTE_CONFIG[ente]?.color || 'grey'
}

export const MODELO_OPTIONS = [
  { label: 'NF-e (55)', value: '55' },
  { label: 'NFC-e (65)', value: '65' },
]

export const STATUS_OPTIONS = [
  { label: 'Lançada', value: 'LAN' },
  { label: 'Em Digitação', value: 'DIG' },
  { label: 'Não Autorizada', value: 'ERR' },
  { label: 'Autorizada', value: 'AUT' },
  { label: 'Cancelada', value: 'CAN' },
  { label: 'Inutilizada', value: 'INU' },
]

export const EMITIDA_OPTIONS = [
  { label: 'Nossa Emissão', value: true },
  { label: 'Emitida por Terceiro', value: false },
]

export const OPERACAO_OPTIONS = [
  { label: 'Entrada', value: 0 },
  { label: 'Saída', value: 1 },
]

/**
 * Retorna a label do status baseado no valor
 */
export const getStatusLabel = (status) => {
  const option = STATUS_OPTIONS.find((opt) => opt.value === status)
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
    INU: 'warning',
  }
  return colors[status] || 'grey'
}

/**
 * Retorna a label do modelo baseado no valor
 */
export const getModeloLabel = (modelo) => {
  const option = MODELO_OPTIONS.find((opt) => opt.value === String(modelo))
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
  const option = TIPO_PAGAMENTO_OPTIONS.find((opt) => opt.value === tipo)
  return option ? option.label : `Tipo ${tipo}`
}

/**
 * Retorna label da bandeira do cartão
 */
export const getBandeiraLabel = (bandeira) => {
  const option = BANDEIRA_CARTAO_OPTIONS.find((opt) => opt.value === bandeira)
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
  { label: '00 - Tributada integralmente', value: 0 },
  { label: '10 - Tributada e com cobrança do ICMS por substituição tributária', value: 10 },
  { label: '20 - Com redução de base de cálculo', value: 20 },
  {
    label: '30 - Isenta ou não tributada e com cobrança do ICMS por substituição tributária',
    value: 30,
  },
  { label: '40 - Isenta', value: 40 },
  { label: '41 - Não tributada', value: 41 },
  { label: '50 - Suspensão', value: 50 },
  { label: '51 - Diferimento', value: 51 },
  { label: '60 - ICMS cobrado anteriormente por substituição tributária', value: 60 },
  {
    label: '70 - Com redução de base de cálculo e cobrança do ICMS por substituição tributária',
    value: 70,
  },
  { label: '90 - Outras', value: 90 },
]

/**
 * CSOSN - Código de Situação da Operação no Simples Nacional
 */
export const CSOSN_OPTIONS = [
  { label: '101 - Tributada pelo Simples Nacional com permissão de crédito', value: 101 },
  { label: '102 - Tributada pelo Simples Nacional sem permissão de crédito', value: 102 },
  { label: '103 - Isenção do ICMS no Simples Nacional para faixa de receita bruta', value: 103 },
  {
    label:
      '201 - Tributada pelo Simples Nacional com permissão de crédito e com cobrança do ICMS por substituição tributária',
    value: 201,
  },
  {
    label:
      '202 - Tributada pelo Simples Nacional sem permissão de crédito e com cobrança do ICMS por substituição tributária',
    value: 202,
  },
  {
    label:
      '203 - Isenção do ICMS no Simples Nacional para faixa de receita bruta e com cobrança do ICMS por substituição tributária',
    value: 203,
  },
  { label: '300 - Imune', value: 300 },
  { label: '400 - Não tributada pelo Simples Nacional', value: 400 },
  {
    label: '500 - ICMS cobrado anteriormente por substituição tributária ou por antecipação',
    value: 500,
  },
  { label: '900 - Outros', value: 900 },
]

/**
 * CST IPI - Código de Situação Tributária do IPI
 */
export const IPI_CST_OPTIONS = [
  { label: '00 - Entrada com Recuperação de Crédito', value: 0 },
  { label: '01 - Entrada Tributada com Alíquota Zero', value: 1 },
  { label: '02 - Entrada Isenta', value: 2 },
  { label: '03 - Entrada Não-Tributada', value: 3 },
  { label: '04 - Entrada Imune', value: 4 },
  { label: '05 - Entrada com Suspensão', value: 5 },
  { label: '49 - Outras Entradas', value: 49 },
  { label: '50 - Saída Tributada', value: 50 },
  { label: '51 - Saída Tributada com Alíquota Zero', value: 51 },
  { label: '52 - Saída Isenta', value: 52 },
  { label: '53 - Saída Não-Tributada', value: 53 },
  { label: '54 - Saída Imune', value: 54 },
  { label: '55 - Saída com Suspensão', value: 55 },
  { label: '99 - Outras Saídas', value: 99 },
]

/**
 * CST PIS - Código de Situação Tributária do PIS
 */
export const PIS_CST_OPTIONS = [
  { label: '01 - Operação Tributável com Alíquota Básica', value: 1 },
  { label: '02 - Operação Tributável com Alíquota Diferenciada', value: 2 },
  { label: '03 - Operação Tributável com Alíquota por Unidade de Medida de Produto', value: 3 },
  { label: '04 - Operação Tributável Monofásica - Revenda a Alíquota Zero', value: 4 },
  { label: '05 - Operação Tributável por Substituição Tributária', value: 5 },
  { label: '06 - Operação Tributável a Alíquota Zero', value: 6 },
  { label: '07 - Operação Isenta da Contribuição', value: 7 },
  { label: '08 - Operação sem Incidência da Contribuição', value: 8 },
  { label: '09 - Operação com Suspensão da Contribuição', value: 9 },
  { label: '49 - Outras Operações de Saída', value: 49 },
  {
    label:
      '50 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita Tributada no Mercado Interno',
    value: 50,
  },
  {
    label:
      '51 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita Não Tributada no Mercado Interno',
    value: 51,
  },
  {
    label: '52 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita de Exportação',
    value: 52,
  },
  {
    label:
      '53 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno',
    value: 53,
  },
  {
    label:
      '54 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas no Mercado Interno e de Exportação',
    value: 54,
  },
  {
    label:
      '55 - Operação com Direito a Crédito - Vinculada a Receitas Não-Tributadas no Mercado Interno e de Exportação',
    value: 55,
  },
  {
    label:
      '56 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno, e de Exportação',
    value: 56,
  },
  {
    label:
      '60 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita Tributada no Mercado Interno',
    value: 60,
  },
  {
    label:
      '61 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita Não-Tributada no Mercado Interno',
    value: 61,
  },
  {
    label:
      '62 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita de Exportação',
    value: 62,
  },
  {
    label:
      '63 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno',
    value: 63,
  },
  {
    label:
      '64 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas no Mercado Interno e de Exportação',
    value: 64,
  },
  {
    label:
      '65 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Não-Tributadas no Mercado Interno e de Exportação',
    value: 65,
  },
  {
    label:
      '66 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno, e de Exportação',
    value: 66,
  },
  { label: '67 - Crédito Presumido - Outras Operações', value: 67 },
  { label: '70 - Operação de Aquisição sem Direito a Crédito', value: 70 },
  { label: '71 - Operação de Aquisição com Isenção', value: 71 },
  { label: '72 - Operação de Aquisição com Suspensão', value: 72 },
  { label: '73 - Operação de Aquisição a Alíquota Zero', value: 73 },
  { label: '74 - Operação de Aquisição sem Incidência da Contribuição', value: 74 },
  { label: '75 - Operação de Aquisição por Substituição Tributária', value: 75 },
  { label: '98 - Outras Operações de Entrada', value: 98 },
  { label: '99 - Outras Operações', value: 99 },
]

/**
 * CST COFINS - Código de Situação Tributária do COFINS
 */
export const COFINS_CST_OPTIONS = [
  { label: '01 - Operação Tributável com Alíquota Básica', value: 1 },
  { label: '02 - Operação Tributável com Alíquota Diferenciada', value: 2 },
  { label: '03 - Operação Tributável com Alíquota por Unidade de Medida de Produto', value: 3 },
  { label: '04 - Operação Tributável Monofásica - Revenda a Alíquota Zero', value: 4 },
  { label: '05 - Operação Tributável por Substituição Tributária', value: 5 },
  { label: '06 - Operação Tributável a Alíquota Zero', value: 6 },
  { label: '07 - Operação Isenta da Contribuição', value: 7 },
  { label: '08 - Operação sem Incidência da Contribuição', value: 8 },
  { label: '09 - Operação com Suspensão da Contribuição', value: 9 },
  { label: '49 - Outras Operações de Saída', value: 49 },
  {
    label:
      '50 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita Tributada no Mercado Interno',
    value: 50,
  },
  {
    label:
      '51 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita Não Tributada no Mercado Interno',
    value: 51,
  },
  {
    label: '52 - Operação com Direito a Crédito - Vinculada Exclusivamente a Receita de Exportação',
    value: 52,
  },
  {
    label:
      '53 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno',
    value: 53,
  },
  {
    label:
      '54 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas no Mercado Interno e de Exportação',
    value: 54,
  },
  {
    label:
      '55 - Operação com Direito a Crédito - Vinculada a Receitas Não-Tributadas no Mercado Interno e de Exportação',
    value: 55,
  },
  {
    label:
      '56 - Operação com Direito a Crédito - Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno, e de Exportação',
    value: 56,
  },
  {
    label:
      '60 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita Tributada no Mercado Interno',
    value: 60,
  },
  {
    label:
      '61 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita Não-Tributada no Mercado Interno',
    value: 61,
  },
  {
    label:
      '62 - Crédito Presumido - Operação de Aquisição Vinculada Exclusivamente a Receita de Exportação',
    value: 62,
  },
  {
    label:
      '63 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno',
    value: 63,
  },
  {
    label:
      '64 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas no Mercado Interno e de Exportação',
    value: 64,
  },
  {
    label:
      '65 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Não-Tributadas no Mercado Interno e de Exportação',
    value: 65,
  },
  {
    label:
      '66 - Crédito Presumido - Operação de Aquisição Vinculada a Receitas Tributadas e Não-Tributadas no Mercado Interno, e de Exportação',
    value: 66,
  },
  { label: '67 - Crédito Presumido - Outras Operações', value: 67 },
  { label: '70 - Operação de Aquisição sem Direito a Crédito', value: 70 },
  { label: '71 - Operação de Aquisição com Isenção', value: 71 },
  { label: '72 - Operação de Aquisição com Suspensão', value: 72 },
  { label: '73 - Operação de Aquisição a Alíquota Zero', value: 73 },
  { label: '74 - Operação de Aquisição sem Incidência da Contribuição', value: 74 },
  { label: '75 - Operação de Aquisição por Substituição Tributária', value: 75 },
  { label: '98 - Outras Operações de Entrada', value: 98 },
  { label: '99 - Outras Operações', value: 99 },
]

export const CBS_CST_OPTIONS = [
  { label: '000 - Tributação integral', value: '000' },
  { label: '010 - Tributação com alíquotas uniformes', value: '010' },
  { label: '011 - Tributação com alíquotas uniformes reduzidas', value: '011' },
  { label: '200 - Alíquota reduzida', value: '200' },
  { label: '220 - Alíquota fixa', value: '220' },
  { label: '221 - Alíquota fixa proporcional', value: '221' },
  { label: '222 - Redução de Base de Cálculo', value: '222' },
  { label: '400 - Isenção', value: '400' },
  { label: '410 - Imunidade e não incidência', value: '410' },
  { label: '510 - Diferimento', value: '510' },
  { label: '515 - Diferimento com redução de alíquota', value: '515' },
  { label: '550 - Suspensão', value: '550' },
  { label: '620 - Tributação Monofásica', value: '620' },
  { label: '800 - Transferência de crédito', value: '800' },
  { label: '810 - Ajuste de IBS na ZFM', value: '810' },
  { label: '811 - Ajustes', value: '811' },
  { label: '820 - Tributação em declaração de regime específico', value: '820' },
  { label: '830 - Exclusão da Base de Cálculo', value: '830' },
]

export const CBS_CCLASSTRIB_OPTIONS = [
  {
    label: '000001 - Situações tributadas integralmente pelo IBS e CBS',
    value: '000001',
    cst: '000',
  },
  { label: '000002 - Exploração de via', value: '000002', cst: '000' },
  {
    label: '000003 - Regime automotivo - projetos incentivados (art. 311)',
    value: '000003',
    cst: '000',
  },
  {
    label: '000004 - Regime automotivo - projetos incentivados (art. 312)',
    value: '000004',
    cst: '000',
  },
  {
    label: '010001 - Operações do FGTS não realizadas pela Caixa Econômica Federal',
    value: '010001',
    cst: '010',
  },
  { label: '010002 - Operações do serviço financeiro', value: '010002', cst: '010' },
  { label: '011001 - Planos de assistência funerária', value: '011001', cst: '011' },
  { label: '011002 - Planos de assistência à saúde', value: '011002', cst: '011' },
  { label: '011003 - Intermediação de planos de assistência à saúde', value: '011003', cst: '011' },
  { label: '011004 - Concursos e prognósticos', value: '011004', cst: '011' },
  {
    label: '011005 - Planos de assistência à saúde de animais domésticos',
    value: '011005',
    cst: '011',
  },
  {
    label:
      '200001 - Aquisições realizadas entre empresas autorizadas a operar em zonas de processamento de exportação',
    value: '200001',
    cst: '200',
  },
  {
    label: '200002 - Fornecimento ou importação para produtor rural não contribuinte ou TAC',
    value: '200002',
    cst: '200',
  },
  {
    label: '200003 - Vendas de produtos destinados à alimentação humana (Anexo I)',
    value: '200003',
    cst: '200',
  },
  { label: '200004 - Venda de dispositivos médicos (Anexo XII)', value: '200004', cst: '200' },
  {
    label:
      '200005 - Venda de dispositivos médicos adquiridos por órgãos da administração pública (Anexo IV)',
    value: '200005',
    cst: '200',
  },
  {
    label:
      '200006 - Situação de emergência de saúde pública reconhecida pelo Poder público (Anexo XII)',
    value: '200006',
    cst: '200',
  },
  {
    label:
      '200007 - Fornecimento dos dispositivos de acessibilidade próprios para pessoas com deficiência (Anexo XIII)',
    value: '200007',
    cst: '200',
  },
  {
    label:
      '200008 - Fornecimento dos dispositivos de acessibilidade próprios para pessoas com deficiência adquiridos por órgãos da administração pública (Anexo V)',
    value: '200008',
    cst: '200',
  },
  { label: '200009 - Fornecimento de medicamentos (Anexo XIV)', value: '200009', cst: '200' },
  {
    label:
      '200010 - Fornecimento dos medicamentos registrados na Anvisa, adquiridos por órgãos da administração pública',
    value: '200010',
    cst: '200',
  },
  {
    label:
      '200011 - Fornecimento das composições para nutrição enteral e parenteral quando adquiridas por órgãos da administração pública (Anexo VI)',
    value: '200011',
    cst: '200',
  },
  {
    label:
      '200012 - Situação de emergência de saúde pública reconhecida pelo Poder público (Anexo XIV)',
    value: '200012',
    cst: '200',
  },
  {
    label:
      '200013 - Fornecimento de tampões higiênicos, absorventes higiênicos internos ou externos',
    value: '200013',
    cst: '200',
  },
  {
    label: '200014 - Fornecimento dos produtos hortícolas, frutas e ovos (Anexo XV)',
    value: '200014',
    cst: '200',
  },
  {
    label:
      '200015 - Venda de automóveis de passageiros de fabricação nacional adquiridos por motoristas profissionais ou pessoas com deficiência',
    value: '200015',
    cst: '200',
  },
  {
    label:
      '200016 - Prestação de serviços de pesquisa e desenvolvimento por Instituição Científica, Tecnológica e de Inovação (ICT)',
    value: '200016',
    cst: '200',
  },
  { label: '200017 - Operações relacionadas ao FGTS', value: '200017', cst: '200' },
  { label: '200018 - Operações de resseguro e retrocessão', value: '200018', cst: '200' },
  {
    label: '200019 - Importador dos serviços financeiros contribuinte',
    value: '200019',
    cst: '200',
  },
  {
    label:
      '200020 - Operação praticada por sociedades cooperativas optantes por regime específico do IBS e CBS',
    value: '200020',
    cst: '200',
  },
  {
    label:
      '200021 - Serviços de transporte público coletivo de passageiros ferroviário e hidroviário',
    value: '200021',
    cst: '200',
  },
  {
    label:
      '200022 - Operação originada fora da ZFM que destine bem material industrializado a contribuinte estabelecido na ZFM',
    value: '200022',
    cst: '200',
  },
  {
    label:
      '200023 - Operação realizada por indústria incentivada que destine bem material intermediário para outra indústria incentivada na ZFM',
    value: '200023',
    cst: '200',
  },
  {
    label:
      '200024 - Operação originada fora das Áreas de Livre Comércio destinadas a contribuinte estabelecido nas Áreas de Livre Comércio',
    value: '200024',
    cst: '200',
  },
  {
    label:
      '200025 - Fornecimento dos serviços de educação relacionados ao Programa Universidade para Todos (Prouni)',
    value: '200025',
    cst: '200',
  },
  {
    label: '200026 - Locação de imóveis localizados nas zonas reabilitadas',
    value: '200026',
    cst: '200',
  },
  {
    label: '200027 - Operações de locação, cessão onerosa e arrendamento de bens imóveis',
    value: '200027',
    cst: '200',
  },
  {
    label: '200028 - Fornecimento dos serviços de educação (Anexo II)',
    value: '200028',
    cst: '200',
  },
  {
    label: '200029 - Fornecimento dos serviços de saúde humana (Anexo III)',
    value: '200029',
    cst: '200',
  },
  { label: '200030 - Venda dos dispositivos médicos (Anexo IV)', value: '200030', cst: '200' },
  {
    label:
      '200031 - Fornecimento dos dispositivos de acessibilidade próprios para pessoas com deficiência (Anexo V)',
    value: '200031',
    cst: '200',
  },
  {
    label:
      '200032 - Fornecimento dos medicamentos registrados na Anvisa ou produzidos por farmácias de manipulação, ressalvados os medicamentos sujeitos à alíquota zero',
    value: '200032',
    cst: '200',
  },
  {
    label: '200033 - Fornecimento das composições para nutrição enteral e parenteral (Anexo VI)',
    value: '200033',
    cst: '200',
  },
  {
    label: '200034 - Fornecimento dos alimentos destinados ao consumo humano (Anexo VII)',
    value: '200034',
    cst: '200',
  },
  {
    label: '200035 - Fornecimento dos produtos de higiene pessoal e limpeza (Anexo VIII)',
    value: '200035',
    cst: '200',
  },
  {
    label:
      '200036 - Fornecimento de produtos agropecuários, aquícolas, pesqueiros, florestais e extrativistas vegetais in natura',
    value: '200036',
    cst: '200',
  },
  {
    label:
      '200037 - Fornecimento de serviços ambientais de conservação ou recuperação da vegetação nativa',
    value: '200037',
    cst: '200',
  },
  {
    label: '200038 - Fornecimento dos insumos agropecuários e aquícolas (Anexo IX)',
    value: '200038',
    cst: '200',
  },
  {
    label:
      '200039 - Fornecimento dos serviços e o licenciamento ou cessão dos direitos destinados às produções nacionais artísticas (Anexo X)',
    value: '200039',
    cst: '200',
  },
  {
    label: '200040 - Fornecimento de serviços de comunicação institucional à administração pública',
    value: '200040',
    cst: '200',
  },
  {
    label: '200041 - Fornecimento de serviço de educação desportiva (art. 141. I)',
    value: '200041',
    cst: '200',
  },
  {
    label: '200042 - Fornecimento de serviço de educação desportiva (art. 141. II)',
    value: '200042',
    cst: '200',
  },
  {
    label:
      '200043 - Fornecimento à administração pública dos serviços e dos bens relativos à soberania (Anexo XI)',
    value: '200043',
    cst: '200',
  },
  {
    label:
      '200044 - Operações e prestações de serviços de segurança da informação e segurança cibernética desenv por sociedade que tenha sócio brasileiro (Anexo XI)',
    value: '200044',
    cst: '200',
  },
  {
    label:
      '200045 - Operações relacionadas a projetos de reabilitação urbana de zonas históricas e de áreas críticas de recuperação e reconversão urbanística',
    value: '200045',
    cst: '200',
  },
  { label: '200046 - Operações com bens imóveis', value: '200046', cst: '200' },
  { label: '200047 - Bares e Restaurantes', value: '200047', cst: '200' },
  {
    label: '200048 - Hotelaria, Parques de Diversão e Parques Temáticos',
    value: '200048',
    cst: '200',
  },
  {
    label: '200049 - Transporte coletivo de passageiros rodoviário, ferroviário e hidroviário',
    value: '200049',
    cst: '200',
  },
  {
    label: '200050 - Serviços de transporte aéreo regional coletivo de passageiros ou de carga',
    value: '200050',
    cst: '200',
  },
  { label: '200051 - Agências de Turismo', value: '200051', cst: '200' },
  {
    label: '200052 - Prestação de serviços de profissões intelectuais',
    value: '200052',
    cst: '200',
  },
  {
    label: '220001 - Incorporação imobiliária submetida ao regime especial de tributação',
    value: '220001',
    cst: '220',
  },
  {
    label: '220002 - Incorporação imobiliária submetida ao regime especial de tributação',
    value: '220002',
    cst: '220',
  },
  {
    label: '220003 - Alienação de imóvel decorrente de parcelamento do solo',
    value: '220003',
    cst: '220',
  },
  {
    label:
      '221001 - Locação, cessão onerosa ou arrendamento de bem imóvel com alíquota sobre a receita bruta',
    value: '221001',
    cst: '221',
  },
  {
    label:
      '222001 - Transporte internacional de passageiros, caso os trechos de ida e volta sejam vendidos em conjunto',
    value: '222001',
    cst: '222',
  },
  {
    label:
      '400001 - Fornecimento de serviços de transporte público coletivo de passageiros rodoviário e metroviário',
    value: '400001',
    cst: '400',
  },
  {
    label:
      '410001 - Fornecimento de bonificações quando constem no documento fiscal e que não dependam de evento posterior',
    value: '410001',
    cst: '410',
  },
  {
    label: '410002 - Transferências entre estabelecimentos pertencentes ao mesmo contribuinte',
    value: '410002',
    cst: '410',
  },
  {
    label: '410003 - Doações sem contraprestação em benefício do doador',
    value: '410003',
    cst: '410',
  },
  { label: '410004 - Exportações de bens e serviços', value: '410004', cst: '410' },
  {
    label:
      '410005 - Fornecimentos realizados pela União, pelos Estados, pelo Distrito Federal e pelos Municípios',
    value: '410005',
    cst: '410',
  },
  {
    label: '410006 - Fornecimentos realizados por entidades religiosas e templos de qualquer culto',
    value: '410006',
    cst: '410',
  },
  {
    label: '410007 - Fornecimentos realizados por partidos políticos',
    value: '410007',
    cst: '410',
  },
  {
    label:
      '410008 - Fornecimentos de livros, jornais, periódicos e do papel destinado a sua impressão',
    value: '410008',
    cst: '410',
  },
  {
    label: '410009 - Fornecimentos de fonogramas e videofonogramas musicais produzidos no Brasil',
    value: '410009',
    cst: '410',
  },
  {
    label:
      '410010 - Fornecimentos de serviço de comunicação nas modalidades de radiodifusão sonora e de sons e imagens de recepção livre e gratuita',
    value: '410010',
    cst: '410',
  },
  {
    label:
      '410011 - Fornecimentos de ouro, quando definido em lei como ativo financeiro ou instrumento cambial',
    value: '410011',
    cst: '410',
  },
  {
    label: '410012 - Fornecimento de condomínio edilício não optante pelo regime regular',
    value: '410012',
    cst: '410',
  },
  { label: '410013 - Exportações de combustíveis', value: '410013', cst: '410' },
  {
    label: '410014 - Fornecimento de produtor rural não contribuinte',
    value: '410014',
    cst: '410',
  },
  {
    label: '410015 - Fornecimento por transportador autônomo não contribuinte',
    value: '410015',
    cst: '410',
  },
  { label: '410016 - Fornecimento ou aquisição de resíduos sólidos', value: '410016', cst: '410' },
  {
    label:
      '410017 - Aquisição de bem móvel com crédito presumido sob condição de revenda realizada',
    value: '410017',
    cst: '410',
  },
  {
    label:
      '410018 - Operações relacionadas aos fundos garantidores e executores de políticas públicas',
    value: '410018',
    cst: '410',
  },
  {
    label: '410019 - Exclusão da gorjeta na base de cálculo no fornecimento de alimentação',
    value: '410019',
    cst: '410',
  },
  {
    label:
      '410020 - Exclusão do valor de intermediação na base de cálculo no fornecimento de alimentação',
    value: '410020',
    cst: '410',
  },
  {
    label: '410021 - Contribuição de que trata o art. 149-A da Constituição Federal',
    value: '410021',
    cst: '410',
  },
  { label: '410022 - Consolidação da propriedade do bem pelo credor', value: '410022', cst: '410' },
  {
    label:
      '410023 - Alienação de bens móveis ou imóveis que tenham sido objeto de garantia em que o prestador da garantia não seja contribuinte',
    value: '410023',
    cst: '410',
  },
  {
    label: '410024 - Consolidação da propriedade do bem pelo grupo de consórcio',
    value: '410024',
    cst: '410',
  },
  {
    label:
      '410025 - Alienação de bem que tenha sido objeto de garantia em que o prestador da garantia não seja contribuinte',
    value: '410025',
    cst: '410',
  },
  { label: '410026 - Doação com anulação de crédito', value: '410026', cst: '410' },
  { label: '410027 - Exportação de serviço ou de bem imaterial', value: '410027', cst: '410' },
  {
    label:
      '410028 - Operações com bens imóveis realizadas por pessoas físicas não consideradas contribuintes',
    value: '410028',
    cst: '410',
  },
  { label: '410029 - Operações acobertadas somente pelo ICMS', value: '410029', cst: '410' },
  {
    label: '410030 - Estorno de crédito por perecimento, deteriorização, roubo, furto ou extravio',
    value: '410030',
    cst: '410',
  },
  {
    label:
      '410031 - Fornecimento em período anterior ao início de vigência de incidências de CBS e IBS',
    value: '410031',
    cst: '410',
  },
  {
    label:
      '410999 - Operações não onerosas sem previsão de tributação, não especificadas anteriormente',
    value: '410999',
    cst: '410',
  },
  {
    label:
      '510001 - Operações, sujeitas a diferimento, com energia elétrica, relativas à geração, comercialização, distribuição e transmissão',
    value: '510001',
    cst: '510',
  },
  {
    label:
      '515001 - Operações, sujeitas a diferimento, com insumos agropecuários e aquícolas (Anexo IX)',
    value: '515001',
    cst: '515',
  },
  { label: '550001 - Exportações de bens materiais', value: '550001', cst: '550' },
  { label: '550002 - Regime de Trânsito', value: '550002', cst: '550' },
  { label: '550003 - Regimes de Depósito (art. 85)', value: '550003', cst: '550' },
  { label: '550004 - Regimes de Depósito (art. 87)', value: '550004', cst: '550' },
  { label: '550005 - Regimes de Depósito (art. 87, Parágrafo único)', value: '550005', cst: '550' },
  { label: '550006 - Regimes de Permanência Temporária', value: '550006', cst: '550' },
  { label: '550007 - Regimes de Aperfeiçoamento', value: '550007', cst: '550' },
  {
    label: '550008 - Importação de bens para o Regime de Repetro-Temporário',
    value: '550008',
    cst: '550',
  },
  { label: '550009 - GNL-Temporário', value: '550009', cst: '550' },
  { label: '550010 - Repetro-Permanente', value: '550010', cst: '550' },
  { label: '550011 - Repetro-Industrialização', value: '550011', cst: '550' },
  { label: '550012 - Repetro-Nacional', value: '550012', cst: '550' },
  { label: '550013 - Repetro-Entreposto', value: '550013', cst: '550' },
  { label: '550014 - Zona de Processamento de Exportação', value: '550014', cst: '550' },
  {
    label:
      '550015 - Regime Tributário para Incentivo à Modernização e à Ampliação da Estrutura Portuária',
    value: '550015',
    cst: '550',
  },
  {
    label: '550016 - Regime Especial de Incentivos para o Desenvolvimento da Infraestrutura',
    value: '550016',
    cst: '550',
  },
  {
    label: '550017 - Regime Tributário para Incentivo à Atividade Econômica Naval',
    value: '550017',
    cst: '550',
  },
  { label: '550018 - Desoneração da aquisição de bens de capital', value: '550018', cst: '550' },
  {
    label: '550019 - Importação de bem material por indústria incentivada para utilização na ZFM',
    value: '550019',
    cst: '550',
  },
  { label: '550020 - Áreas de livre comércio', value: '550020', cst: '550' },
  { label: '550021 - Industrialização destinada a exportações', value: '550021', cst: '550' },
  { label: '620001 - Tributação monofásica sobre combustíveis', value: '620001', cst: '620' },
  {
    label: '620002 - Tributação monofásica com responsabilidade pela retenção sobre combustíveis',
    value: '620002',
    cst: '620',
  },
  {
    label:
      '620003 - Tributação monofásica com tributos retidos por responsabilidade sobre combustíveis',
    value: '620003',
    cst: '620',
  },
  {
    label:
      '620004 - Tributação monofásica sobre mistura de EAC com gasolina A em percentual superior ao obrigatório',
    value: '620004',
    cst: '620',
  },
  {
    label:
      '620005 - Tributação monofásica sobre mistura de EAC com gasolina A em percentual inferior ao obrigatório',
    value: '620005',
    cst: '620',
  },
  {
    label: '620006 - Tributação monofásica sobre combustíveis cobrada anteriormente',
    value: '620006',
    cst: '620',
  },
  { label: '800001 - Fusão, cisão ou incorporação', value: '800001', cst: '800' },
  {
    label: '800002 - Transferência de crédito do associado, inclusive as cooperativas singulares',
    value: '800002',
    cst: '800',
  },
  {
    label: '810001 - Crédito presumido sobre o valor apurado nos fornecimentos a partir da ZFM',
    value: '810001',
    cst: '810',
  },
  { label: '811001 - Anulação de Crédito por Saídas Imunes/Isentas', value: '811001', cst: '811' },
  {
    label: '811002 - Débitos de notas fiscais não processadas na apuração',
    value: '811002',
    cst: '811',
  },
  { label: '811003 - Desenquadramento do Simples Nacional', value: '811003', cst: '811' },
  {
    label:
      '820001 - Documento com informações de fornecimento de serviços de planos de assinst�ncia à saúde',
    value: '820001',
    cst: '820',
  },
  {
    label:
      '820002 - Documento com informações de fornecimento de serviços de planos de assistência funerária',
    value: '820002',
    cst: '820',
  },
  {
    label:
      '820003 - Documento com informações de fornecimento de serviços de planos de assistência à saúde de animais domésticos',
    value: '820003',
    cst: '820',
  },
  {
    label:
      '820004 - Documento com informações de prestação de serviços de consursos de prognósticos',
    value: '820004',
    cst: '820',
  },
  {
    label: '820005 - Documento com informações de alienação de bens imóveis',
    value: '820005',
    cst: '820',
  },
  {
    label: '820006 - Documento com informações de fornecimento de serviços de exploração de via',
    value: '820006',
    cst: '820',
  },
  {
    label: '820007 - Documento com informações de fornecimento de serviços financeiros',
    value: '820007',
    cst: '820',
  },
  {
    label:
      '820008 - Documento com informações de fornecimento, mas com tributação realizada em fatura anterior',
    value: '820008',
    cst: '820',
  },
  {
    label:
      '830001 - Documento com exclusão da BC da CBS e do IBS de energia elétrica fornecida pela distribuidora à UC',
    value: '830001',
    cst: '830',
  },
]
