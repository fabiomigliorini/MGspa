// =====================================================================
// Formatters compartilhados MGspa
// Importar como: import { ... } from '@components/formatters'
// =====================================================================

// ---------- Números / Moeda ----------

// Converte qualquer entrada (number ou string "1.234,56") em number; NaN se inválido.
function paraNumero(valor) {
  if (valor === null || valor === undefined || valor === "") return NaN;
  return typeof valor === "number"
    ? valor
    : Number(
        String(valor)
          .replace(/[^0-9,\-.]/g, "")
          .replace(",", "."),
      );
}

export function formataNumero(valor, casas = 2) {
  const n = paraNumero(valor);
  if (Number.isNaN(n)) return "";
  return new Intl.NumberFormat("pt-BR", {
    minimumFractionDigits: casas,
    maximumFractionDigits: casas,
  }).format(n);
}

// Número "enxuto": mostra só os decimais necessários, até `maxCasas`.
// 2,000 -> "2" | 1,500 -> "1,5" | 1,570 -> "1,57" | 1,576 -> "1,576"
export function formataNumeroInteligente(valor, maxCasas = 3) {
  const n = paraNumero(valor);
  if (Number.isNaN(n)) return "";
  return new Intl.NumberFormat("pt-BR", {
    minimumFractionDigits: 0,
    maximumFractionDigits: maxCasas,
  }).format(n);
}

export function formataPercentual(valor, casas = 2) {
  return formataNumero(valor, casas) + "%";
}

export function arredonda(valor, casas = 2) {
  const factor = Math.pow(10, casas);
  return Math.round((valor || 0) * factor) / factor;
}

// ---------- Datas ----------

// Helper interno: cria Date evitando shift de TZ para strings YYYY-MM-DD puras.
// new Date("2026-05-16") interpreta UTC 00:00, que em GMT-3 vira 2026-05-15 21:00.
// Aqui forçamos meio-dia local para strings que são só data.
function parseLocalDate(data) {
  if (data instanceof Date) return data;
  const s = String(data);
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return new Date(s + "T12:00:00");
  return new Date(s);
}

// Helper: monta a parte do ano conforme digitosAno (4 = YYYY, 2 = YY, 0 = sem ano)
function anoStr(d, digitosAno) {
  if (digitosAno === 0) return "";
  if (digitosAno === 2) return String(d.getFullYear()).slice(-2);
  return String(d.getFullYear());
}

const MESES_ABREV = [
  "jan", "fev", "mar", "abr", "mai", "jun",
  "jul", "ago", "set", "out", "nov", "dez",
];
const MESES_LONGO = [
  "janeiro", "fevereiro", "março", "abril", "maio", "junho",
  "julho", "agosto", "setembro", "outubro", "novembro", "dezembro",
];
const DIAS_ABREV = ["dom", "seg", "ter", "qua", "qui", "sex", "sáb"];
const DIAS_LONGO = [
  "domingo", "segunda-feira", "terça-feira", "quarta-feira",
  "quinta-feira", "sexta-feira", "sábado",
];

// Data numérica: DD/MM/YYYY (4) | DD/MM/YY (2) | DD/MM (0)
export function formataData(data, digitosAno = 4) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  const dd = String(d.getDate()).padStart(2, "0");
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const ano = anoStr(d, digitosAno);
  return ano ? `${dd}/${mm}/${ano}` : `${dd}/${mm}`;
}

// Data + hora: DD/MM/YYYY HH:mm [:ss]
export function formataTimestamp(data, digitosAno = 4, segundos = false) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  const base = formataData(d, digitosAno);
  const hora = formataHora(d, segundos);
  return `${base} ${hora}`;
}

// Hora: HH:mm [:ss]
export function formataHora(data, segundos = false) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  const hh = String(d.getHours()).padStart(2, "0");
  const ii = String(d.getMinutes()).padStart(2, "0");
  if (!segundos) return `${hh}:${ii}`;
  const ss = String(d.getSeconds()).padStart(2, "0");
  return `${hh}:${ii}:${ss}`;
}

// Data com mês abreviado em letras: DD/mmm/YY (default) | DD/mmm/YYYY | DD/mmm
export function formataDataAbreviada(data, digitosAno = 2) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  const dd = String(d.getDate()).padStart(2, "0");
  const mmm = MESES_ABREV[d.getMonth()];
  const ano = anoStr(d, digitosAno);
  return ano ? `${dd}/${mmm}/${ano}` : `${dd}/${mmm}`;
}

// Mês/ano abreviado: mmm/YYYY (default) | mmm/YY
export function formataMesAno(data, digitosAno = 4) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  const mmm = MESES_ABREV[d.getMonth()];
  const ano = anoStr(d, digitosAno);
  return ano ? `${mmm}/${ano}` : mmm;
}

// Dia da semana: "seg" (short=true) | "segunda-feira" (short=false)
export function formataDiaSemana(data, short = true) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  return short ? DIAS_ABREV[d.getDay()] : DIAS_LONGO[d.getDay()];
}

// Data extensa: "quarta-feira, 5 janeiro 2026"
export function formataDataCompleta(data) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  const semana = DIAS_LONGO[d.getDay()];
  const dd = d.getDate();
  const mes = MESES_LONGO[d.getMonth()];
  return `${semana}, ${dd} ${mes} ${d.getFullYear()}`;
}

// Data extensa com hora: "quarta-feira, 5 janeiro 2026 14:30"
export function formataTimestampCompleto(data) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  return `${formataDataCompleta(d)} ${formataHora(d)}`;
}

// Para envio ao backend (naive): YYYY-MM-DD, em horário local
export function formataDataIso(data) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd}`;
}

// Para envio ao backend (naive): YYYY-MM-DD HH:mm:ss, em horário local
export function formataTimestampIso(data) {
  if (!data) return "";
  const d = parseLocalDate(data);
  if (isNaN(d.getTime())) return "";
  const base = formataDataIso(d);
  const hh = String(d.getHours()).padStart(2, "0");
  const ii = String(d.getMinutes()).padStart(2, "0");
  const ss = String(d.getSeconds()).padStart(2, "0");
  return `${base} ${hh}:${ii}:${ss}`;
}

const rtf = new Intl.RelativeTimeFormat(
  typeof navigator !== "undefined" ? navigator.language || "pt-BR" : "pt-BR",
  { numeric: "auto" },
);

export function tempoRelativo(dataStr) {
  if (!dataStr) return "";
  const diff = parseLocalDate(dataStr) - new Date();
  const seconds = Math.round(diff / 1000);
  const minutes = Math.round(diff / 60000);
  const hours = Math.round(diff / 3600000);
  const days = Math.round(diff / 86400000);
  const months = Math.round(days / 30);
  const years = Math.round(days / 365);

  if (Math.abs(seconds) < 60) return rtf.format(seconds, "second");
  if (Math.abs(minutes) < 60) return rtf.format(minutes, "minute");
  if (Math.abs(hours) < 24) return rtf.format(hours, "hour");
  if (Math.abs(days) < 30) return rtf.format(days, "day");
  if (Math.abs(months) < 12) return rtf.format(months, "month");
  return rtf.format(years, "year");
}

export const formataFromNow = tempoRelativo;

export function verificaPassadoFuturo(data) {
  if (!data) return null;
  return parseLocalDate(data).getTime() < Date.now();
}

export function verificaIdade(dataNascimento) {
  if (!dataNascimento) return null;
  const nasc = parseLocalDate(dataNascimento);
  const hoje = new Date();
  let idade = hoje.getFullYear() - nasc.getFullYear();
  const m = hoje.getMonth() - nasc.getMonth();
  if (m < 0 || (m === 0 && hoje.getDate() < nasc.getDate())) idade--;
  return idade;
}

// ---------- Documentos ----------

export function formataCpf(cpf) {
  if (cpf == null) return null;
  const s = String(cpf).padStart(11, "0");
  return (
    s.slice(0, 3) +
    "." +
    s.slice(3, 6) +
    "." +
    s.slice(6, 9) +
    "-" +
    s.slice(9, 11)
  );
}

export function formataCnpj(cnpj) {
  if (cnpj == null) return null;
  const s = String(cnpj).padStart(14, "0");
  return (
    s.slice(0, 2) +
    "." +
    s.slice(2, 5) +
    "." +
    s.slice(5, 8) +
    "/" +
    s.slice(8, 12) +
    "-" +
    s.slice(12, 14)
  );
}

// fisica: true=CPF, false=CNPJ, null/undefined=auto-detecta pelo length (≤11 dígitos = CPF)
export function formataCnpjCpf(cnpjcpf, fisica = null) {
  if (cnpjcpf == null) return null;
  if (fisica == null) fisica = String(cnpjcpf).length <= 11;
  return fisica ? formataCpf(cnpjcpf) : formataCnpj(cnpjcpf);
}

export function formataPisPasep(pispasep) {
  if (pispasep == null) return null;
  const s = String(pispasep).padStart(11, "0");
  return (
    s.slice(0, 3) +
    "." +
    s.slice(3, 8) +
    "." +
    s.slice(8, 10) +
    "-" +
    s.slice(10, 11)
  );
}

export function formataCep(cep) {
  if (cep == null) return null;
  const s = String(cep).padStart(8, "0");
  return s.slice(0, 2) + "." + s.slice(2, 5) + "-" + s.slice(5, 8);
}

// Inscrição Estadual — merge "best-of-breed" das 3 versões anteriores
export function formataIe(ie, uf) {
  if (ie == null || ie === "") return "";
  const ieStr = String(ie).replace(/\D/g, "");
  if (!ieStr) return "";
  if (!uf) return ieStr;
  const ufUpper = String(uf).toUpperCase();
  try {
    switch (ufUpper) {
      case "AC":
        return ieStr
          .padStart(13, "0")
          .replace(/(\d{2})(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3/$4-$5");
      case "AL":
        return ieStr.padStart(9, "0");
      case "AP":
        return ieStr.padStart(9, "0").replace(/(\d{8})(\d{1})/, "$1-$2");
      case "AM":
        return ieStr
          .padStart(9, "0")
          .replace(/(\d{2})(\d{3})(\d{3})(\d{1})/, "$1.$2.$3-$4");
      case "BA":
        return ieStr.padStart(9, "0").replace(/(\d{7})(\d{2})/, "$1-$2");
      case "CE":
        return ieStr.padStart(9, "0").replace(/(\d{8})(\d{1})/, "$1-$2");
      case "DF":
        return ieStr.padStart(13, "0").replace(/(\d{11})(\d{2})/, "$1-$2");
      case "ES":
        return ieStr
          .padStart(9, "0")
          .replace(/(\d{3})(\d{3})(\d{2})(\d{1})/, "$1.$2.$3.$4");
      case "GO":
        return ieStr
          .padStart(9, "0")
          .replace(/(\d{2})(\d{3})(\d{3})(\d{1})/, "$1.$2.$3-$4");
      case "MA":
        return ieStr.padStart(9, "0").replace(/(\d{8})(\d{1})/, "$1-$2");
      case "MT":
        return ieStr
          .padStart(9, "0")
          .replace(/(\d{2})(\d{3})(\d{3})(\d{1})/, "$1.$2.$3-$4");
      case "MS":
        return ieStr.padStart(9, "0").replace(/(\d{8})(\d{1})/, "$1-$2");
      case "MG":
        return ieStr
          .padStart(13, "0")
          .replace(/(\d{3})(\d{3})(\d{3})(\d{4})/, "$1.$2.$3/$4");
      case "PA":
        return ieStr
          .padStart(9, "0")
          .replace(/(\d{2})(\d{6})(\d{1})/, "$1-$2-$3");
      case "PB":
        return ieStr.padStart(9, "0").replace(/(\d{8})(\d{1})/, "$1-$2");
      case "PR":
        return ieStr
          .padStart(10, "0")
          .replace(/(\d{3})(\d{5})(\d{2})/, "$1.$2-$3");
      case "PE":
        return ieStr
          .padStart(14, "0")
          .replace(/(\d{2})(\d{1})(\d{3})(\d{7})(\d{1})/, "$1.$2.$3.$4-$5");
      case "PI":
        return ieStr.padStart(9, "0").replace(/(\d{8})(\d{1})/, "$1-$2");
      case "RJ":
        return ieStr
          .padStart(8, "0")
          .replace(/(\d{2})(\d{3})(\d{2})(\d{1})/, "$1.$2.$3-$4");
      case "RN":
        if (ieStr.length <= 9) {
          return ieStr
            .padStart(9, "0")
            .replace(/(\d{2})(\d{3})(\d{3})(\d{1})/, "$1.$2.$3-$4");
        }
        return ieStr
          .padStart(10, "0")
          .replace(/(\d{2})(\d{1})(\d{3})(\d{3})(\d{1})/, "$1.$2.$3.$4-$5");
      case "RS":
        return ieStr.padStart(10, "0").replace(/(\d{3})(\d{7})/, "$1/$2");
      case "RO":
        return ieStr.padStart(14, "0").replace(/(\d{13})(\d{1})/, "$1-$2");
      case "RR":
        return ieStr.padStart(9, "0").replace(/(\d{8})(\d{1})/, "$1-$2");
      case "SC":
        return ieStr
          .padStart(9, "0")
          .replace(/(\d{3})(\d{3})(\d{3})/, "$1.$2.$3");
      case "SP":
        return ieStr
          .padStart(12, "0")
          .replace(/(\d{3})(\d{3})(\d{3})(\d{3})/, "$1.$2.$3.$4");
      case "SE":
        return ieStr.padStart(10, "0").replace(/(\d{9})(\d{1})/, "$1-$2");
      case "TO":
        return ieStr.padStart(11, "0");
      default:
        return ieStr;
    }
  } catch (e) {
    console.log(e);
    return ieStr;
  }
}

// ---------- Telefone ----------

// fone pode ser:
//   - objeto { pais, ddd, telefone } → formato internacional "+pais (ddd) telefone"
//   - string/number raw → autodetecta pelo length (8=fixo, 9=cel, 10=fixo+DDD, 11=cel+DDD)
// tipo (1=fixo, 2=celular) é opcional — força o formato em casos ambíguos
export function formataTelefone(fone, tipo = null) {
  if (fone == null) return null;
  // Objeto estruturado
  if (typeof fone === "object" && !Array.isArray(fone)) {
    if (!fone.telefone) return "";
    return `+${fone.pais} (${fone.ddd}) ${fone.telefone}`;
  }
  // Raw → autodetecta
  const s = String(fone).replace(/\D/g, "");
  if (!s) return "";
  const len = s.length;
  const comDDD = len === 10 || len === 11;
  const isCel = tipo === 2 || (tipo == null && (len === 9 || len === 11));
  if (isCel) {
    if (comDDD) {
      const p = s.padStart(11, "0");
      return `(${p.slice(0, 2)}) ${p.slice(2, 3)} ${p.slice(3, 7)}-${p.slice(7, 11)}`;
    }
    const p = s.padStart(9, "0");
    return `${p.slice(0, 1)} ${p.slice(1, 5)}-${p.slice(5, 9)}`;
  }
  // Fixo (tipo === 1 ou autodetectado por length 8/10)
  if (comDDD) {
    const p = s.padStart(10, "0");
    return `(${p.slice(0, 2)}) ${p.slice(2, 6)}-${p.slice(6, 10)}`;
  }
  const p = s.padStart(8, "0");
  return `${p.slice(0, 4)}-${p.slice(4, 8)}`;
}

// ---------- Códigos / Fiscal ----------

export function formataNcm(ncm) {
  if (!ncm) return "-";
  const s = String(ncm).padStart(8, "0");
  return `${s.substring(0, 4)}.${s.substring(4, 6)}.${s.substring(6, 8)}`;
}

export function formataCest(cest) {
  if (!cest) return "-";
  const s = String(cest).padStart(7, "0");
  return `${s.substring(0, 2)}.${s.substring(2, 5)}.${s.substring(5, 7)}`;
}

export function formataChave(chave) {
  if (!chave) return "-";
  return (
    String(chave)
      .match(/.{1,4}/g)
      ?.join(" ") || String(chave)
  );
}

export function formataProtocolo(protocolo) {
  if (!protocolo) return "-";
  return (
    String(protocolo)
      .match(/.{1,5}/g)
      ?.join(" ") || String(protocolo)
  );
}

export function formataCodigo(codigo, digitos = 8) {
  if (!codigo) return "#" + "0".repeat(digitos);
  return "#" + String(codigo).padStart(digitos, "0");
}

export function formataNumeroNota(
  numero,
  tipo = null,
  serie = null,
  emitida = null,
) {
  if (numero == null) return "";
  let resultado = String(numero).padStart(9, "0");
  if (serie != null) resultado = String(serie).padStart(3, "0") + "-" + resultado;
  if (emitida === false) resultado = "T-" + resultado;
  if (tipo === 55) resultado = "NFe " + resultado;
  else if (tipo === 65) resultado = "NFCe " + resultado;
  return resultado;
}

export function formataTitulo(titulo) {
  if (titulo == null) return null;
  const s = String(titulo).padStart(12, "0");
  return s.slice(0, 4) + "." + s.slice(4, 8) + "." + s.slice(8, 12);
}

export function formataCfop(cfop) {
  if (!cfop) return "";
  const s = String(cfop).padStart(4, "0");
  return s.slice(0, 1) + "." + s.slice(1, 4);
}

// ---------- Strings ----------

export function removerAcentos(str) {
  if (!str) return str;
  return str.normalize("NFD").replace(/\p{Mn}/gu, "");
}

export function primeiraLetraMaiuscula(str) {
  if (!str) return str;
  return removerAcentos(str)
    .trimStart()
    .replace(/\s+/g, " ")
    .replace(
      /\w\S*/g,
      (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase(),
    );
}

// ---------- Diversos ----------

export function linkMaps(cidade, endereco, numero, cep) {
  return (
    "https://www.google.com/maps/search/?api=1&query=" +
    encodeURIComponent(
      [endereco, numero, cidade, cep].filter(Boolean).join(","),
    )
  );
}

export const localeBrasil = {
  days: "Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado".split("_"),
  daysShort: "Dom_Seg_Ter_Qua_Qui_Sex_Sáb".split("_"),
  months:
    "Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro".split(
      "_",
    ),
  monthsShort: "Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez".split("_"),
  firstDayOfWeek: 1,
  format24h: true,
  pluralDay: "dias",
};

// ---------- Máscaras de input (q-input :mask) ----------

export const MASCARA_CPF = "###.###.###-##";
export const MASCARA_CNPJ = "##.###.###/####-##";
export const MASCARA_CEP = "#####-###";
export const MASCARA_DATA = "##/##/####";
export const MASCARA_TELEFONE_FIXO = "(##) ####-####";
export const MASCARA_TELEFONE_CELULAR = "(##) #-####-####";

export function mascaraTelefone(tipo) {
  if (tipo === 1) return MASCARA_TELEFONE_FIXO;
  if (tipo === 2) return MASCARA_TELEFONE_CELULAR;
  return "";
}

const MASCARAS_IE = {
  AC: "##.###.###/###-##",
  AL: "#########",
  AP: "#########",
  AM: "##.###.###-#",
  BA: "#######-##",
  CE: "########-#",
  DF: "###########-##",
  ES: "###.###.##-#",
  GO: "##.###.###-#",
  MA: "#########",
  MT: "##.###.###-#",
  MS: "#########",
  MG: "###.###.###/####",
  PA: "##-######-#",
  PB: "########-#",
  PR: "########-##",
  PE: "##.#.###.#######-#",
  PI: "#########",
  RJ: "##.###.##-#",
  RN: "##.###.###-#",
  RS: "###-#######",
  RO: "#############-#",
  RR: "########-#",
  SC: "###.###.###",
  SP: "###.###.###.###",
  SE: "#########-#",
  TO: "###########",
};

export function mascaraIe(uf) {
  if (!uf) return "";
  return MASCARAS_IE[String(uf).toUpperCase()] || "";
}
