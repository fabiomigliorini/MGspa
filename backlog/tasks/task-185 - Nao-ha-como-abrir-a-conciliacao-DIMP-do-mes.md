---
id: TASK-185
title: Nao ha como abrir a conciliacao DIMP do mes
status: To Do
assignee: []
created_date: '2026-09-28 23:36'
updated_date: '2026-10-10 19:07'
labels:
  - contas
dependencies: []
references:
  - >-
    backlog/docs/doc-2 -
    Plano-da-interface-e-do-fechamento-da-conciliacao-DIMP.md
  - .claude/contexto-dimp.md
priority: medium
type: feature
ordinal: 198000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O relatorio de conciliacao DIMP existe e funciona, mas nao tem ponto de entrada em nenhum app: e so a rota GET /api/v1/dimp/conciliacao?ano=&mes=&codfilial=, protegida por auth:api (Bearer). Colada no navegador da 401, entao na pratica ninguem consegue rodar. Quem precisa dele e o financeiro/contador respondendo notificacao de malha fiscal.

O backend saiu como milestone 7 da TASK-38 (commit d1c3cf251) e la o criterio foi marcado como concluido -- o que faltou, e o que esta task cobre, e a tela e o fechamento da conta que a tese fiscal pede.

ATENCAO antes de executar: o relatorio de hoje NAO fecha em zero como a tese doc-1 secao 4.4 prescreve, e nao e so uma linha faltando. Fazer so a tela deixa o trabalho furado. O plano completo, com as decisoes ja tomadas, o diagnostico do furo e as 3 opcoes de escopo, esta em backlog/docs/doc-2. LER ANTES DE COMECAR.

Contexto de como o backend chegou nesse estado: .claude/contexto-dimp.md
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tela da conciliacao DIMP acessivel no app contas, grupo de menu Movimento
- [ ] #2 Filtro de ano, mes e filial (default Todas) na tela
- [ ] #3 Painel na tela com os numeros da apuracao, carregado por botao Apurar explicito (a apuracao leva 30 a 50 segundos, nao pode carregar sozinha ao abrir)
- [ ] #4 Botao de imprimir o PDF, pelo helper abrirPdf, habilitado depois de apurar
- [ ] #5 Permissao alinhada entre a rota do front e o Autorizador do backend
- [ ] #6 Linha dos recebimentos de crediario apurada dos dois lados da conta (soma no total da adquirente e subtrai como explicacao), conforme doc-1 secao 4.4
- [ ] #7 Conferir o primeiro mes apurado depois da conversao do legado (milestone 9 dropou tblvalecompra e os vales antigos agora aparecem como tblnegociovale em negocios retroativos, mudando a contagem dos meses historicos)
- [ ] #8 Antes da tela: refazer o diagnóstico do furo e a linha do crediário do doc-2 sobre o modelo novo (tblpagamento + tblnegocioparcela)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): o doc-2 (28/09) e o AC #6 foram montados sobre tblnegocioformapagamento e tblliquidacaotitulo, que a TASK-188 trocou por tblpagamento + tblnegocioparcela (M4 ed90fe2e8, M6 bc911e922). O DimpConciliacaoService já lê o modelo novo (linhas 190-215).
<!-- SECTION:NOTES:END -->
