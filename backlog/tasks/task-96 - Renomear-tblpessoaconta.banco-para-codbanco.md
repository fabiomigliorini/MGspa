---
id: TASK-96
title: Renomear tblpessoaconta.banco para codbanco
status: To Do
assignee: []
created_date: '2026-09-12 16:26'
labels:
  - pessoas
dependencies: []
type: chore
ordinal: 95000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Descoberto ao resolver TASK-95. A coluna tblpessoaconta.banco eh numeric(3,0) mas guarda o codbanco (FK para tblbanco.codbanco) - confirmado: 268 registros, zero orfaos, e valores como 10 (Santander) e 760 (Nubank) so existem como codbanco, nunca como numerobanco. O MgSelectBanco tambem devolve value = codbanco.

Fere o padrao do projeto (FK sempre codXxx inteiro -> PK) e ja causou colisao de nomes na serializacao: a relacao Banco vira 'banco' no toArray e sobrescreve a coluna homonima, contornado a mao no PessoaContaResource.

Escopo: DDL de renomear coluna para codbanco (bigint) + FK para tblbanco, ajustar PessoaConta (fillable/casts/relacao), PessoaContaResource (remover o workaround), PessoaContaStore/UpdateRequest e o front (CardPessoaConta.vue). DDL precisa ser rodado em producao pelo Fabio.
<!-- SECTION:DESCRIPTION:END -->
