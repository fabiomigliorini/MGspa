// Validadores compartilhados. Vieram de negocios/src/utils/validador.js (a
// cópia mais completa); negocios e pessoas ainda usam as cópias locais.

export function isCpfValido(cpf) {
  const d = String(cpf ?? "").replace(/\D/g, "");
  if (d.length !== 11 || /^(\d)\1{10}$/.test(d)) return false;

  const digito = (base) => {
    let soma = 0;
    for (let i = 0; i < base.length; i++) {
      soma += Number(base[i]) * (base.length + 1 - i);
    }
    const resto = (soma * 10) % 11;
    return resto === 10 ? 0 : resto;
  };

  return digito(d.slice(0, 9)) === Number(d[9]) && digito(d.slice(0, 10)) === Number(d[10]);
}

// tipo 1 = fixo (10 dígitos com DDD), 2 = celular (11) — mesmos tipos de
// tblpessoatelefone e de mascaraTelefone() em formatters.js.
export function isTelefoneValido(telefone, tipo) {
  const d = String(telefone ?? "").replace(/\D/g, "");
  if (tipo === 1) return d.length === 10;
  if (tipo === 2) return d.length === 11;
  return d.length > 0;
}
