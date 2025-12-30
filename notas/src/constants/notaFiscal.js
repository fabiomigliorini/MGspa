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
