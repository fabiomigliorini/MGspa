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

// v3: geometria do talhão passou a viver no plantio (por safra). O plantio
// agora referencia a fazenda direto (codfazenda), não mais um talhão fixo.
db.version(3).stores({
  plantio: 'codplantio, codsafra, codfazenda, sincronizado',
})

// v4: cache de veículos (caminhões) p/ autocomplete da placa offline e dados
// completos no ticket. Motorista = pessoa, buscado online (não cacheado).
db.version(4).stores({
  veiculo: 'codveiculo, placa, sincronizado',
})

// v5: Carga UNIFICADA (recebimento + expedição + transferência) substitui
// cargacolheita + embarque. As origens/destinos (pontos) ficam embutidos no
// registro da carga (array), como os talhões/contratos ficavam antes. O extrato
// (tblmovimentograo) é gerado no servidor; aqui só cacheamos os saldos por
// unidade armazenadora p/ exibir/avisar offline. Cadastro de unidade cacheado.
db.version(5).stores({
  cargacolheita: null, // substituído por 'carga'
  embarque: null, // substituído por 'carga'
  carga: 'uuid, codcarga, sincronizado, codsafra, sentido, etapa, data',
  unidadearmazenadora: 'codunidadearmazenadora, sincronizado',
})

// v6: classificação de grãos por FÓRMULA (catálogo de parâmetros + tabelas
// nomeadas por cultura). Substitui tabeladesconto (faixas fixas). As tabelas
// guardam os itens (valores) aninhados; a carga embute `classificacao` (array
// de leituras), como faz com `pontos`.
db.version(6).stores({
  tabeladesconto: null, // substituído por 'tabelaclassificacao'
  parametroclassificacao: 'codparametroclassificacao, sincronizado',
  tabelaclassificacao: 'codtabelaclassificacao, codcultura, sincronizado',
})

export default db
