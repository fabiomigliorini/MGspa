---
id: TASK-95
title: Nome do banco não aparece no card de Contas Bancárias
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 16:02'
updated_date: '2026-09-12 16:26'
labels:
  - pessoas
dependencies: []
priority: high
type: bug
ordinal: 4000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na tela /pessoa/:id (ex.: pessoas.mgpapelaria.com.br/pessoa/3105), o card CONTAS BANCÁRIAS mostra a linha sem o nome do banco: "Corrente, , 1, 1180-0, 99355-7" — fica vazio entre a vírgula.

Causa provável (conferida no código): o model app/Mg/Pessoa/PessoaConta.php NÃO declara a relação Banco — só tem Pessoa, UsuarioAlteracao e UsuarioCriacao (linhas 54-66). O PessoaContaResource faz $ret['nomeBanco'] = @$this->Banco->banco; e o @ engole o erro, devolvendo null em silêncio. O front (pessoas/src/components/pessoa/CardPessoaConta.vue:491) renderiza contas.nomeBanco, que chega vazio.

Correção sugerida: declarar em PessoaConta a relação belongsTo(Mg\\Banco\\Banco::class, 'codbanco', 'codbanco') e remover o @ do Resource para o erro não voltar a passar despercebido. A tabela tblbanco tem a coluna 'banco'.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Card de Contas Bancárias mostra o nome do banco
- [x] #2 Resource não usa @ para suprimir o erro
<!-- AC:END -->
