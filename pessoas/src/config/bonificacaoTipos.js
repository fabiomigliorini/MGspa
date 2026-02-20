export const bonificacaoTipos = {
  VENDA_VENDEDOR: {
    label: "Vendedor",
    icon: "mdi-cart-outline",
    color: "blue-7",
  },
  VENDA_CAIXA: {
    label: "Caixa",
    icon: "mdi-cash-register",
    color: "teal-7",
  },
  VENDA_LOJA: {
    label: "Loja",
    icon: "mdi-store-outline",
    color: "indigo-7",
  },
  VENDA_XEROX: {
    label: "Xerox",
    icon: "mdi-printer",
    color: "brown-7",
  },
  META_ATINGIDA: {
    label: "Meta Atingida",
    icon: "mdi-flag-checkered",
    color: "green-7",
  },
  PREMIO_RANKING: {
    label: "Prêmio Ranking",
    icon: "mdi-podium-gold",
    color: "amber-8",
  },
  BONUS_FIXO: {
    label: "Bônus Fixo",
    icon: "mdi-star-circle-outline",
    color: "purple-7",
  },
  PREMIO_META: {
    label: "Prêmio Meta",
    icon: "mdi-trophy-outline",
    color: "green-8",
  },
  PREMIO_META_XEROX: {
    label: "Prêmio Meta Xerox",
    icon: "mdi-trophy-outline",
    color: "light-green-8",
  },
  PREMIO_META_LOJA: {
    label: "Prêmio Meta Loja",
    icon: "mdi-trophy-outline",
    color: "deep-purple-5",
  },
};

const tipoDefault = {
  label: "",
  icon: "mdi-help-circle-outline",
  color: "grey-7",
};

export const getTipo = (tipo) => {
  const config = bonificacaoTipos[tipo] || { ...tipoDefault, label: tipo };
  return config;
};
