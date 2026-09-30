---
id: TASK-180
title: Carga finalizada ou cancelada volta atrás quando outro aparelho sincroniza
status: In Progress
assignee: []
created_date: '2026-09-28 21:11'
updated_date: '2026-09-30 15:07'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 2000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Com dois aparelhos no pátio, a cópia velha de um sobrescreve o que o outro já lançou: carga finalizada volta de etapa e o grão some do silo; carga cancelada volta a valer; em corrida, carga fica FINALIZADA sem ter entrado no estoque. Reenvio da mesma carga dá erro 'Rejeitado' falso ou 500. Causas em CargaService::sincronizar: firstOrNew sem trava, sem versão (o último envio vence), inativo aceito do cliente, inativar/ativar fora de transação. Plano: specs/PLANO-AGRO-ARMAZENAMENTO.md (Fases 1 e 2) com os acréscimos de /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fase 2): request valida uuid, pbt/tara inteiros até 150.000, tara <= pbt, leitura 0-100 sem parâmetro repetido; DB::transaction com retentativa; timeout do pátio não é rejeição; pull não sobrescreve carga pendente.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Cópia velha de outro aparelho não desfaz finalização nem cancelamento; o aparelho é avisado e recebe a versão atual
- [ ] #2 Carga FINALIZADA sempre tem o extrato correspondente (nunca uma sem a outra)
- [ ] #3 Reenvio da mesma carga (Salvar + ciclo de sync, ou rede caindo) não dá erro nem duplica
- [ ] #4 Editar a carga enquanto o envio anterior está no ar não perde a edição nova
<!-- AC:END -->
