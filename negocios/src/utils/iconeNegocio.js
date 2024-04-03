export const iconeNegocio = (n) => {
  if (n.codnegociostatus == 1) {
    return "mdi-cart-outline";
  }
  if (n.codoperacao == 2) {
    return "mdi-cart-check";
  }
  return "mdi-cart-remove";
};

export const corIconeNegocio = (n) => {
  if (n.codnegociostatus == 1) {
    if (n.sincronizado) {
      return "secondary";
    }
    return "orange";
  }
  if (n.codoperacao == 2) {
    return "primary";
  }
  return "negative";
};
