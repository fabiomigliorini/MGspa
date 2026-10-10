---
id: TASK-209
title: Gerente não vê o histórico do negócio nem o motivo da reabertura
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 19:57'
updated_date: '2026-10-10 21:06'
labels:
  - negocios
dependencies: []
priority: low
type: feature
ordinal: 221000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio em 10/10/2026. Reabrir uma venda fechada (TASK-30) não pede motivo, e no PDV não há onde ver a auditoria e as ocorrências do negócio.

Proposta: o Reabrir pede justificativa (gravada na tblauditoria, coluna justificativa que já existe) e um componente genérico em @components (como o MgInfoCriacao) recebe tabela + código, busca no backend ao clicar e mostra num dialog a auditoria e as ocorrências do registro. Para tblnegocio, o backend traz também a auditoria dos filhos (itens, pagamentos, parcelas, vales). Visível para todos os usuários do PDV (decisão do Fábio).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Reabrir negócio pede justificativa e grava na auditoria Negócio reaberto
- [x] #2 Componente genérico em @components recebe tabela + código e mostra num dialog a auditoria e as ocorrências do registro
- [x] #3 No negócio, o histórico traz também a auditoria dos itens, pagamentos, parcelas e vales
- [x] #4 Botão do histórico no PDV, visível para todos os usuários
- [x] #5 Tela da ocorrência no contas mostra todas as auditorias amarradas a ela
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (10/10/2026):
- Reabrir: POST v1/pdv/negocio/{cod}/reabrir exige justificativa (required, min:15, igual ao cancelar); PdvNegocioReaberturaService::reabrir($negocio, $justificativa) grava na auditoria 15 (Negócio reaberto). PDV: o Reabrir virou prompt 'Justificativa da Reabertura' (mín. 15 caracteres), como o do cancelamento.
- Histórico: GET v1/auditoria/{tabela}/{codigo} -> AuditoriaController@historico -> AuditoriaService::historico. Auditorias do registro + dos filhos vivos (AuditoriaService::FILHOS: tblnegocio -> itens, pagamentos, parcelas, vales; PK/FK pela convenção cod+tabela) + as amarradas às ocorrências do registro (pega o pagamento/parcela apagados, que não têm mais linha). Ocorrências: OcorrenciaService::historico (as do registro, as do negócio por codnegocio e as que amarram alguma das auditorias). Sem trava de gerente: todo usuário logado vê.
- AuditoriaService::linha formata a auditoria para o front; OcorrenciaService::auditoriasDe passou a usar ela (mesmo formato de antes).
- @components/MgHistorico.vue (props tabela, codigo): botão history que abre dialog com Auditoria (tipo, usuário, data, justificativa, campo: antes → depois) e Ocorrências (descrição, tipo, usuário, PDV, data, valor, conferida). No PDV fica no 1º item do painel da direita (DetalheNegocio), só com codnegocio.

Testado em dev: historico de 4549126 (reaberturas, pagamento/parcela cancelados e reativados, item excluído + ocorrência), 4549128 (pagamento apagado entra pela ocorrência; negócio cancelado), tabela inexistente devolve vazio; reabrir com justificativa grava trimado (transação desfeita); endpoint 200 para usuário não gerente; reabrir sem justificativa ou com menos de 15 → 422. Front: eslint/prettier ok e Vite compila MgHistorico/DetalheNegocio/IndexPage. Tela não exercitada no navegador.

Card da ocorrência num componente só (10/10/2026): @components/MgOcorrenciaItem.vue (prop ocorrencia, slot acoes embaixo do valor), usado na tela Ocorrências do Contas (botões abrir negócio/conferir/reabrir pelo slot) e no MgHistorico. O ícone e a cor por tipo saíram da página do Contas para @components/ocorrencia.js (VISUAL, visual()). O resumo compacto do DispositivoDetalhePage continua como está (decisão do Fábio).

Tela da ocorrência no contas (10/10/2026, AC #5): GET v1/ocorrencia/{id} -> OcorrenciaController@show (mesma trava de filial do conferir: ConferenciaAutorizador::autorizar) -> OcorrenciaService::carregar, que já devolvia as auditorias amarradas (tblocorrenciaauditoria). Página contas/src/pages/ocorrencia/Detalhe.vue (rota ocorrencia-detalhe, /ocorrencia/:codocorrencia): cabeçalho com voltar, #código e tipo, e as ações (abrir negócio, conferir com observação, reabrir); card da ocorrência (MgOcorrenciaItem) e card Auditoria com todas as amarradas, ou MgEmptyState para as de estado (esquecido, desconto acima, sem financeiro). Na lista /ocorrencia, clicar na linha abre a tela da ocorrência; conferir e reabrir continuam na linha. Linha da auditoria virou @components/MgAuditoriaItem.vue (tipo, usuário, data, justificativa, campo: antes → depois), usada aqui e no MgHistorico. Store: ocorrencia + carregar(); conferir/reabrir atualizam também a ocorrência aberta.

Testado em dev: GET 301 (3 auditorias, pagamentos apagados) 200; 260 (desconto acima) 200 sem auditoria; 999999 404; usuário sem grupo 403. Front: prettier/eslint ok; dev server do contas (8086) compila Detalhe, Index, store, routes, MgAuditoriaItem; MgHistorico compilado pelo compiler-sfc. Tela não exercitada no navegador.
<!-- SECTION:NOTES:END -->
