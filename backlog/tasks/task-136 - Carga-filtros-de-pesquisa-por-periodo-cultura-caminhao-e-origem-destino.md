---
id: TASK-136
title: 'Carga: filtros de pesquisa por periodo, cultura, caminhao e origem/destino'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-22 12:23'
updated_date: '2026-09-22 12:26'
labels:
  - agro
dependencies: []
priority: medium
type: feature
ordinal: 146000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A listagem de romaneios (tela nova) precisa de filtros que o CargaService::pesquisar hoje nao tem. Estender SEM quebrar o consumidor existente: stores/sincronizacao.js chama GET v1/carga?codsafra&data&etapa&page e depende do comportamento atual.

Escopo:
- Extrair os ifs de filtro num qryFiltros(qry, filter) publico e abrir um 4o parametro opcional $with em pesquisar() (assinatura compativel, CargaController@index nao muda).
- Const WITH_LISTAGEM (eager load enxuto: Safra.Cultura, CargaPontoS.Plantio.Variedade, CargaPontoS.UnidadeArmazenadora, CargaPontoS.Contrato.Pessoa). Marcar a const WITH atual como CONTRATO DO SYNC OFFLINE em comentario.
- Filtros novos: data_inicio/data_fim (range; o filtro data atual com whereDate fica como esta), codcultura (whereHas Safra), placa/placacarreta/motorista (ilike), codveiculo, codpessoamotorista, codunidadearmazenadora, codplantio, codcontrato, codpessoacontrato, papel (ORIGEM|DESTINO, opcional).
- Origem/destino vivem em tblcargaponto (N:N). Usar whereHas (EXISTS), NUNCA join: join duplica a linha da carga e quebra meta.total e a paginacao.
- Um whereHas POR filtro, nunca um so: contatipo e excludente (sincronizarPontos grava so uma das tres FKs), entao unidade+talhao num whereHas unico daria sempre zero.
- Papel casa em qualquer lado por padrao (o silo e DESTINO no recebimento e ORIGEM na expedicao).
- Indices novos em api/database/agro_carga_listagem.sql (idempotente, sem migrations): ix_carga_data, ix_carga_safra_data e tres parciais em tblcargaponto (unidade/plantio/contrato).

Origem: pedido do Fabio (21/09/2026) de uma tela de listagem de todas as cargas com filtros e relatorio PDF.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado.

Arquivos:
- api/app/Mg/Grao/CargaService.php
  - pesquisar() ganhou 4o parametro opcional $with (default WITH). Assinatura compativel: CargaController@index nao mudou.
  - Filtros extraidos para qryFiltros(qry, filter) publico -- fonte unica de WHERE, que o relatorio PDF vai reusar.
  - qryFiltrosPonto(): origem/destino via whereHas, UM por filtro. Comentario explica por que nao pode ser join (duplica linha, quebra meta.total) nem um whereHas unico (contatipo e excludente -> sempre vazio).
  - const WITH ganhou comentario CONTRATO DO SYNC OFFLINE + a relacao CargaPontoS.Plantio.Fazenda (o romaneio da TASK-139 precisa do nome da fazenda; aditivo, +1 query).
  - const WITH_LISTAGEM nova (enxuta, sem classificacao/Talhao/Veiculo/PessoaMotorista).
  - Comentario no filtro inativo avisando que a AUSENCIA da chave traz canceladas.
- api/database/agro_carga_listagem.sql (novo): ix_carga_data, ix_carga_safra_data + 3 indices PARCIAIS em tblcargaponto.

Verificado no dev (8 cargas):
- baseline 8 | data=2026-09-18 (dia unico, contrato do sync) 1 | periodo 16..18 5 | sentido=ENTRADA 5 | etapa=FINALIZADO 2
- papel funciona: unidade 1 qualquer=8, ORIGEM=4, DESTINO=7
- E logico entre tipos: unidade 1 + plantio 1 = 1 (prova que os EXISTS separados funcionam; num whereHas unico daria 0)
- SQL confirmado com dois 'exists' correlacionados independentes
- paginate(): total=8, itens=8, codcarga distintos=8 -> SEM duplicacao
- Regressao do sync: CargaResource ainda traz classificacao (4 leituras), CargaPontoS (2 pontos), Safra.Cultura e Plantio.Talhao. Plantio.Fazenda nova resolve 'Renascer'.
- SQL rodado 2x no dev: idempotente (NOTICE ... skipping).

PENDENTE EM PROD: rodar api/database/agro_carga_listagem.sql na producao (sem migrations, nada registra o aplicado).
<!-- SECTION:NOTES:END -->
