---
id: TASK-180
title: Carga finalizada ou cancelada volta atrás quando outro aparelho sincroniza
status: Done
assignee:
  - '@eduardo'
created_date: '2026-09-28 21:11'
updated_date: '2026-09-30 20:33'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 3000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Com dois aparelhos no pátio, a cópia velha de um sobrescreve o que o outro já lançou: carga finalizada volta de etapa e o grão some do silo; carga cancelada volta a valer; em corrida, carga fica FINALIZADA sem ter entrado no estoque. Reenvio da mesma carga dá erro 'Rejeitado' falso ou 500. Causas em CargaService::sincronizar: firstOrNew sem trava, sem versão (o último envio vence), inativo aceito do cliente, inativar/ativar fora de transação. Plano: specs/PLANO-AGRO-ARMAZENAMENTO.md (Fases 1 e 2) com os acréscimos de /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fase 2): request valida uuid, pbt/tara inteiros até 150.000, tara <= pbt, leitura 0-100 sem parâmetro repetido; DB::transaction com retentativa; timeout do pátio não é rejeição; pull não sobrescreve carga pendente.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Cópia velha de outro aparelho não desfaz finalização nem cancelamento; o aparelho é avisado e recebe a versão atual
- [x] #2 Carga FINALIZADA sempre tem o extrato correspondente (nunca uma sem a outra)
- [x] #3 Reenvio da mesma carga (Salvar + ciclo de sync, ou rede caindo) não dá erro nem duplica
- [x] #4 Editar a carga enquanto o envio anterior está no ar não perde a edição nova
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Implementado em 30/09/2026 — aguardando validação (roteiro abaixo)

**Backend** (Fase 1 da spec):
- `tblcarga.versao` (DDL `api/database/agro_carga_versao.sql`), controle otimista.
- `CargaService::sincronizar`: `pg_advisory_xact_lock` pelo uuid antes de ler o
  estado (serializa envios concorrentes da mesma carga), checagem de `versao`
  com `CargaConflitoException` (409 + carga atual) quando diverge, `versao`
  ausente/null = aplica direto (compat com app antigo). `DB::transaction(...,3)`.
- `ativar`/`inativar`: mesma trava + `lockForUpdate` + incremento de `versao`,
  agora dentro de transação (antes rodavam soltos).
- `CargaSincronizarRequest`: `uuid` (regra `uuid`), `pbt`/`tara` inteiros 0–150000
  com `tara <= pbt`, leitura 0–100 com `distinct` no parâmetro.

**Frontend** (Fase 2 da spec):
- `stores/sincronizacao.js`: fila por uuid (`enviarCargaPorUuid`, nunca 2 POSTs
  da mesma carga no ar), trata 409 (grava a versão do servidor, guarda
  `conflito: {em, local}`, NÃO marca `syncerro`), e no 200 confere `revisao`
  antes de sobrescrever (edição no meio do envio não se perde).
- `stores/carga.js::salvar`: grava `revisao` (contador local) e preserva
  `versao` do Dexie antes de enviar.
- Banner "Alterada em outro aparelho" em `CargaResumo.vue` (padrão visual do
  aviso laranja já usado em `CargaBlocoClassificacao`), com dialog "Ver o que
  eu lancei" e "Dispensar".

**Bateria** (`api/tests/agro/`, camada `corrida`): R1, R2, R3, R4, R5, R6, R7
em OK (antes: 500 duplicado, cópia velha desfazia fechamento/cancelamento,
extrato duplicado). Ajustei `lib/Patio.php` pra threadear `versao` entre
chamadas (sem isso R3/R4 não exercitavam o 409 de verdade) e corrigi uma
regressão que isso expôs em `cenarios/Fluxo.php::a5` (o botão admin de
ativar/inativar também grava versão nova; o teste não repassava pro `$c`).
`run.php todos`: OK 57 · FALHA 37 · INFO 15 (era OK 38 · FALHA 59 · INFO 12 em
28/09) — nenhuma FALHA nova; as que sobraram são de TASK-182/170/181/130/183/138/140.

Nada commitado — árvore de trabalho conforme CLAUDE.md, aguardando validação.

## Simplificação em 30/09/2026 (revisão a pedido)

Colisão entre 2 aparelhos na mesma carga é rara — cortei o que só se justificava
por isso: em vez de guardar o que o operador tinha digitado (`conflito`) e
mostrar banner + dialog "Ver o que eu lancei", o 409 agora só adota a versão do
servidor e avisa com um toast (`notifyWarning`). Removido: `dispensarConflito`,
o bloco de UI em `CargaResumo.vue`.

Mantido (não é sobre 2 aparelhos, resolve reenvio do MESMO aparelho — timeout,
duplo toque, sync em cima de Salvar — que é comum): trava por uuid no servidor,
fila por uuid no front, checagem de `revisao` pra não perder edição em voo, e a
checagem de `versao`/409 em si (sem ela o bug original volta: carga finalizada
podendo regredir de etapa).
<!-- SECTION:NOTES:END -->
