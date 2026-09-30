---
id: TASK-184
title: >-
  Antes da colheita, conferir automaticamente estoque, contratos e descontos num
  dia de pátio cheio
status: Done
assignee:
  - '@eduardo'
created_date: '2026-09-28 21:11'
updated_date: '2026-09-29 21:38'
labels:
  - agro
dependencies: []
priority: high
type: task
ordinal: 198000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Não existe teste nenhum do agro (sem phpunit.xml nem banco de teste; RefreshDatabase apagaria o dev) e a bateria da auditoria de 25/09 (api/tests/agro-armazenamento/) se perdeu sem commit. Bateria nova em api/tests/agro/: PHP CLI no container mgspa-api, massa isolada ZZTESTE, ações por HTTP real com curl_multi, camadas de cenários, corrida, descontos, valores, listagem e estresse (aparelhos virtuais: 3 no pico real, rampa até 24), mais conferencia.sql só leitura para rodar na PROD. Plano: /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fases 1 e 10).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A bateria está versionada, documentada e roda com um comando
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

29/09/2026 — classificação confirmada com quem prioriza (registro: backlog/docs/doc-3). Aceito: contrato carregado além do saldo (D12: o caminhão completa a carga; o servidor aceita, o pátio avisa, a tela mostra o excesso) e silo negativo (D1). Confirmados como defeito: transferência para o mesmo silo, tabela nova recalculando romaneio fechado, silo inativo recebendo carga, entregue somando compra; cargas 1, 4, 6 e 8 do dev são teste. Bateria ajustada à D12: C1 e E3 exigem que a carga que passa do saldo seja ACEITA (hoje o servidor barra com 422, falha da TASK-170); C2/C3 conferem o saldo a entregar da tela; C4 e R5 aceitam o excesso; C6 virou roteiro de tela; I7 virou INFO.

29/09/2026: fechada a pedido do usuario — a bateria existe, esta versionada e roda com um comando (AC1). AC3 (rampa) e AC4 (conferencia na PROD) dispensados: o servidor da conta do servico. AC2 rodado em 29/09 (3 aparelhos, 150 caminhoes): desempenho ok (p95 111 ms, 0 erro), mas E4 falha — dois aparelhos gravando o mesmo caminhao deixam etapa e extrato desencontrados em 11 de 20 rodadas (reflexo em I1/I4). Defeito de codigo no servidor, sem task propria ainda; relatado ao usuario.
<!-- SECTION:NOTES:END -->
