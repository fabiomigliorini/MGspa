export const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(parseFloat(valor) || 0);
};

export const formataPercentual = (valor) => {
  if (valor == null) return "—";
  return (
    new Intl.NumberFormat("pt-BR", {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    }).format(parseFloat(valor) || 0) + "%"
  );
};

export const corProgresso = (percentual) => {
  if (!percentual) return "grey";
  if (percentual >= 100) return "green";
  if (percentual >= 70) return "orange";
  return "red";
};

export const tipoIndicadorLabel = (tipo) => {
  const map = { U: "Unidade", S: "Setor", V: "Vendedor", C: "Caixa" };
  return map[tipo] || tipo;
};

export const tipoIndicadorColor = (tipo) => {
  const map = { V: "blue", C: "purple", U: "orange", S: "teal" };
  return map[tipo] || "grey";
};

export const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};
