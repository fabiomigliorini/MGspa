// db.js
import Dexie from "dexie";

export const db = new Dexie("negocios");
db.version(1).stores({
  produtos:
    //"codprodutobarra, produto",
    "codprodutobarra, codproduto, abc, barras, produto, variacao, sigla, quantidade, codimagem, preco, inativo, sincronizado, *palavras", // Primary key and indexed props
});
