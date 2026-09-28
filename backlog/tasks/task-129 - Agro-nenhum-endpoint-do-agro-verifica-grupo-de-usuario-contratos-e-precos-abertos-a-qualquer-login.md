---
id: TASK-129
title: >-
  Agro: nenhum endpoint do agro verifica grupo de usuario (contratos e precos
  abertos a qualquer login)
status: To Do
assignee: []
created_date: '2026-09-21 21:33'
updated_date: '2026-09-28 21:00'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 140000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Os 17 controllers do dominio agro (Grao: Carga/MovimentoGrao/UnidadeArmazenadora; Contrato + Fixacao/Cambio/Pagamento/Nota/Anexo; Safra; Fazenda/Plantio/Talhao; Cultura/CulturaTributo/Variedade; ParametroClassificacao) nao chamam Autorizador::autoriza em nenhum metodo - conferido com grep, todos zero. As rotas estao sob auth:api, entao QUALQUER usuario autenticado do MG (caixa, vendedor, RH) pode ler contratos com preco/fixacao/recebimento, lancar ajuste no extrato, finalizar carga e excluir plantio. O padrao do monorepo e o oposto: Banco, Cheque, ContaContabil, Titulo etc. declaram const GRUPOS e chamam Autorizador::autoriza em cada acao. No front tambem: as rotas do agro (router/routes.js) so tem meta.auth, sem meta.permissions - o contas usa PERMISSOES.ADMINISTRADOR/FINANCEIRO em toda rota. Definir o(s) grupo(s) do agro (ex.: Administrador + Agro, leitura x mutacao) e aplicar nos dois lados.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Agro abre só para Administrador e Gerente, na tela e na API
- [ ] #2 Quem opera o pátio hoje está num desses grupos antes da publicação
- [ ] #3 Pátio aberto sem internet continua funcionando
<!-- AC:END -->
