// Parse e validação da linha CMC7 do cheque.
// Porta de api/app/Mg/Cheque/Cmc7/Cmc7.php (mesma regra, para validar offline no PDV).

const DIGITOS_A_IGNORAR_CONTA = {
  1: 2, // Banco do Brasil
  33: 4, // Santander
  41: 0, // Banrisul
  104: 0, // CEF
  237: 3, // Bradesco
  341: 4, // Itaú
  389: 1, // Mercantil
  409: 3, // Unibanco
  479: 2, // Bank of Boston
}

const soDigitos = (texto) => String(texto ?? '').replace(/\D/g, '')

const calculaDv = (str) => {
  let result = 0
  let weight = 2
  for (let i = str.length - 1; i >= 0; i--) {
    const total = parseInt(str[i]) * weight
    result += total > 9 ? 1 + (total - 10) : total
    weight = weight === 1 ? 2 : 1
  }
  const dv = 10 - (result % 10)
  return dv === 10 ? 0 : dv
}

export function parseCmc7(texto) {
  const cmc7 = soDigitos(texto)
  if (cmc7.length !== 30) {
    return { cmc7, valido: false }
  }
  const banco = parseInt(cmc7.substr(0, 3))
  const ignorar = DIGITOS_A_IGNORAR_CONTA[banco] ?? 3
  const valido =
    calculaDv(cmc7.substr(8, 10)) === parseInt(cmc7[7]) &&
    calculaDv(cmc7.substr(0, 7)) === parseInt(cmc7[18]) &&
    calculaDv(cmc7.substr(19, 10)) === parseInt(cmc7[29])
  return {
    cmc7,
    valido,
    banco,
    agencia: parseInt(cmc7.substr(3, 4)),
    contacorrente: cmc7.substr(19 + ignorar, 9 - ignorar) + '-' + cmc7[28],
    numero: parseInt(cmc7.substr(11, 6)),
  }
}
