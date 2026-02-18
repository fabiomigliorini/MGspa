import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

export const formataData = (data) => {
  if (!data) return "";
  return moment(data).format("DD/MM/YYYY HH:mm");
};

export const formataDataSemHora = (data) => {
  if (!data) return "";
  return moment(data).format("DD/MM/YYYY");
};

export const formataFromNow = (data) => {
  if (!data) return "";
  return moment(data).fromNow();
};

export const formataDataCompleta = (data) => {
  if (!data) return "";
  return moment(data).format("llll");
};

export const dataAtual = () => {
  return moment().format("YYYY-MM-DD");
};

export const dataFormatoSql = (data) => {
  return moment(data, "DD/MM/YYYY").format("YYYY-MM-DD");
};

export const formataDataInput = (data) => {
  if (!data) return "";
  return moment(data).format("DD/MM/YYYY");
};

export const formataCep = (cep) => {
  if (cep == null) return cep;
  cep = cep.toString().padStart(8);
  return cep.slice(0, 2) + "." + cep.slice(2, 5) + "-" + cep.slice(5, 8);
};

export const formataIe = (uf, ie) => {
    switch (uf) {
        case 'AC':
            ie = ie.toString().padStart(13, '0')
            return ie.slice(0, 2) + "." +
                ie.slice(2, 5) + "." +
                ie.slice(5, 8) + "/" +
                ie.slice(8, 11) + "-" +
                ie.slice(11, 13)
            break;

        case 'AL':
            ie = ie.toString().padStart(9, '0')
            return ie;
            break;

        case 'AP':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 9)
            break;

        case 'AM':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 2) + "." +
                ie.slice(2, 5) + "." +
                ie.slice(5, 8) + "-" +
                ie.slice(8, 9)
            break;

        case 'BA':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 7) + "-" +
                ie.slice(7, 9)
            break;

        case 'CE':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 8) + "-" +
                ie.slice(8, 9)
            break;

        case 'DF':
            ie = ie.toString().padStart(13, '0')
            return ie.slice(0, 11) + "-" +
                ie.slice(11, 13)
            break;

        case 'ES':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 3) + "." +
                ie.slice(3, 6) + "." +
                ie.slice(6, 8) + "." +
                ie.slice(8, 9)
            break;

        case 'GO':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 2) + "." +
                ie.slice(2, 5) + "." +
                ie.slice(5, 8) + "-" +
                ie.slice(8, 9)
            break;

        case 'MA':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 9)
            break;

        case 'MT':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 2) + "." +
                ie.slice(2, 5) + "." +
                ie.slice(5, 8) + "-" +
                ie.slice(8, 9)
            break;

        case 'MS':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 9)
            break;

        case 'MG':
            ie = ie.toString().padStart(13, '0')
            return ie.slice(0, 3) + "." +
                ie.slice(3, 6) + "." +
                ie.slice(6, 9) + "/" +
                ie.slice(9, 13)
            break;

        case 'PA':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 2) + "-" +
                ie.slice(2, 8) + "-" +
                ie.slice(8, 9)
            break;

        case 'PB':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 8) + "-" +
                ie.slice(8, 9)
            break;

        case 'PR':
            ie = ie.toString().padStart(10, '0')
            return ie.slice(0, 8) + "-" +
                ie.slice(8, 10)
            break;

        case 'PE':
            ie = ie.toString().padStart(14, '0')
            return ie.slice(0, 2) + "." +
                ie.slice(2, 3) + "." +
                ie.slice(3, 6) + "." +
                ie.slice(6, 13) + "-" +
                ie.slice(13, 14)
            break;

        case 'PI':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 9)
            break;

        case 'RJ':
            ie = ie.toString().padStart(8, '0')
            return ie.slice(0, 2) + "." +
                ie.slice(2, 5) + "." +
                ie.slice(5, 7) + "-" +
                ie.slice(7, 8)
            break;

        case 'RN':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 2) + "." +
                ie.slice(2, 5) + "." +
                ie.slice(5, 8) + "-" +
                ie.slice(8, 9)
            break;

        case 'RS':
            ie = ie.toString().padStart(10, '0')
            return ie.slice(0, 3) + "-" +
                ie.slice(3, 10)
            break;

        case 'RO':
            ie = ie.toString().padStart(14, '0')
            return ie.slice(0, 13) + "-" +
                ie.slice(13, 14)
            break;

        case 'RR':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 8) + "-" +
                ie.slice(8, 9)
            break;


        case 'SC':
            ie = ie.toString().padStart(9, '0')
            return ie.slice(0, 3) + "." +
                ie.slice(3, 6) + "." +
                ie.slice(6, 9)
            break;

        case 'SP':
            ie = ie.toString().padStart(12, '0')
            return ie.slice(0, 3) + "." +
                ie.slice(3, 6) + "." +
                ie.slice(6, 9) + "." +
                ie.slice(9, 12)
            break;

        case 'SE':
            ie = ie.toString().padStart(10, '0')
            return ie.slice(0, 9) + "-" +
                ie.slice(9, 10)
            break;

        case 'TO':
            ie = ie.toString().padStart(11, '0')
            return ie.slice(0, 11)
            break;

        default:
            break;
    }
};

export const formataCPF = (cpf) => {
  if (cpf == null) return cpf;
  cpf = cpf.toString().padStart(11, "0");
  return cpf.slice(0, 3) + "." + cpf.slice(3, 6) + "." + cpf.slice(6, 9) + "-" + cpf.slice(9, 11);
};

export const formataCNPJ = (cnpj) => {
  if (cnpj == null) return cnpj;
  cnpj = cnpj.toString().padStart(14, "0");
  return cnpj.slice(0, 2) + "." + cnpj.slice(2, 5) + "." + cnpj.slice(5, 8) + "/" + cnpj.slice(8, 12) + "-" + cnpj.slice(12, 14);
};

export const formataCnpjCpf = (cnpjcpf, fisica) => {
  if (cnpjcpf == null) return cnpjcpf;
  if (fisica) return formataCPF(cnpjcpf);
  return formataCNPJ(cnpjcpf);
};

export const formataCnpjEcpf = (cnpjcpf) => {
  if (cnpjcpf == null) return cnpjcpf;
  if (cnpjcpf.toString().length <= 11) {
    return formataCPF(cnpjcpf.toString().padStart(11, "0"));
  }
  return formataCNPJ(cnpjcpf.toString().padStart(14, "0"));
};

export const formataCelular = (cel) => {
  if (cel == null) return cel;
  cel = cel.toString().padStart(9);
  return cel.slice(0, 1) + " " + cel.slice(1, 5) + "-" + cel.slice(5, 9);
};

export const formataCelularComDDD = (cel) => {
  if (cel == null) return cel;
  cel = cel.toString().padStart(11);
  return "(" + cel.slice(0, 2) + ") " + cel.slice(2, 3) + " " + cel.slice(3, 7) + "-" + cel.slice(7, 11);
};

export const formataFixo = (fixo) => {
  if (fixo == null) return fixo;
  fixo = fixo.toString().padStart(9);
  return fixo.slice(0, 1) + "" + fixo.slice(1, 5) + "-" + fixo.slice(5, 9);
};

export const formataFone = (tipo, fone) => {
  switch (tipo) {
    case 2:
      return formataCelular(fone);
    case 1:
      return formataFixo(fone);
    default:
      return fone;
  }
};

export const formataTitulo = (titulo) => {
  if (titulo == null) return titulo;
  titulo = titulo.toString().padStart(12, "0");
  return titulo.slice(0, 4) + "." + titulo.slice(4, 8) + "." + titulo.slice(8, 12);
};

export const formataPisPasep = (pispasep) => {
  if (pispasep == null) return pispasep;
  pispasep = pispasep.toString().padStart(11, "0");
  return pispasep.slice(0, 3) + "." + pispasep.slice(3, 8) + "." + pispasep.slice(8, 10) + "-" + pispasep.slice(10, 12);
};

export const verificaPassadoFuturo = (data) => {
  return moment(data).isBefore();
};

export const verificaIdade = (data) => {
  return moment().diff(data, "years", false);
};

export const linkMaps = (cidade, endereco, numero, cep) => {
  return "https://www.google.com/maps/search/?api=1&query=" + endereco + "," + numero + "," + cidade + "," + cep;
};

export const primeiraLetraMaiuscula = (str) => {
  return removerAcentos(str).trimStart().replace(/\s+/g, " ").replace(
    /\w\S*/g,
    (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
  );
};

export const removerAcentos = (str) => {
  return str.normalize("NFD").replace(/\p{Mn}/gu, "");
};

export const localeBrasil = {
  days: "Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado".split("_"),
  daysShort: "Dom_Seg_Ter_Qua_Qui_Sex_Sáb".split("_"),
  months: "Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro".split("_"),
  monthsShort: "Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez".split("_"),
  firstDayOfWeek: 1,
  format24h: true,
  pluralDay: "dias",
};
