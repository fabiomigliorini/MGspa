// Validadores compartilhados. Vieram de negocios/src/utils/validador.js (a
// cópia mais completa); as cópias locais de negocios e pessoas saíram na TASK-283.

// CPF/CNPJ só com letras maiúsculas e dígitos, sem pontuação. O CNPJ
// alfanumérico (desde 07/2026) tem letras nas 12 primeiras posições.
export function documentoLimpo(v) {
  return String(v ?? '')
    .toUpperCase()
    .replace(/[^0-9A-Z]/g, '')
}

export function isCpfValido(cpf) {
  const d = String(cpf ?? '').replace(/\D/g, '')
  if (d.length !== 11 || /^(\d)\1{10}$/.test(d)) return false

  const digito = (base) => {
    let soma = 0
    for (let i = 0; i < base.length; i++) {
      soma += Number(base[i]) * (base.length + 1 - i)
    }
    const resto = (soma * 10) % 11
    return resto === 10 ? 0 : resto
  }

  return digito(d.slice(0, 9)) === Number(d[9]) && digito(d.slice(0, 10)) === Number(d[10])
}

// CNPJ numérico ou alfanumérico: 12 posições de A-Z/0-9 e 2 dígitos
// verificadores. Módulo 11 com pesos 2 a 9 da direita para a esquerda; cada
// caractere vale o código ASCII menos 48 (dígito vale ele mesmo, A=17, B=18...).
export function isCnpjValido(cnpj) {
  const d = documentoLimpo(cnpj)
  if (!/^[0-9A-Z]{12}\d{2}$/.test(d) || /^(.)\1{13}$/.test(d)) return false

  const digito = (base) => {
    let soma = 0
    for (let i = 0; i < base.length; i++) {
      soma += (base.charCodeAt(i) - 48) * (((base.length - 1 - i) % 8) + 2)
    }
    const resto = soma % 11
    return resto < 2 ? 0 : 11 - resto
  }

  return digito(d.slice(0, 12)) === Number(d[12]) && digito(d.slice(0, 13)) === Number(d[13])
}

// 11 dígitos = CPF, 14 caracteres = CNPJ.
export function isCnpjCpfValido(v) {
  const d = documentoLimpo(v)
  if (d.length === 11) return isCpfValido(d)
  if (d.length === 14) return isCnpjValido(d)
  return false
}

// tipo 1 = fixo (10 dígitos com DDD), 2 = celular (11) — mesmos tipos de
// tblpessoatelefone e de mascaraTelefone() em formatters.js.
export function isTelefoneValido(telefone, tipo) {
  const d = String(telefone ?? '').replace(/\D/g, '')
  if (tipo === 1) return d.length === 10
  if (tipo === 2) return d.length === 11
  return d.length > 0
}
