// db.js
import Dexie from "dexie";

export const db = new Dexie("negocios");
db.version(2).stores({
  produtos:
    //"codprodutobarra, produto",
    // Primary key and indexed props
    "codprodutobarra, codproduto, abc, barras, produto, variacao, sigla, quantidade, codimagem, preco, inativo, sincronizado, *palavras",
});
