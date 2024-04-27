export const iconeNegocio = (n) => {
  switch (n.codnegociostatus) {
    case 1:
      return "mdi-cart-outline";
    case 3:
      return "mdi-cart-remove";
  }
  if (n.codoperacao == 2) {
    return "mdi-cart-arrow-up";
  }
  return "mdi-cart-arrow-down";
};

export const corIconeNegocio = (n) => {
  switch (n.codnegociostatus) {
    case 1:
      return "secondary";
    case 3:
      return "negative";
  }
  if (n.codoperacao == 2) {
    return "primary";
  }
  return "warning";
};
