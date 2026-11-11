---
id: TASK-264
title: Parear de novo a maquininha SafraPay cria outra maquineta e inativa a antiga
status: In Progress
assignee: []
created_date: '2026-11-04 15:42'
updated_date: '2026-11-11 18:34'
labels:
  - contas
dependencies: []
priority: critical
type: bug
ordinal: 3000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Sintoma: no contas → Maquinetas → Parear de novo, quando o S2 Pay é reinstalado ou o aparelho é trocado, a leitura do QR cria outra maquineta e inativa a antiga. Pagamentos, períodos (conferência do borderô) e a maquineta padrão do PDV ficam divididos em dois registros. Aconteceu em produção em 04/11/2026: maquininha travada, app reinstalado, re-pareamento criou registro novo.

Por que Critical: prioridade de quem prioriza. Maquininha travada exige reinstalar ou trocar o aparelho na hora, e hoje cada pareamento parte o registro da maquineta em produção.

Causa: a maquineta SafraPay está amarrada ao pinpad (M3 da TASK-188, doc-3: "maquineta = pinpad"). App reinstalado, aparelho novo ou vindo de outro QR volta da Saurus com outro id de pinpad; MaquinetaService::daSaurusPinPad faz firstOrCreate por codsauruspinpad (maquineta nova) e parearSaurusPinPad inativa a anterior. O QR é do PDV Saurus e o Parear de novo já reaproveita o mesmo; o problema é só o cadastro. Sem reinstalar, o mesmo aparelho volta com o mesmo id e não cria nada.

Regra nova: o registro é o QR (PDV Saurus). Qualquer aparelho que lê o QR de uma maquineta vira o aparelho dela.

Correção (sem DDL e sem parâmetro novo na rota):
- SaurusService::verificarLeitura: pinpad gravado por id + codsauruspdv (aparelho vindo de outro QR ganha registro neste QR e não arrasta a maquineta de lá).
- SaurusService::consultarPedido: pinpad por idPinPad + codsauruspdv do pedido, com value(); nulo cai no pinpad mais novo do QR (acaba o 500 do aparelho com leitura ainda não confirmada).
- MaquinetaService::daSaurusPinPad vira resolver: a maquineta do pinpad; senão a do QR (ativa primeiro, depois a do pinpad mais novo); senão cria.
- MaquinetaService::parearSaurusPinPad: re-aponta essa maquineta para o pinpad novo e ativa; inativa os outros pinpads e maquinetas do mesmo QR. Resolver e re-apontamento entram juntos, senão o cartão do pinpad antigo cria maquineta fantasma.
- Texto do MaquinetaParearDialog.vue, comentários "um por pinpad" (Maquineta.php, service, controller, maquinetaStore) e nota no doc-3 (M3).

Fora do escopo: juntar cadastros ou corrigir dados de produção (a maquineta antiga do incidente fica inativa) e as duplicatas de 2025 (7 QRs com 2 ou 3 maquinetas ficam como estão). Achado à parte, sem task: Juntar com outra (manual) dá 500 quando a errada já tem período ou é maquineta padrão de PDV (FKs de tblmaquinetalote/tblpdv no delete; a transação desfaz).

Teste em dev: tinker em transação com rollback. Não chamar saurus/qrcode nem saurus/confirmar: o dev não tem SAURUS_S2PAY_* e os PDVs Saurus têm os ids da produção. PDV Saurus 1 (maquinetas 90/98/102 nos pinpads 1/9/13): pinpad fake parea na 102 sem maquineta nova (count 139); pinpad com o id do pinpad 19 (PDV 18) parea na 102 e a 108 fica intacta; pedido 236210 dá 102; QR novo cria a maquineta. Em produção: o próximo Parear de novo com o aparelho trocado mantém o codmaquineta.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Parear de novo mantém a mesma maquineta com qualquer aparelho que leia o QR (o mesmo, com o app reinstalado, um novo ou um vindo de outro QR): pagamentos, períodos e maquineta padrão do PDV ficam nela; só o pinpad troca e o anterior é inativado
- [ ] #2 O cartão cobrado no QR cai na maquineta do QR, sem nascer outra: inclusive do pinpad antigo confirmado depois do novo pareamento e do aparelho cuja leitura ainda não foi confirmada (hoje dá erro)
- [x] #3 A maquineta duplicada em produção (153) foi juntada na 108 sem perder nada
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
CORREÇÃO DE DADOS EM PRODUÇÃO (04/11/2026) — juntar a 153 na 108, por SQL, sem perder nada.

Diagnóstico de produção (cópia de segurança, antes da correção):
- QR (PDV Saurus) da 108: pinpad 19, id 0ba11877-b74c-418f-9b82-bb158e229520, criado 2025-09-26 11:06:29, inativado 2026-11-04 10:47:42 → maquineta 108 (inativada 2026-11-04 10:47:42).
- Pinpad 32, id b8a36e05-cbaa-4c62-8f38-af44b43de0d0, criado 2026-11-04 10:43:24, ativo → maquineta 153 (ativa).
- 153: 0 pagamentos, 0 períodos, 0 cartões Saurus. 108: 16 pagamentos. 5 cobranças pendentes no QR nos últimos 3 dias (conferir as de antes das 10:43 antes de corrigir).

Por que não apontar a 108 para o pinpad 32: sem esta task no ar, o pinpad 19 ficaria sem maquineta e todo cartão antigo re-consultado (ou usado numa baixa de título, já no portadorPrevisto) criaria maquineta fantasma via daSaurusPinPad. Então o pinpad 19 recebe o id do aparelho novo; a 153 e o pinpad 32 saem. Efeito até esta task entrar: re-consultar na Saurus um cartão do aparelho velho dá erro sem gravar nada.

Ensaio no dev (transação com ROLLBACK, 04/11/2026): incidente simulado na 108 (pinpad novo 32, maquineta nova 140, um cartão Saurus no pinpad novo) → depois da correção a 108 fica ativa no pinpad 19 com o id novo, 140 e 32 saem, pagamentos (10) e períodos (1) da 108 iguais, cartão passa para o 19, id novo resolve na 108. Trava: com pagamento na nova, o DELETE falha na fk_tblpagamento_tblmaquineta e nada é gravado.

Para a implementação: com o pinpad 19 levando o id novo, o consultarPedido desta task não pode sobrescrever com nulo o codsauruspinpad já gravado de um cartão antigo (pinpad não achado → mantém o gravado).

Executado em produção em 04/11/2026. Conferência: só a 108, ativa, no pinpad 19 com o id b8a36e05-cbaa-4c62-8f38-af44b43de0d0 (ativo), 16 pagamentos, pinpad 32 removido.
<!-- SECTION:NOTES:END -->
