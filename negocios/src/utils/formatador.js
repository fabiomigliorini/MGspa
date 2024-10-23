export const formataCnpj = (cnpj) => {
  return String(cnpj)
    .padStart(14, "0")
    .replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
};

export const formataCpf = (cpf) => {
  return String(cpf)
    .padStart(11, "0")
    .replace(/^(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
};

export const formataCnpjCpf = (cnpj, fisica) => {
  if (fisica) {
    return formataCpf(cnpj);
  }
  return formataCnpj(cnpj);
};

export const formataIe = (uf, ie) => {
  switch (uf) {
    case "AC":
      ie = ie.toString().padStart(13, "0");
      return (
        ie.slice(0, 2) +
        "." +
        ie.slice(2, 5) +
        "." +
        ie.slice(5, 8) +
        "/" +
        ie.slice(8, 11) +
        "-" +
        ie.slice(11, 13)
      );
      break;

    case "AL":
      ie = ie.toString().padStart(9, "0");
      return ie;
      break;

    case "AP":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 9);
      break;

    case "AM":
      ie = ie.toString().padStart(9, "0");
      return (
        ie.slice(0, 2) +
        "." +
        ie.slice(2, 5) +
        "." +
        ie.slice(5, 8) +
        "-" +
        ie.slice(8, 9)
      );
      break;

    case "BA":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 7) + "-" + ie.slice(7, 9);
      break;

    case "CE":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 8) + "-" + ie.slice(8, 9);
      break;

    case "DF":
      ie = ie.toString().padStart(13, "0");
      return ie.slice(0, 11) + "-" + ie.slice(11, 13);
      break;

    case "ES":
      ie = ie.toString().padStart(9, "0");
      return (
        ie.slice(0, 3) +
        "." +
        ie.slice(3, 6) +
        "." +
        ie.slice(6, 8) +
        "." +
        ie.slice(8, 9)
      );
      break;

    case "GO":
      ie = ie.toString().padStart(9, "0");
      return (
        ie.slice(0, 2) +
        "." +
        ie.slice(2, 5) +
        "." +
        ie.slice(5, 8) +
        "-" +
        ie.slice(8, 9)
      );
      break;

    case "MA":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 9);
      break;

    case "MT":
      ie = ie.toString().padStart(9, "0");
      return (
        ie.slice(0, 2) +
        "." +
        ie.slice(2, 5) +
        "." +
        ie.slice(5, 8) +
        "-" +
        ie.slice(8, 9)
      );
      break;

    case "MS":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 9);
      break;

    case "MG":
      ie = ie.toString().padStart(13, "0");
      return (
        ie.slice(0, 3) +
        "." +
        ie.slice(3, 6) +
        "." +
        ie.slice(6, 9) +
        "/" +
        ie.slice(9, 13)
      );
      break;

    case "PA":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 2) + "-" + ie.slice(2, 8) + "-" + ie.slice(8, 9);
      break;

    case "PB":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 8) + "-" + ie.slice(8, 9);
      break;

    case "PR":
      ie = ie.toString().padStart(10, "0");
      return ie.slice(0, 8) + "-" + ie.slice(8, 10);
      break;

    case "PE":
      ie = ie.toString().padStart(14, "0");
      return (
        ie.slice(0, 2) +
        "." +
        ie.slice(2, 3) +
        "." +
        ie.slice(3, 6) +
        "." +
        ie.slice(6, 13) +
        "-" +
        ie.slice(13, 14)
      );
      break;

    case "PI":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 9);
      break;

    case "RJ":
      ie = ie.toString().padStart(8, "0");
      return (
        ie.slice(0, 2) +
        "." +
        ie.slice(2, 5) +
        "." +
        ie.slice(5, 7) +
        "-" +
        ie.slice(7, 8)
      );
      break;

    case "RN":
      ie = ie.toString().padStart(9, "0");
      return (
        ie.slice(0, 2) +
        "." +
        ie.slice(2, 5) +
        "." +
        ie.slice(5, 8) +
        "-" +
        ie.slice(8, 9)
      );
      break;

    case "RS":
      ie = ie.toString().padStart(10, "0");
      return ie.slice(0, 3) + "-" + ie.slice(3, 10);
      break;

    case "RO":
      ie = ie.toString().padStart(14, "0");
      return ie.slice(0, 13) + "-" + ie.slice(13, 14);
      break;

    case "RR":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 8) + "-" + ie.slice(8, 9);
      break;

    case "SC":
      ie = ie.toString().padStart(9, "0");
      return ie.slice(0, 3) + "." + ie.slice(3, 6) + "." + ie.slice(6, 9);
      break;

    case "SP":
      ie = ie.toString().padStart(12, "0");
      return (
        ie.slice(0, 3) +
        "." +
        ie.slice(3, 6) +
        "." +
        ie.slice(6, 9) +
        "." +
        ie.slice(9, 12)
      );
      break;

    case "SE":
      ie = ie.toString().padStart(10, "0");
      return ie.slice(0, 9) + "-" + ie.slice(9, 10);
      break;

    case "TO":
      ie = ie.toString().padStart(11, "0");
      return ie.slice(0, 11);
      break;

    default:
      break;
  }
};

export const primeiraLetraMaiuscula = (str) => {
  if (!str) {
    return str;
  }
  return removerAcentos(str)
    .trimStart()
    .replace(/\s+/g, " ")
    .replace(/\w\S*/g, function (txt) {
      return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
    });
};

export const removerAcentos = (str) => {
  if (!str) {
    return str;
  }
  return str.normalize("NFD").replace(/\p{Mn}/gu, "");
};
