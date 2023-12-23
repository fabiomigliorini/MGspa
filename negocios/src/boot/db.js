// db.js
import Dexie from "dexie";

export const db = new Dexie("negocios");
db.version(3).stores({
  produto:
    "codprodutobarra, codproduto, abc, barras, produto, variacao, sigla, quantidade, codimagem, preco, inativo, sincronizado, busca, *buscaArr",
  pessoa:
    "codpessoa, fantasia, pessoa, cnpj, vendedor, inativo, sincronizado, busca, *buscaArr",
  negocio:
    "uuid, codnegocio, sincronizado, codnegociostatus, codestoquelocal, valortotal",
  naturezaOperacao:
    "codnaturezaoperacao, naturezaoperacao, sincronizado, [codoperacao+naturezaoperacao]",
  estoqueLocal:
    "codestoquelocal, estoquelocal, inativo, filial, sincronizado, [codfilial+sigla]",
  formaPagamento: "codformapagamento, formapagamento, sincronizado",
  impressora: "codimpressora, impressora, nome, sincronizado",
});
