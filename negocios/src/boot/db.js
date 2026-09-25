// db.js
import Dexie from 'dexie'

export const db = new Dexie('negocios')
db.version(6).stores({
  produto:
    'codprodutobarra, codproduto, abc, barras, produto, variacao, sigla, quantidade, codimagem, preco, inativo, sincronizado, busca, *buscaArr',
  produtoDetalhe: 'codproduto, sincronizado',
  pessoa: 'codpessoa, fantasia, pessoa, cnpj, vendedor, inativo, sincronizado, busca, *buscaArr',
  negocio:
    'uuid, codnegocio, sincronizado, codnegociostatus, codestoquelocal, valortotal, [codnegociostatus+codpdv]',
  naturezaOperacao:
    'codnaturezaoperacao, naturezaoperacao, sincronizado, [codoperacao+naturezaoperacao]',
  estoqueLocal: 'codestoquelocal, estoquelocal, inativo, filial, sincronizado, [codfilial+sigla]',
  formaPagamento: 'codformapagamento, formapagamento, sincronizado',
  impressora: 'codimpressora, impressora, nome, sincronizado',
  prancheta: 'ordem, categoria, codpranchetacategoria, sincronizado',
})

// v7: catalogo de modelos de vale compras (kit escolar) no cache offline.
// Os itens de cada modelo viajam dentro do proprio registro, entao nao ha
// tabela filha -- o vale so' precisa deles na hora de semear a grade.
db.version(7).stores({
  valeModelo: 'codvalemodelo, modelo, codpessoafavorecido, sincronizado',
})
