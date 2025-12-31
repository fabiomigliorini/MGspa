/**
 * Funções de validação reutilizáveis
 */

/**
 * Valida o dígito verificador da chave de acesso da NFe
 * Utiliza o algoritmo Módulo 11
 * @param {string} chave - Chave de 44 dígitos
 * @returns {boolean} - true se a chave é válida
 */
export const validarChaveNFe = (chave) => {
  if (!chave || chave.length !== 44) {
    return false
  }

  // Remove qualquer caractere não numérico
  const chaveNumeros = chave.replace(/\D/g, '')

  if (chaveNumeros.length !== 44) {
    return false
  }

  // Os primeiros 43 dígitos
  const base = chaveNumeros.substring(0, 43)
  // O último dígito é o verificador
  const digitoVerificador = parseInt(chaveNumeros.charAt(43))

  // Calcula o dígito verificador usando Módulo 11
  let soma = 0
  let peso = 2

  // Percorre da direita para esquerda
  for (let i = base.length - 1; i >= 0; i--) {
    soma += parseInt(base.charAt(i)) * peso
    peso++
    if (peso > 9) {
      peso = 2
    }
  }

  const resto = soma % 11
  const digitoCalculado = resto === 0 || resto === 1 ? 0 : 11 - resto

  return digitoCalculado === digitoVerificador
}

/**
 * Valida CNPJ
 * @param {string} cnpj - CNPJ com ou sem formatação
 * @returns {boolean} - true se o CNPJ é válido
 */
export const validarCNPJ = (cnpj) => {
  if (!cnpj) return false

  // Remove formatação
  cnpj = cnpj.replace(/\D/g, '')

  if (cnpj.length !== 14) return false

  // Verifica se todos os dígitos são iguais
  if (/^(\d)\1+$/.test(cnpj)) return false

  // Validação do primeiro dígito verificador
  let tamanho = cnpj.length - 2
  let numeros = cnpj.substring(0, tamanho)
  const digitos = cnpj.substring(tamanho)
  let soma = 0
  let pos = tamanho - 7

  for (let i = tamanho; i >= 1; i--) {
    soma += numeros.charAt(tamanho - i) * pos--
    if (pos < 2) pos = 9
  }

  let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11)
  if (resultado !== parseInt(digitos.charAt(0))) return false

  // Validação do segundo dígito verificador
  tamanho = tamanho + 1
  numeros = cnpj.substring(0, tamanho)
  soma = 0
  pos = tamanho - 7

  for (let i = tamanho; i >= 1; i--) {
    soma += numeros.charAt(tamanho - i) * pos--
    if (pos < 2) pos = 9
  }

  resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11)
  if (resultado !== parseInt(digitos.charAt(1))) return false

  return true
}

/**
 * Valida CPF
 * @param {string} cpf - CPF com ou sem formatação
 * @returns {boolean} - true se o CPF é válido
 */
export const validarCPF = (cpf) => {
  if (!cpf) return false

  // Remove formatação
  cpf = cpf.replace(/\D/g, '')

  if (cpf.length !== 11) return false

  // Verifica se todos os dígitos são iguais
  if (/^(\d)\1+$/.test(cpf)) return false

  // Validação do primeiro dígito verificador
  let soma = 0
  for (let i = 0; i < 9; i++) {
    soma += parseInt(cpf.charAt(i)) * (10 - i)
  }
  let resto = 11 - (soma % 11)
  let digitoVerificador1 = resto === 10 || resto === 11 ? 0 : resto

  if (digitoVerificador1 !== parseInt(cpf.charAt(9))) return false

  // Validação do segundo dígito verificador
  soma = 0
  for (let i = 0; i < 10; i++) {
    soma += parseInt(cpf.charAt(i)) * (11 - i)
  }
  resto = 11 - (soma % 11)
  const digitoVerificador2 = resto === 10 || resto === 11 ? 0 : resto

  if (digitoVerificador2 !== parseInt(cpf.charAt(10))) return false

  return true
}
