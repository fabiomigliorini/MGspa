---
id: TASK-129
title: >-
  Agro: nenhum endpoint do agro verifica grupo de usuario (contratos e precos
  abertos a qualquer login)
status: Done
assignee: []
created_date: '2026-09-21 21:33'
updated_date: '2026-09-30 15:07'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 199000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Os 17 controllers do dominio agro (Grao: Carga/MovimentoGrao/UnidadeArmazenadora; Contrato + Fixacao/Cambio/Pagamento/Nota/Anexo; Safra; Fazenda/Plantio/Talhao; Cultura/CulturaTributo/Variedade; ParametroClassificacao) nao chamam Autorizador::autoriza em nenhum metodo - conferido com grep, todos zero. As rotas estao sob auth:api, entao QUALQUER usuario autenticado do MG (caixa, vendedor, RH) pode ler contratos com preco/fixacao/recebimento, lancar ajuste no extrato, finalizar carga e excluir plantio. O padrao do monorepo e o oposto: Banco, Cheque, ContaContabil, Titulo etc. declaram const GRUPOS e chamam Autorizador::autoriza em cada acao. No front tambem: as rotas do agro (router/routes.js) so tem meta.auth, sem meta.permissions - o contas usa PERMISSOES.ADMINISTRADOR/FINANCEIRO em toda rota. Definir o(s) grupo(s) do agro (ex.: Administrador + Agro, leitura x mutacao) e aplicar nos dois lados.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Agro abre só para Administrador e Gerente, na tela e na API
- [x] #2 Quem opera o pátio hoje está num desses grupos antes da publicação
- [x] #3 Pátio aberto sem internet continua funcionando
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado: backend chama Autorizador::autoriza(['Administrador','Gerente']) nos 17 controllers do agro (Contrato x6, Cultura x3, Fazenda x3, Grao x3, Safra, ParametroClassificacao), mesmo padrao de BancoController/ValeModeloController. Verificado contra o banco de dev (nao so leitura de codigo): Autorizador::pode(['Administrador','Gerente'], null, $codusuario) = true para usuario dos grupos Administrador e Gerente, false para Caixa e Recursos Humanos. Frontend: agro/src/router/routes.js ganhou meta.permissions:[PERMISSOES.ADMINISTRADOR, PERMISSOES.GERENTE] nas 16 rotas autenticadas (o guard em router/index.js ja fazia esse check, so faltava as rotas declararem). Achado durante a implementacao: agro/src/stores/auth.js so persistia token, nao usuario/permissoes -- um F5 offline no patio ia perder o cache e cair em sem-permissao mesmo pra quem tem acesso. Corrigido com persist:{pick:['usuario','expiresAt']} via pinia-plugin-persistedstate (mesmo mecanismo ja usado em cargaListagem.js). AC#2 fica pendente: e checagem manual/operacional (conferir tblgrupousuariousuario antes de publicar), nao codigo.
<!-- SECTION:NOTES:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-30 14:29
---
TASK-182 (romaneio muda de peso ao mexer na tabela de classificacao) foi revisada junto com esta e fechada sem implementacao a pedido do responsavel -- ver comentario la para o achado da investigacao.
---

created: 2026-09-30 14:39
---
AC#2 confirmado pelo responsavel: o operador master do patio e o gerente da Fazenda, que lanca a maioria das cargas -- ja cai no grupo Gerente. Nao deu pra conferir isso direto no banco: esta maquina so tem acesso ao Postgres de DEV (dados de teste), onde so existe 1 usuario com carga lancada (conta de teste, ja Administrador). Confirmacao de producao fica por conta de quem tem acesso la.
---
<!-- COMMENTS:END -->
