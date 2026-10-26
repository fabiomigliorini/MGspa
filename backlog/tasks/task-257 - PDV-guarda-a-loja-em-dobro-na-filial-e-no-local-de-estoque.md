---
id: TASK-257
title: 'PDV guarda a loja em dobro: na filial e no local de estoque'
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-24 19:15'
updated_date: '2026-10-24 19:53'
labels:
  - negocios
dependencies: []
priority: low
type: chore
ordinal: 266000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O dispositivo do PDV (tblpdv) tem codfilial e codestoquelocal. Desde a TASK-46 a filial já é derivada do local de estoque ao editar (PdvController::update / PdvService::update), então codfilial é cópia. Eliminar tblpdv.codfilial: a filial do PDV passa a ser sempre tblestoquelocal.codfilial do local dele. Pedido do Fábio em 24/10/2026, depois do levantamento de onde o código usa a filial do PDV.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Banco: api/database/pdv_filial_drop.sql preenche o local de estoque de quem só tinha filial, avisa os PDVs cuja filial não bate com a do local e remove tblpdv.codfilial
- [x] #2 Backend: a filial do PDV vem sempre do local de estoque (permissão do Gerente, lista e filtro de dispositivos, select de PDV, painel do contas, ocorrências, pagamentos/adiantamento/PIX/Stone feitos no PDV, requisição entre lojas)
- [x] #3 Telas continuam mostrando a filial do PDV (página e lista de dispositivos, select de PDV, PDVs da gaveta no contas): agora a do local de estoque
- [x] #4 Front: sai do sSinc.pdv o codfilial/filial que ninguém lia
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Levantamento (24/10): nenhuma view, função ou trigger do banco usa tblpdv; MGLara não usa; o model do MGsis está morto. No front ninguém mais lia sSinc.pdv.codfilial desde a TASK-213.

Como ficou: o model Pdv perdeu codfilial e a relação Filial (e a Filial perdeu PdvS); quem precisa da filial usa $pdv->EstoqueLocal->codfilial. O PdvResource continua devolvendo codfilial e filial, calculados do local, então DialogDispositivo, DispositivoPage, DispositivoDetalhePage, MgSelectPdv e portador/Detalhe do contas não mudaram, e o front velho em cache nos PDVs continua funcionando. A regra não mudou: a requisição entre lojas continua comparando filial (não local), a gaveta tem que ser da filial do PDV, o Gerente vê os dispositivos da filial dele.

Arquivos: Pdv, Filial, IndiceModels.json, PdvController (lista/filtro via whereHas EstoqueLocal; update compara a filial do local atual com a do novo sem carregar a relação, senão a resposta sairia com o local antigo), PdvAutorizador, PdvService (OBRIGATORIOS_ATIVAR sem Filial; sai a cópia no update e no migrarConfiguracao; gaveta x filial do local), PdvResource, SelectPdvController, PortadorResource, PainelService, OcorrenciaService (filial() e esquecidos com left join), RequisicaoService, PagamentoTituloService, TituloAdiantamentoService, CobrancaService, PixService; negocios: stores/sincronizacao.js, stores/dispositivo.js (comentário).

Atenção no commit: PdvService.php e RequisicaoService.php já tinham alteração de outras tasks na árvore (TASK-254/TASK-55).

Go-live: publicar o código e logo depois rodar api/database/pdv_filial_drop.sql (ver ORDEM no cabeçalho).

Teste em dev (24/10): pdv_filial_drop.sql rodado de verdade no banco de dev (206 PDVs receberam o local, nenhum mudou de filial; 2a execução não faz nada) e a fila reiniciada. Bateria pelo Laravel, em transação com rollback, 34 verificações ok:
- PdvResource e select/pdv trazem a filial do local; PDVs da gaveta no PortadorResource;
- lista de dispositivos (admin todos, filtro codfilial, Gerente da 103 só a 103); página e registros (Gerente de outra filial leva 403);
- editar: Gerente mantém o local, não manda para filial onde não é gerente (403); Admin troca de filial e a resposta já vem com o local novo; gaveta de outra filial recusada (422);
- ativar pede Local de Estoque, não Filial;
- OcorrenciaService::filial e esquecidos (geral e do caixa); PainelService::negocios;
- requisição: requisitante/requisitada pela filial do local, PDV sem local ou sem PDV não é nenhum dos dois; GET requisicao pelo X-Pdv;
- pagamento no PDV nasce na filial do local; sem PDV, na do portador.
CLAUDE.md: regra nova na seção do PDV (a loja do PDV é a do local de estoque).
<!-- SECTION:NOTES:END -->
