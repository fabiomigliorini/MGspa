import { defineStore } from "pinia";
import { pessoaStore } from "./pessoa";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

const sPessoa = pessoaStore();

export const formataDocumetos = defineStore("documentos", {
  actions: {
    formataIePorSigla(ie) {
      if (!sPessoa.item.PessoaEnderecoS) {
        return ie;
      }

      const end = sPessoa.item.PessoaEnderecoS.filter((end) => end.nfe == true);
      if (end.length == 0) {
        return ie;
      }

      const uf = end[0].uf;

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
    },

    formataCPF(cpf) {
      if (cpf == null) {
        return cpf;
      }
      cpf = cpf.toString().padStart(11, "0");
      return (
        cpf.slice(0, 3) +
        "." +
        cpf.slice(3, 6) +
        "." +
        cpf.slice(6, 9) +
        "-" +
        cpf.slice(9, 11)
      );
    },

    formataIe(ie) {
      if (ie == null) {
        return ie;
      }
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
    },

    formataCnpjCpf(cnpj, fisica) {
      if (cnpj == null) {
        return cnpj;
      }
      if (fisica) {
        return this.formataCPF(cnpj);
      }
      return this.formataCNPJ(cnpj);
    },

    formataCNPJ(cnpj) {
      if (cnpj == null) {
        return cnpj;
      }
      cnpj = cnpj.toString().padStart(14, "0");
      return (
        cnpj.slice(0, 2) +
        "." +
        cnpj.slice(2, 5) +
        "." +
        cnpj.slice(5, 8) +
        "/" +
        cnpj.slice(8, 12) +
        "-" +
        cnpj.slice(12, 14)
      );
    },

    formataFone(tipo, fone) {
      switch (tipo) {
        case 2:
          return this.formataCelular(fone);
          break;
        default:
          return this.formataFixo(fone);
          break;
      }
    },

    formataCelular(cel) {
      if (cel == null) {
        return cel;
      }
      cel = cel.toString().padStart(9);
      return cel.slice(0, 1) + " " + cel.slice(1, 5) + "-" + cel.slice(5, 9);
    },

    formataCelularComDDD(cel) {
      if (cel == null) {
        return cel;
      }
      cel = cel.toString().padStart(11);
      return (
        cel.slice(0, 0) +
        "(" +
        cel.slice(0, 2) +
        ") " +
        cel.slice(2, 3) +
        " " +
        cel.slice(3, 7) +
        "-" +
        cel.slice(7, 11)
      );
    },

    formataFixo(fixo) {
      if (fixo == null) {
        return fixo;
      }
      fixo = fixo.toString().padStart(9);
      return fixo.slice(0, 1) + "" + fixo.slice(1, 5) + "-" + fixo.slice(5, 9);
    },

    formataData(data) {
      var dataformatada = moment(data).format("DD/MM/YYYY HH:mm");
      return dataformatada;
    },

    formataDataLonga(data) {
      return moment(data).startOf("day").format("llll");
    },

    formataDatasemHr(data) {
      if (data) {
        var dataformatada = moment(data).format("DD/MM/YYYY");
        return dataformatada;
      }
      return data;
    },

    dataFormatoSql(data) {
      var dataformatada = moment(data, "DD/MM/YYYY").format("YYYY-MM-DD");
      return dataformatada;
    },

    formataDataInput(data) {
      var dataformatada = moment(data).format("DD/MM/YYYY");
      return dataformatada;
    },

    dataAtual() {
      return moment().format("YYYY-MM-DD");
    },

    formataMes(data) {
      var pegaMes = moment(data).locale("Pt-Br").format("MMM/YYYY");
      return pegaMes;
    },

    formataFromNow(data) {
      var dataformatada = moment(data).locale("Pt-Br").fromNow();
      return dataformatada;
    },

    verificaPassadoFuturo(data) {
      //Se for true é passado, se não é futuro
      var dataformatada = moment(data).isBefore();
      return dataformatada;
    },

    verificaIdade(data) {
      var dataFormatada = moment().diff(data, "years", false);
      return dataFormatada;
    },

    formataCnpjEcpf(cnpjcpf) {
      if (cnpjcpf == null) {
        return cnpjcpf;
      }
      if (cnpjcpf.toString().length <= 11) {
        cnpjcpf = this.formataCPF(cnpjcpf.toString().padStart(11, "0"));
        return cnpjcpf;
      }
      cnpjcpf = this.formataCNPJ(cnpjcpf.toString().padStart(14, "0"));
      return cnpjcpf;
    },

    formataTitulo(titulo) {
      if (titulo == null) {
        return titulo;
      }
      titulo = titulo.toString().padStart(12, "0");
      return (
        titulo.slice(0, 4) +
        "." +
        titulo.slice(4, 8) +
        "." +
        titulo.slice(8, 12)
      );
    },

    formataPisPasep(pispasep) {
      if (pispasep == null) {
        return pispasep;
      }
      pispasep = pispasep.toString().padStart(11, "0");
      return (
        pispasep.slice(0, 3) +
        "." +
        pispasep.slice(3, 8) +
        "." +
        pispasep.slice(8, 10) +
        "-" +
        pispasep.slice(10, 12)
      );
    },
  },
});
