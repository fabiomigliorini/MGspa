// db.js
import Dexie from 'dexie'
import { uid } from 'quasar'
import { MEIOS, vencimentoSugerido } from '../utils/pagamento.js'

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

// v8: pagamentos do negócio no formato novo (M5 do plano doc-3) -- `meio` no lugar de
// codformapagamento/tipo, principal/juros/desconto/total -- e o prazo em `parcelas`
// (condição, vencimento, valor). Converte o que estava no aparelho; os abertos voltam a
// sincronizar para o servidor gravar no formato novo.
const CONDICAO_DA_FORMA = {
  3010: 'F',
  3020: 'F',
  5601: 'F',
  5100: 'P',
  4100: 'B',
  1099: 'E',
  5606: 'X',
  5602: 'X',
}
const MEIO_DA_FORMA = { 1010: 1, 1020: 2, 1030: 12, 5604: 17 }
const arredonda = (v) => Math.round((parseFloat(v) || 0) * 100) / 100

export const converterPagamentosAntigos = (neg) => {
  if (!Array.isArray(neg.pagamentos) || neg.pagamentos.every((p) => p.meio !== undefined)) {
    neg.parcelas = neg.parcelas ?? []
    return false
  }
  const pagamentos = []
  const parcelas = []
  for (const pag of neg.pagamentos) {
    if (pag.meio !== undefined) {
      pagamentos.push(pag)
      continue
    }
    const cod = parseInt(pag.codformapagamento)
    let condicao = CONDICAO_DA_FORMA[cod]
    if (cod == 1030 && pag.avista === false) {
      condicao = 'V'
    }
    if (condicao) {
      const quantidade = Math.max(1, parseInt(pag.parcelas) || 1)
      const valor = arredonda(pag.valortotal)
      const juros = arredonda(pag.valorjuros)
      const dias = pag.dias ?? (condicao == 'F' ? 30 : 0)
      let somaValor = 0
      let somaJuros = 0
      for (let i = 1; i <= quantidade; i++) {
        const ultima = i == quantidade
        const v = ultima
          ? arredonda(valor - somaValor)
          : arredonda(pag.valorparcela || valor / quantidade)
        const j = ultima ? arredonda(juros - somaJuros) : arredonda((juros * v) / valor)
        somaValor += v
        somaJuros += j
        parcelas.push({
          codnegocioparcela: null,
          uuid: uid(),
          condicao,
          numero: i,
          vencimento: vencimentoSugerido(condicao, i, dias),
          valor: v,
          juros: j,
          codtitulo: null,
        })
      }
      continue
    }
    const tipo = parseInt(pag.tipo)
    const meio = MEIOS[tipo] && tipo < 90 ? tipo : (MEIO_DA_FORMA[cod] ?? 99)
    const troco = arredonda(pag.valortroco)
    const principal = arredonda(Math.abs(pag.valorpagamento) - troco)
    const juros = arredonda(pag.valorjuros)
    pagamentos.push({
      ...pag,
      codpagamento: pag.codpagamento ?? null,
      meio,
      meiodescricao: MEIOS[meio],
      estado: neg.codnegociostatus == 1 ? 'P' : neg.codnegociostatus == 3 ? 'C' : 'E',
      principal,
      juros,
      multa: 0,
      desconto: 0,
      total: arredonda(principal + juros),
      valortroco: troco || null,
    })
  }
  neg.pagamentos = pagamentos
  neg.parcelas = parcelas
  if (neg.codnegociostatus == 1) {
    neg.sincronizado = false
  }
  return true
}

db.version(8)
  .stores({})
  .upgrade((tx) =>
    tx
      .table('negocio')
      .toCollection()
      .modify((neg) => {
        converterPagamentosAntigos(neg)
      }),
  )
