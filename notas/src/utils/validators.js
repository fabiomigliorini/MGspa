/**
 * Funções de validação reutilizáveis
 */

import { documentoLimpo } from '@components/validador'

/**
 * Valida o dígito verificador da chave de acesso da NFe
 * Utiliza o algoritmo Módulo 11; cada caractere vale o código ASCII menos 48, porque o CNPJ
 * do emitente dentro da chave pode ter letras (CNPJ alfanumérico)
 * @param {string} chave - Chave de 44 caracteres
 * @returns {boolean} - true se a chave é válida
 */
export const validarChaveNFe = (chave) => {
  if (!chave) {
    return true
  }

  if (chave.length !== 44) {
    return false
  }

  // Remove qualquer caractere que não seja letra ou dígito
  const chaveNumeros = documentoLimpo(chave)

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
    soma += (base.charCodeAt(i) - 48) * peso
    peso++
    if (peso > 9) {
      peso = 2
    }
  }

  const resto = soma % 11
  const digitoCalculado = resto === 0 || resto === 1 ? 0 : 11 - resto

  return digitoCalculado === digitoVerificador
}
