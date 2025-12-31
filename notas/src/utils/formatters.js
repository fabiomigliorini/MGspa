/**
 * Funções utilitárias de formatação
 */

/**
 * Formata um valor monetário para o padrão brasileiro
 * @param {number|string} value - Valor a ser formatado
 * @returns {string} Valor formatado (ex: "1.234,56")
 */
export const formatCurrency = (value) => {
  if (!value) return '0,00'
  return parseFloat(value).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

/**
 * Formata um número decimal com quantidade específica de dígitos
 * @param {number|string} value - Valor a ser formatado
 * @param {number} digitos - Quantidade de casas decimais
 * @returns {string|null} Valor formatado ou null
 */
export const formatDecimal = (value, digitos) => {
  if (!value) return null
  return parseFloat(value).toLocaleString('pt-BR', {
    minimumFractionDigits: digitos,
    maximumFractionDigits: digitos,
  })
}

/**
 * Formata um NCM no padrão 9999.99.99
 * @param {number|string} ncm - Código NCM
 * @returns {string} NCM formatado ou "-"
 */
export const formatNCM = (ncm) => {
  if (!ncm) return '-'
  const ncmStr = String(ncm).padStart(8, '0')
  return `${ncmStr.substring(0, 4)}.${ncmStr.substring(4, 6)}.${ncmStr.substring(6, 8)}`
}

/**
 * Formata um CEST no padrão 99.999.99
 * @param {number|string} cest - Código CEST
 * @returns {string} CEST formatado ou "-"
 */
export const formatCEST = (cest) => {
  if (!cest) return '-'
  const cestStr = String(cest).padStart(7, '0')
  return `${cestStr.substring(0, 2)}.${cestStr.substring(2, 5)}.${cestStr.substring(5, 7)}`
}

/**
 * Formata uma data/hora completa para o padrão brasileiro
 * @param {string|Date} value - Data a ser formatada
 * @returns {string} Data formatada (ex: "31/12/2024, 23:59:59") ou "-"
 */
export const formatDateTime = (value) => {
  if (!value) return '-'
  const dateObj = new Date(value)
  return dateObj.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

/**
 * Formata uma data para o padrão brasileiro (sem hora)
 * @param {string|Date} value - Data a ser formatada
 * @returns {string} Data formatada (ex: "31/12/2024") ou "-"
 */
export const formatDate = (value) => {
  if (!value) return '-'
  const dateObj = new Date(value)
  return dateObj.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

/**
 * Formata um número de nota fiscal com zeros à esquerda
 * @param {number|string} numero - Número da nota fiscal
 * @returns {string} Número formatado com 8 dígitos
 */
export const formatNumero = (numero) => {
  if (!numero) return '00000000'
  return String(numero).padStart(8, '0')
}

/**
 * Formata uma chave de acesso NFe em grupos de 4 dígitos
 * @param {string} chave - Chave de acesso da NFe
 * @returns {string} Chave formatada (ex: "1234 5678 9012 ...") ou "-"
 */
export const formatChave = (chave) => {
  if (!chave) return '-'
  // Formata a chave em grupos de 4 dígitos
  return chave.match(/.{1,4}/g)?.join(' ') || chave
}
