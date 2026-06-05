import Dexie from 'dexie'

// Banco local (IndexedDB) do app agro — offline-first, espelha o padrao do
// negocios. A carga de colheita (sincronizavel) usa `uuid` como chave primaria
// e a flag `sincronizado` (0 = pendente, timestamp = sincronizado). Os cadastros
// de referencia ficam cacheados p/ leitura offline (dropdowns do Kanban).
export const db = new Dexie('agro')

db.version(1).stores({
  // carga de colheita criada/editada offline (sync por uuid)
  cargacolheita: 'uuid, codcargacolheita, sincronizado, codsafra, etapa, data',

  // cadastros de referencia (cache p/ leitura offline)
  cultura: 'codcultura, sincronizado',
  variedade: 'codvariedade, codcultura, sincronizado',
  tabeladesconto: 'codtabeladesconto, codcultura, tipo, sincronizado',
  fazenda: 'codfazenda, sincronizado',
  talhao: 'codtalhao, codfazenda, sincronizado',
  safra: 'codsafra, sincronizado',
  plantio: 'codplantio, codsafra, codtalhao, sincronizado',
})

// v2: expedição/embarque (offline) + cache de contratos pro pátio de saída
db.version(2).stores({
  embarque: 'uuid, codembarque, sincronizado, etapa, data',
  contrato: 'codcontrato, sincronizado',
})

export default db
