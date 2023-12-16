export const formataCnpj = (cnpj) => {
  return cnpj
    .padStart(14, "0")
    .replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
};

export const formataCpf = (cpf) => {
  return cpf
    .padStart(11, "0")
    .replace(/^(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
};

export const formataCnpjCpf = (cnpj, fisica) => {
  if (fisica) {
    return formataCpf(cnpj);
  }
  return formataCnpj(cnpj);
};
