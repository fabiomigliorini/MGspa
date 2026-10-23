---
id: TASK-231
title: Build da API mostra avisos sobre a bateria de testes do agro
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-16 21:20'
updated_date: '2026-10-21 15:16'
labels:
  - agro
dependencies: []
priority: low
type: chore
ordinal: 249000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No deploy, o composer install --optimize-autoloader mostra ~20 avisos 'does not comply with psr-4' sobre api/tests/agro/ (namespace AgroBateria fora do mapeamento Tests\ => tests/). Nada executa, mas bateria de testes dentro da API nao e o padrao do sistema; decidido remover a bateria do repo (recuperavel em git show 8139b9b2, TASK-184).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Massa ZZTESTE apagada do dev antes de remover a bateria
- [x] #2 api/tests/agro removida
- [x] #3 specs e doc-5 deixam de mandar rodar a bateria
- [x] #4 composer dump-autoload sem avisos psr-4
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
16/10/2026: run.php limpar apagou a massa ZZTESTE do dev (335 cargas, 22 contratos, 51 silos; restantes 0). api/tests/agro e storage/app/agro-bateria removidas. specs/PLANO-AGRO-ARMAZENAMENTO.md e doc-5 com nota de remocao; tasks Done 107/180/183/184 mantidas como historico. composer dump-autoload --optimize: 0 avisos psr-4. artisan optimize no dev falha por permissao em bootstrap/cache (dono usuario, nao 82) — preexistente e fora do escopo; o dev nao usa cache de config.
<!-- SECTION:NOTES:END -->
