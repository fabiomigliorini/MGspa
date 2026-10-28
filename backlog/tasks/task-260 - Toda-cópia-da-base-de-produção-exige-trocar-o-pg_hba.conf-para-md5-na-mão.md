---
id: TASK-258
title: Toda cópia da base de produção exige trocar o pg_hba.conf para md5 na mão
status: To Do
assignee: []
created_date: '2026-10-28 15:25'
labels: []
dependencies: []
priority: low
type: chore
ordinal: 267000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ao restaurar o backup de produção numa base nova (MGdb/copiar-base-producao), nada conecta (API, MGLara, MGsis, DBeaver) até trocar no pg_hba.conf a linha 'host all all all scram-sha-256' por 'host all all all md5'.

Causa: os roles mgsis, mgsis_yii e agroproject têm a senha guardada em hash md5 (legado de versões antigas do Postgres; mgmkt e postgres já estão em SCRAM-SHA-256). O PostgreSQL 14 cria o pg_hba.conf com scram-sha-256, que não autentica senha guardada em md5. Com 'md5' no pg_hba o servidor aceita os dois formatos, por isso o workaround funciona. Desde o commit 8c92ae8 do MGdb o copiar-base-producao faz essa troca automaticamente.

Correção: em produção, regravar as senhas com as MESMAS senhas atuais (password_encryption já é scram-sha-256), sem mudar nada nas aplicações:
  ALTER ROLE mgsis PASSWORD '<senha atual>';
  ALTER ROLE mgsis_yii PASSWORD '<senha atual>';
  ALTER ROLE agroproject PASSWORD '<senha atual>';
Conferir com: select rolname, left(rolpassword,13) from pg_authid where rolpassword is not null;

Risco: todo cliente precisa suportar SCRAM (libpq >= 10 no PHP pdo_pgsql/pgsql, JDBC >= 42.2 no DBeaver). Atenção especial ao MGsis se rodar em PHP/libpq antigo. Precisa ser feito pelo Fabio em produção.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Testado na base local: senhas regravadas em SCRAM, pg_hba em scram-sha-256, e API, MGLara, MGsis e DBeaver conectando
- [ ] #2 Senhas de mgsis, mgsis_yii e agroproject regravadas em SCRAM em produção, sem alterar as senhas
- [ ] #3 pg_hba.conf de produção usando scram-sha-256 no lugar de md5
- [ ] #4 Removido do MGdb/copiar-base-producao o passo AJUSTANDO PG_HBA.CONF PARA MD5
<!-- AC:END -->
