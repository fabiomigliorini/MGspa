---
id: TASK-273
title: Leitor do PDA abre a tela de pagamento ao ler o segundo produto no PDV
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-10 19:13'
updated_date: '2026-11-11 13:47'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 283000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato (10/11/2026): no PDV, o leitor embutido do PDA lê o primeiro produto normalmente; no segundo, em vez de adicionar o item, abre a tela de pagamento (Receber), como se tivesse apertado F8. Na primeira leitura aparece o aviso vermelho 'Não há nada a receber!' (confirmado por quem relatou).

Apareceu depois da TASK-244 (21/10/2026), que fez o leitor embutido do Android funcionar no PDV: antes a leitura nem chegava à venda.

Causa: o botão de leitura do PDA manda uma tecla F (entre F6 e F9) ao ser apertado, antes dos dígitos. O atalho global do PDV (hotkeys em negocios/src/pages/IndexPage.vue:47-91, keydown no document) trata F6, F7, F8 e F9 como Receber sem olhar de onde a tecla veio. Com a venda zerada, abrirReceber (negocios/src/stores/negocio.js:2040-2049) só recusa com 'Não há nada a receber!'; da segunda leitura em diante a venda tem valor e o pagamento abre. O quiosque só trata F1 e não é afetado.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 No PDA, ler um produto depois do outro adiciona todos e não abre o pagamento
- [ ] #2 No caixa com teclado, F6/F7/F8/F9 continuam abrindo o Receber
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Próximo passo (10/11/2026): medir no PDA o que o botão de leitura manda. Chrome do PDA -> https://www.toptal.com/developers/keycode -> apertar o botão SEM código na frente -> anotar event.key, event.code, event.which. Referência, F8 de teclado: key F8, code F8, which 119.
Se o code do botão for diferente do key: guarda no começo do hotkeys (atalho só vale tecla de teclado, code === key). Se for igual ao de um teclado: não dá para separar pela tecla; decidir entre aparelho só de toque ignorar F6-F9 ou tirar o alias daquela tecla.

Medição no PDA (10/11/2026), igual em várias leituras: event.key = F8, event.code = F12 (a página mostrou keyCode 13). O F8 de um teclado de verdade tem code F8: o botão do PDA é a única fonte de key diferente do code.
Correção: negocios/src/pages/IndexPage.vue, guarda no começo do hotkeys (if (event.code !== event.key) return). Atalho só vale tecla de teclado; F1-F4 e F6-F10 do caixa ficam iguais.
<!-- SECTION:NOTES:END -->
