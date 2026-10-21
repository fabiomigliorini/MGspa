---
id: TASK-251
title: >-
  Usuários e grupos só no Pessoas: o MGLara ainda cadastra em paralelo e o
  cadastro de grupos do Pessoas não funciona
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-21 21:49'
updated_date: '2026-10-21 21:50'
labels:
  - pessoas
dependencies: []
priority: low
type: bug
ordinal: 261000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato do Fábio (21/10/2026): usuários e grupos são cadastrados no Pessoas, mas o MGLara ainda tinha as telas de Usuários, Grupos e Permissões (menu Admin e Perfil). Desativar todas. Ao testar o cadastro de grupos do Pessoas, ele não funcionava: não havia como criar grupo, o detalhe abria em branco e a exclusão apagava o grupo Administrador com todos os usuários dentro.

Causas no Pessoas/API:
- GET v1/grupo-usuario/{id}: a rota explícita para detalhes() era sobrescrita pelo apiResource registrado depois (mesmo método e URI), e o show() devolvia o model cru, sem data e sem Usuarios. A tela lia ret.data.data, recebia undefined e não desenhava nada. Agora o show() devolve o GrupoUsuarioResource e a rota/método detalhes saíram.
- GrupoUsuarioResource: Usuarios chaveado por codusuario saía como objeto JSON; a tela testava .length. values() resolve.
- tblgrupousuariousuario e tblgrupousuariopermissao têm FK ON DELETE CASCADE: excluir o grupo levava junto, sem aviso, os usuários e as permissões. O destroy() agora recusa com 409 quando o grupo tem usuários.
- Store do pessoas não tinha criar; a listagem só buscava quando vazia, então editar/excluir/ativar/inativar não apareciam nela.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 MGLara não cadastra mais usuário, grupo nem permissão: telas, rotas e itens de menu saem
- [x] #2 Pessoas cria grupo de usuário (botão Novo na listagem, mesmo dialog do editar)
- [x] #3 Detalhe do grupo abre e lista os usuários ativos com as filiais
- [x] #4 Listagem de grupos acompanha o editar, excluir, ativar e inativar
- [x] #5 Grupo com usuários não pode ser excluído
- [x] #6 Listagens de usuários e de grupos no padrão de card central, com a quantidade de usuários ativos do grupo e os grupos de cada usuário
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
MGLara (repo separado, commit próprio): saíram as rotas usuario/*, grupo-usuario/*, permissao e printers (esta apontava para um método que não existia), os itens Usuários/Grupos/Permissões do menu Admin e o Perfil do menu do usuário, os controllers UsuarioController/GrupoUsuarioController/PermissaoController e as views. Os links "por <usuário>" (rodapé de autor, negócio, histórico de preço) viraram texto. As permissões do MGLara continuam valendo (middleware permissao + tblgrupousuariopermissao); só não se editam mais por tela.

Pendência relatada, sem task: no MGLara, create/store/edit/update/destroy do EstoqueMesController e as views estoque-mes/create, edit e form são cópia da tela de Permissão. O editar já quebrava (Permissao sem use); o create agora dá view não encontrada. Nenhum menu ou link leva até eles.

Incidente no dev (21/10/2026): na validação o grupo Administrador foi excluído e a FK em cascata levou os usuários e as 321 ligações de permissão. Recriado no banco de dev com codgrupousuario 1, o fabio nas 20 filiais ativas e todas as permissões. Os outros membros não foram recuperados (sem auditoria dessas tabelas nem dump local); a produção não foi tocada.

O nome Administrador é lido literalmente (Mg\Usuario\Autorizador e temPermissao dos apps): renomear o grupo tira o acesso de administrador de todos os membros.

Testes (21/10/2026): API pelo kernel HTTP, numa transação desfeita no fim, 14 casos OK (detalhe com Usuarios em lista, listagem, criar 201, nome repetido 422, alterar, inativar, ativar, excluir com usuário 409 mantendo o vínculo, excluir grupo vazio, listagem de usuários). MGLara: route:list sem as rotas removidas e as 4 views alteradas compilam. Pessoas: build pwa OK num worktree. Telas conferidas pelo Fábio no dev.
<!-- SECTION:NOTES:END -->
