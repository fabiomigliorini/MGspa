---
id: TASK-107
title: >-
  Agro: rodar agro_classificacao_parametro.sql no dev (classificacao de
  soja/milho vazia)
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-17 14:51'
updated_date: '2026-09-29 14:26'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 106000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O banco dev nao tem nenhum parametro de classificacao de soja nem de milho, entao nenhuma carga recebe desconto.

**Causa:** o codigo esta no modelo \"parametro por cultura\" (commit 14cf6d6b, 11/08/2026), mas o script `api/database/agro_classificacao_parametro.sql` nunca rodou no dev. O banco continua no esquema de julho (`agro_classificacao.sql`, commit 999867ce):
- `tblparametroclassificacao` nao tem `codcultura/ordem/tolerancia/fator/desagio`, entao `ParametroClassificacaoService::daCultura()` consulta uma coluna que nao existe
- `tbltabelaclassificacao` e `tbltabelaclassificacaoitem` ainda existem, e `codtabelaclassificacao` segue em `tblcultura/tblcontrato/tblcarga`
- todas as tabelas de classificacao estao VAZIAS (0 linhas); a base foi recriada depois de jul/2026

**Correcao:** rodar o script, que e idempotente, e conferir o resultado:

    docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < api/database/agro_classificacao_parametro.sql

Com o banco vazio, as migracoes do passo 2 nao fazem nada e o passo 3 semeia o padrao da norma, tudo pelo metodo NORMALIZADO:
- Soja (IN MAPA 11/2007): Impureza 1, Umidade 14, Avariados 8, Esverdeados 8, Quebrados 30
- Milho (IN MAPA 60/2011): Impureza 1, Umidade 14, Avariados 6

**Conferir depois:** se a PROD (e a base de onde o dev e restaurado) esta no mesmo estado.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
28/09/2026: o dev já está no esquema novo (tblparametroclassificacao com codcultura, 8 parâmetros da norma: Soja 5, Milho 3; tabelas do modelo de julho e colunas codtabelaclassificacao já removidas). Falta conferir a PROD com api/tests/agro/conferencia.sql antes de fechar.

29/09/2026: kit da PROD pronto em api/database/agro/ — README.md com o roteiro, 00-diagnostico.sql (so leitura: FEITO/PENDENTE por passo + contagem do que cada pendente mexe) e os passos 01-29 com guarda. Dev 100%: 01-29 aplicados, backups _bkp dropados, massa ZZTESTE apagada; conferencia I12/I12b vazia. Falta: usuario rodar o 00 na PROD, mandar o resultado, rodar 01-29 e a conferencia.
<!-- SECTION:NOTES:END -->
