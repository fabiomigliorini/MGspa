---
id: TASK-252
title: >-
  Sangria e reforço entre caixas são lançados no sentido contrário: a tela não
  mostra de onde sai e para onde vai o dinheiro
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-21 22:06'
updated_date: '2026-10-21 23:11'
labels:
  - contas
dependencies: []
priority: high
type: bug
ordinal: 262000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato de 21/10/2026: no Lançar do período do caixa, Sangria queria dizer 'sai deste caixa' e Reforço 'entra neste caixa', mas o pessoal usa a palavra pelo lado da gaveta (sangria = sai da gaveta para o central). Com a tela do Caixa Central aberta, quem quer registrar a sangria do Cx 01 para o Central clica em Sangria e o lançamento sai do Central para o Cx 01, no sentido contrário. Vira lançamento errado, cancelamento e refação (a TASK-246 nasceu de um cancelamento de sangria entre caixas). Prioridade High: lançamento errado toda semana, o workaround é cancelar e refazer.

Decidido com o Fábio (21/10/2026): os botões Sangria e Reforço (Enviar e Receber no banco) continuam, mas não presumem o lado; o wizard sempre pergunta de onde sai e para onde vai, e um dos lados é sempre o portador da tela. A lista de portadores (só os que o usuário tem papel: origem Operador, destino Depositante) fica agrupada por filial, com filtro só no desktop.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 O Lançar tem uma opção só, Transferência (caption Sangria / Reforço; no banco, Enviar / Receber), que sempre pergunta de onde sai o dinheiro; se sai de outro portador, o destino é o da tela; se sai do da tela, pergunta para onde vai
- [x] #2 Antes de lançar, a tela mostra Sai de … / Entra em … com os dois nomes
- [x] #3 A lista de portadores mostra o da tela primeiro, depois os desta filial e depois um grupo por filial
- [x] #4 No desktop a lista tem campo de filtro; no celular, não
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Só front (components/caixa/TransferirCaixaDialog.vue, periodoStore.transferir); o backend já recebia origem e destino, e o período da tela é sempre um dos lados. O texto da lista de lançamentos (PortadorPeriodoResource::textoTransferencia) já chamava de Sangria/Reforço pelo lado da gaveta e ficou. Decisão registrada no doc-4 (Transferência pergunta os dois lados). No Lançar, Sangria e Reforço (Enviar e Receber no banco) viraram uma opção só, a primeira; o store.abrirTransferir saiu. Cada opção do Lançar ganhou um caption do que lança (lista no doc-4).

Permissão: a lista vem do v1/select/portador com o papel do usuário em cada portador; origem só com Operador ou Gestor, destino com Depositante ou mais; sem papel não aparece (o Administrador é Gestor em todos). Só ativos, espécie e banco.

Roteiro de teste:
1. Contas, Caixa Central com período aberto: Lançar (cada opção com o caption embaixo) > Transferência > De onde sai o dinheiro? com o Central no topo (Este caixa), depois Desta filial e um grupo por filial > Caixa 01 Centro > vai direto ao valor com Sai de Caixa 01 Centro / Entra em Caixa Central > Lançar > entra (+) no Central.
2. Mesma tela: Transferência > o Central > Para onde vai o dinheiro? > Caixa 01 > valor > sai (-) do Central.
3. Voltar: do valor volta ao Para onde vai só se ele apareceu; senão ao De onde sai.
4. Desktop: o filtro em cima, digitar parte do nome ou da filial filtra; setas e Enter escolhem. Celular e PDV: sem o filtro, só a lista agrupada.
5. Banco: Lançar > Transferência, no mesmo wizard.
6. PDV (negocios /caixa): Lançar com Transferência e Vendas de Parceiro; sangria da gaveta para o cofre continua a confirmar pelo gestor.

Ajustes da validação com o Fábio (21/10): no Lançar, Transferência virou uma opção só e cada opção ganhou um caption (Transferência: Sangria / Reforço; Entrada de item: Novos chips / Ingressos físicos; Saída de item: Recolher chips / Ingressos físicos; Maquinetas de Parceiros, que era Vendas de Parceiro: Redeflex / Bilhete Agora / Black Ticket; Ajuste: Lançar movimento manual). No dialog, o filtro fica no cabeçalho fixo (só a lista rola) e o cabeçalho diz o passo em vermelho: SAINDO DE, RECEBENDO EM; no último passo, TRANSFERÊNCIA.

Testes: prettier; compilação dos SFCs pelo compiler-sfc e pelo Vite dos servidores de dev do contas e do negocios, sem erro. Na revisão, corrigidos o filtro que voltava preenchido ao reabrir o dialog e os portadores sem filial que vinham antes das outras filiais (agora no fim). Backend sem mudança: as duas combinações que o dialog manda (sai deste para outro; sai de outro para este, com o período da tela no destino) são as mesmas da Sangria e do Reforço de antes.
<!-- SECTION:NOTES:END -->
