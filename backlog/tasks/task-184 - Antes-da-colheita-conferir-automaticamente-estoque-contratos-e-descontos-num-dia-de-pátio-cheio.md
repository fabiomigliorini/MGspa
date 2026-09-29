---
id: TASK-184
title: >-
  Antes da colheita, conferir automaticamente estoque, contratos e descontos num
  dia de pátio cheio
status: In Progress
assignee:
  - '@eduardo'
created_date: '2026-09-28 21:11'
updated_date: '2026-09-28 21:52'
labels:
  - agro
dependencies: []
priority: high
type: task
ordinal: 197000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Não existe teste nenhum do agro (sem phpunit.xml nem banco de teste; RefreshDatabase apagaria o dev) e a bateria da auditoria de 25/09 (api/tests/agro-armazenamento/) se perdeu sem commit. Bateria nova em api/tests/agro/: PHP CLI no container mgspa-api, massa isolada ZZTESTE, ações por HTTP real com curl_multi, camadas de cenários, corrida, descontos, valores, listagem e estresse (aparelhos virtuais: 3 no pico real, rampa até 24), mais conferencia.sql só leitura para rodar na PROD. Plano: /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fases 1 e 10).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A bateria está versionada, documentada e roda com um comando
- [ ] #2 Um dia com 3 aparelhos roda sem erro e sem número divergente
- [ ] #3 O limite do servidor foi medido
- [ ] #4 A conferência só-leitura foi rodada na PROD antes e depois da publicação
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Fase 1 — bateria e linha de base (28/09/2026)

Bateria em api/tests/agro/ (README.md explica tudo): run.php + lib/ (Ambiente, Api com curl_multi, Relatorio, Referencia com as regras decididas em bcmath, Massa ZZTESTE, Patio que emula o app, Invariantes, Cenario) + cenarios/ (Fluxo A, Contrato C, Silos T, Corrida R, Desconto D, Valores V, Listagem L, Permissoes P) + estresse/ (Aparelho, Estresse) + conferencia.sql (as invariantes I1-I14, só leitura, a mesma SQL que a bateria usa) + desconto-front.mjs (roda o desconto.js/ticket.js reais do pátio no node).

Linha de base, run.php todos: OK 38, FALHA 59, INFO 12, ERRO 0, em 161 s. Paridade no node: 4 FALHA (F1 a F4). Conferência do dev inteiro: I4 (cargas 2, 3, 9) e I14 (cargas 1, 4, 6, 8).

Destaques: E4 = com dois aparelhos gravando o mesmo caminhão, 7 a 12 de 20 cargas ficam FINALIZADAS sem extrato; R5/E3 = o contrato passa do teto (75 t e 100 t em 60 t); R6 = extrato duplicado em 5-6 de 10; D = a fórmula é a da norma em 10.000/10.000 vetores (gramas), mas 9.706 saem fora do kg inteiro (até 2 kg); P1 = 91 de 91 rotas abertas para usuário só de Caixa; E1 = 3 aparelhos e 150 caminhões com p95 de ~100 ms e zero erro.

Pendente: E2 (rampa até 24 aparelhos) pede horário combinado, porque deixa a API dev lenta para todos. A massa do estresse ficou no dev (safras ZZTESTE ... 2099) para conferir na tela; run.php limpar apaga.
<!-- SECTION:NOTES:END -->
