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
 * Formata um número como percentual
 * @param {number|string} value - Valor a ser formatado
 * @param {number} decimals - Número de casas decimais (padrão: 2)
 * @returns {string} Percentual formatado (ex: "12.34%") ou "-"
 */
export const formatPercent = (value, decimals = 2) => {
  if (value === null || value === undefined) return '-'
  return `${parseFloat(value).toFixed(decimals)}%`
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
 * Formata um codproduto com zeros à esquerda
 * @param {number|string} numero - Número da nota fiscal
 * @returns {string} Número formatado com 8 dígitos
 */
export const formatCodProduto = (numero) => {
  if (!numero) return '#000000'
  return '#' + String(numero).padStart(6, '0')
}

/**
 * Formata um código de negócio com zeros à esquerda
 * @param {number|string} codnegocio - Código do negócio
 * @returns {string} Código formatado com # e 8 dígitos (ex: "#00001234")
 */
export const formatCodNegocio = (codnegocio) => {
  if (!codnegocio) return '#00000000'
  return '#' + String(codnegocio).padStart(8, '0')
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

export const formatProtocolo = (protocolo) => {
  if (!protocolo) return '-'
  // Formata o protocolo em grupos de 4 dígitos
  return protocolo.match(/.{1,5}/g)?.join(' ') || protocolo
}

export const formatCnpjCpf = (cnpj, fisica) => {
  if (!cnpj) return ''
  const str = cnpj.toString().padStart(fisica ? 11 : 14, '0')
  if (fisica) {
    // CPF: 000.000.000-00
    return str.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
  } else {
    // CNPJ: 00.000.000/0000-00
    return str.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
  }
}

/**
 * Formata Inscrição Estadual de acordo com o padrão de cada estado brasileiro
 * @param {string|number} ie - Inscrição Estadual
 * @param {string} uf - Sigla do estado (AC, AL, AM, etc.)
 * @returns {string} IE formatada ou valor original se não houver formatação específica
 */
export const formatInscricaoEstadual = (ie, uf) => {
  if (!ie) return ''

  // Remove caracteres não numéricos e converte para string
  const ieStr = ie.toString().replace(/\D/g, '')
  if (!ieStr) return ''

  // Se não tiver UF, retorna sem formatação
  if (!uf) return ieStr

  const ufUpper = uf.toUpperCase()

  try {
    switch (ufUpper) {
      case 'AC': // Acre - 13 dígitos - 00.000.000/000-00
        return ieStr
          .padStart(13, '0')
          .replace(/(\d{2})(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3/$4-$5')

      case 'AL': // Alagoas - 9 dígitos - 000000000
        return ieStr.padStart(9, '0')

      case 'AP': // Amapá - 9 dígitos - 00000000-0
        return ieStr.padStart(9, '0').replace(/(\d{8})(\d{1})/, '$1-$2')

      case 'AM': // Amazonas - 9 dígitos - 00.000.000-0
        return ieStr.padStart(9, '0').replace(/(\d{2})(\d{3})(\d{3})(\d{1})/, '$1.$2.$3-$4')

      case 'BA': // Bahia - 8 ou 9 dígitos - 000000-00 ou 0000000-00
        if (ieStr.length <= 8) {
          return ieStr.padStart(8, '0').replace(/(\d{6})(\d{2})/, '$1-$2')
        }
        return ieStr.padStart(9, '0').replace(/(\d{7})(\d{2})/, '$1-$2')

      case 'CE': // Ceará - 9 dígitos - 00000000-0
        return ieStr.padStart(9, '0').replace(/(\d{8})(\d{1})/, '$1-$2')

      case 'DF': // Distrito Federal - 13 dígitos - 00000000000-00
        return ieStr.padStart(13, '0').replace(/(\d{11})(\d{2})/, '$1-$2')

      case 'ES': // Espírito Santo - 9 dígitos - 000000000
        return ieStr.padStart(9, '0')

      case 'GO': // Goiás - 9 dígitos - 00.000.000-0
        return ieStr.padStart(9, '0').replace(/(\d{2})(\d{3})(\d{3})(\d{1})/, '$1.$2.$3-$4')

      case 'MA': // Maranhão - 9 dígitos - 00000000-0
        return ieStr.padStart(9, '0').replace(/(\d{8})(\d{1})/, '$1-$2')

      case 'MT': // Mato Grosso - 11 dígitos - 0000000000-0
        return ieStr.padStart(11, '0').replace(/(\d{10})(\d{1})/, '$1-$2')

      case 'MS': // Mato Grosso do Sul - 9 dígitos - 00000000-0
        return ieStr.padStart(9, '0').replace(/(\d{8})(\d{1})/, '$1-$2')

      case 'MG': // Minas Gerais - 13 dígitos - 000.000.000/0000
        return ieStr.padStart(13, '0').replace(/(\d{3})(\d{3})(\d{3})(\d{4})/, '$1.$2.$3/$4')

      case 'PA': // Pará - 9 dígitos - 00-000000-0
        return ieStr.padStart(9, '0').replace(/(\d{2})(\d{6})(\d{1})/, '$1-$2-$3')

      case 'PB': // Paraíba - 9 dígitos - 00000000-0
        return ieStr.padStart(9, '0').replace(/(\d{8})(\d{1})/, '$1-$2')

      case 'PR': // Paraná - 10 dígitos - 000.00000-00
        return ieStr.padStart(10, '0').replace(/(\d{3})(\d{5})(\d{2})/, '$1.$2-$3')

      case 'PE': // Pernambuco - 9 dígitos - 0000000-00
        return ieStr.padStart(9, '0').replace(/(\d{7})(\d{2})/, '$1-$2')

      case 'PI': // Piauí - 9 dígitos - 00000000-0
        return ieStr.padStart(9, '0').replace(/(\d{8})(\d{1})/, '$1-$2')

      case 'RJ': // Rio de Janeiro - 8 dígitos - 00.000.00-0
        return ieStr.padStart(8, '0').replace(/(\d{2})(\d{3})(\d{2})(\d{1})/, '$1.$2.$3-$4')

      case 'RN': // Rio Grande do Norte - 9 ou 10 dígitos - 00.000.000-0 ou 00.0.000.000-0
        if (ieStr.length <= 9) {
          return ieStr.padStart(9, '0').replace(/(\d{2})(\d{3})(\d{3})(\d{1})/, '$1.$2.$3-$4')
        }
        return ieStr
          .padStart(10, '0')
          .replace(/(\d{2})(\d{1})(\d{3})(\d{3})(\d{1})/, '$1.$2.$3.$4-$5')

      case 'RS': // Rio Grande do Sul - 10 dígitos - 000/0000000
        return ieStr.padStart(10, '0').replace(/(\d{3})(\d{7})/, '$1/$2')

      case 'RO': // Rondônia - 14 dígitos - 0000000000000-0
        return ieStr.padStart(14, '0').replace(/(\d{13})(\d{1})/, '$1-$2')

      case 'RR': // Roraima - 9 dígitos - 00000000-0
        return ieStr.padStart(9, '0').replace(/(\d{8})(\d{1})/, '$1-$2')

      case 'SC': // Santa Catarina - 9 dígitos - 000.000.000
        return ieStr.padStart(9, '0').replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3')

      case 'SP': // São Paulo - 12 dígitos - 000.000.000.000
        return ieStr.padStart(12, '0').replace(/(\d{3})(\d{3})(\d{3})(\d{3})/, '$1.$2.$3.$4')

      case 'SE': // Sergipe - 9 dígitos - 00000000-0
        return ieStr.padStart(9, '0').replace(/(\d{8})(\d{1})/, '$1-$2')

      case 'TO': // Tocantins - 11 dígitos - 00000000000
        return ieStr.padStart(11, '0')

      default:
        return ieStr
    }
  } catch (error) {
    // Em caso de erro, retorna o valor original
    console.log(error)
    return ieStr
  }
}

// Arredonda um número para uma quantidade específica de casas decimais
export const round = (value, decimals = 2) => {
  const factor = Math.pow(10, decimals)
  return Math.round((value || 0) * factor) / factor
}
