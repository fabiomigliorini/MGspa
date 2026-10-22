---
id: TASK-183
title: >-
  Líquido do contrato errado: isenção de FETHAB ignorada e card da fixação
  diferente do gravado
status: To Do
assignee:
  - '@eduardo'
created_date: '2026-09-28 21:11'
updated_date: '2026-10-22 15:40'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 204000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
ContratoFixacaoService::recalcular nunca passa isentofethab, então contrato isento tem FETHAB descontado no líquido da fixação; fixação com tributos vazios cai na configuração ao vivo. O card e o diálogo da fixação recalculam no front com outro arredondamento (até R$ 2,05 em 20.000 sc), e a conferência 'Bate' compara recebido líquido com NF bruta. A edição da fixação não confere o que já foi travado (quantidade ou preço abaixo do travado, troca de moeda com câmbio travado). Preço médio tem 3 definições; a comissão total vem pronta do cliente. Confirmar com a contabilidade o mês da UPF do FETHAB (hoje jan/jul do ano anterior; a planilha antiga usava janeiro do mesmo ano). Plano: /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fase 7).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Contrato isento não tem FETHAB descontado
- [x] #2 O líquido mostrado na fixação é o gravado, ao centavo
- [x] #3 Fixação sem tributos informados guarda os da época
- [x] #4 Não dá para reduzir quantidade ou preço abaixo do travado, nem trocar a moeda com câmbio travado
- [x] #5 A conferência do recebimento compara líquido com líquido
- [x] #6 O preço médio é o mesmo no contrato, na safra e no detalhe
- [x] #7 A comissão é calculada pelo servidor
- [x] #8 No pátio, o mapa do talhão não troca a safra já escolhida em Operação
- [ ] #9 Excluir, inativar ou reativar uma fixação atualiza a comissão do contrato
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Linha de base 28/09 (api/tests/agro, camada V): a isenção de FETHAB DECLARADA no modal (linha do grupo com alíquota zerada) já sai sem FETHAB (V2 OK, líquido R$ 59.880,00 em 500 sc a R$ 120). O buraco real é a fixação gravada SEM tributos (V2b): nada é guardado e o líquido segue a tabela ao vivo — daí o risco de cobrar FETHAB de quem é isento e de mudar fixação antiga quando a tabela muda. Confirmado também: V5 (edição abaixo do travado passa) e V7 (plano de NF dá 500, Undefined array key itens).

01/10/2026 — implementado, aguardando validação. Backend: fixação sem tributos passa a GRAVAR os vigentes (ContratoFixacaoService::liquido), então a isenção declarada e os tributos da época ficam congelados; ContratoFixacaoRequest recusa quantidade×preço abaixo do câmbio travado e troca de moeda com trava; preço médio com definição única (R$ ÷ sacas firmes em R$, ContratoCalculoService::sacasFirmes); comissão calculada no servidor (ContratoService::aplicarComissao, reaplicada a cada recálculo de fixação; request não aceita mais comissaototal). Front: card da fixação mostra o liquidobrl gravado e a prévia usa o arredondamento do servidor; 'Bate' compara recebido com carregado líquido; contrato mostra o preço médio líquido; comissão vem do servidor. Pedido extra: PlantioMapaDialog não oferece trocar a safra quando a carga já tem (definida em Operação) e o talhão não sobrescreve mais a safra. Sem flag nova de isenção: a isenção continua sendo zerar a linha do FETHAB no modal. Fora: V7 (TASK-133) e mês da UPF (contabilidade). Bateria todos: OK 64 · FALHA 30 (era 62/32); V2b e V5 OK, nenhuma FALHA nova.

22/10/2026 — conferência no código (só leitura) e critérios #1 a #8 ticados com a validação do usuário. Ressalvas: #1 a isenção é declarada zerando a linha do FETHAB no modal da fixação; não há isenção automática por cooperativa. #3 os tributos da época só ficam gravados quando a fixação é recalculada (fixação antiga sem tributos não tem backfill). #6 o preço médio dá o mesmo número, mas a conta está em 3 cópias (ContratoCalculoService::calcularDoContrato, ContratoResource, SafraService::resumoComercial). Critério #9 novo: ContratoFixacaoController::destroy/inativar/ativar não chamam ContratoFixacaoService::recalcular nem ContratoService::aplicarComissao (só o update recalcula), então a comissão em % fica com o valor antigo. Como ver: contrato com comissão %, excluir uma fixação e olhar o Total da comissão (ContratoDados). Volta para To Do por causa do #9; responsável mantido.
<!-- SECTION:NOTES:END -->
