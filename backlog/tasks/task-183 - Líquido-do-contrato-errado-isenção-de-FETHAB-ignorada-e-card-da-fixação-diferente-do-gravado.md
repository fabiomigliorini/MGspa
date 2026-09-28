---
id: TASK-183
title: >-
  Líquido do contrato errado: isenção de FETHAB ignorada e card da fixação
  diferente do gravado
status: To Do
assignee: []
created_date: '2026-09-28 21:11'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 196000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
ContratoFixacaoService::recalcular nunca passa isentofethab, então contrato isento tem FETHAB descontado no líquido da fixação; fixação com tributos vazios cai na configuração ao vivo. O card e o diálogo da fixação recalculam no front com outro arredondamento (até R$ 2,05 em 20.000 sc), e a conferência 'Bate' compara recebido líquido com NF bruta. A edição da fixação não confere o que já foi travado (quantidade ou preço abaixo do travado, troca de moeda com câmbio travado). Preço médio tem 3 definições; a comissão total vem pronta do cliente. Confirmar com a contabilidade o mês da UPF do FETHAB (hoje jan/jul do ano anterior; a planilha antiga usava janeiro do mesmo ano). Plano: /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fase 7).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Contrato isento não tem FETHAB descontado
- [ ] #2 O líquido mostrado na fixação é o gravado, ao centavo
- [ ] #3 Fixação sem tributos informados guarda os da época
- [ ] #4 Não dá para reduzir quantidade ou preço abaixo do travado, nem trocar a moeda com câmbio travado
- [ ] #5 A conferência do recebimento compara líquido com líquido
- [ ] #6 O preço médio é o mesmo no contrato, na safra e no detalhe
- [ ] #7 A comissão é calculada pelo servidor
<!-- AC:END -->
