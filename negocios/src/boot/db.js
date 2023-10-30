// db.js
import Dexie from "dexie";

export const db = new Dexie("negocios");
db.version(1).stores({
  produtos:
    "++id, &codprodutobarra, codproduto, barras, produto, variacao, sigla, quantidade, codimagem, preco, inativo, sincronizado", // Primary key and indexed props
});
