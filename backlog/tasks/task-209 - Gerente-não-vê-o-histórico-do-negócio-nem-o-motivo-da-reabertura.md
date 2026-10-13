---
id: TASK-209
title: Gerente não vê o histórico do negócio nem o motivo da reabertura
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 19:57'
updated_date: '2026-10-12 13:30'
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
- [x] #6 Fechar o negócio (F3, fechamento automático por pagamento e devolução) grava Negócio fechado no histórico
- [x] #7 No histórico, o quadro de Ocorrências fica em cima da Auditoria
- [x] #8 No histórico do negócio, a ocorrência não mostra o botão de abrir o negócio
- [x] #9 No histórico, ocorrências e auditoria da mais nova para a mais velha, e a ocorrência abre a tela dela no contas
- [x] #10 No histórico com muita coisa, o canto do dialog continua arredondado (rola o conteúdo, não o card)
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

Ajustes (10/10/2026, pedido do Fábio):
- Negócio fechado no histórico: auditoria tipo 20 (AuditoriaService::TIPO_NEGOCIO_FECHADO, status 1 -> 2) em todo fechamento: F3 do PDV (PdvNegocioService::fechar, inclusive o F3 da reaberta), NegocioService::fechar (fecharSePago de PIX/LIO/PagarMe/Saurus sem PDV) e a devolução, que já nasce fechada (antes nulo). Só vale para os fechamentos daqui para frente.
- MgHistorico: quadro de Ocorrências em cima da Auditoria.
- MgOcorrenciaItem ganhou a prop link-negocio (padrão true); o MgHistorico desliga quando a tabela é tblnegocio (já se está no negócio).
Testado: tipo 20 gravado e lido pelo histórico (transação desfeita). O F3 não foi rodado pelo tinker porque chama o MGLara (estoque) e dispara job do RH; testar pela tela. Front: prettier/eslint ok e Vite compila MgHistorico/MgOcorrenciaItem no negocios e no contas.

Ordem e link (10/10/2026): AuditoriaService::historico e OcorrenciaService::historico ordenam por criacao desc (empate pelo código desc). No MgHistorico a ocorrência é clickable com href para CONTAS_URL/ocorrencia/{cod} (tela Detalhe do contas), em outra aba; os atributos passam direto para o q-item do MgOcorrenciaItem, como no painel. A tela da ocorrência é só de Gerente/Financeiro/Admin: quem não é cai na recusa de permissão do contas.

Canto do dialog (10/10/2026, AC #10): com o histórico mais alto que a tela, quem rolava era o próprio card (.q-dialog__inner > div tem overflow auto) e a barra nativa do Chromium, retangular e fora do recorte do border-radius, deixava o canto superior direito quadrado. Agora rola a q-card-section do conteúdo (class scroll, max-height 60vh), como em CardDependentes/TransferirCaixaDialog; o título e o Fechar ficam fixos.

Página do dispositivo no PDV (12/10/2026, decisão do Fábio: muda a de 10/10): as Últimas ocorrências passam a usar o card compartilhado (@components/MgOcorrenciaItem), as mesmas do contas e do histórico, em vez do resumo compacto. O backend lista pelo OcorrenciaService::listar (o mesmo do painel), as 20 mais recentes do PDV. Testado na tela (Chrome headless, dispositivo 508): 20 ocorrências com ícone, conferência e link, sem erro de console; lint completo do negocios.
<!-- SECTION:NOTES:END -->
