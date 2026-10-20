---
id: TASK-245
title: >-
  No PDV, o leitor de código de barras embutido no aparelho só funciona com o
  cursor no campo Barras
status: To Do
assignee: []
created_date: '2026-10-20 16:54'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 255000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato (20/10/2026): no PDV, quando o cursor não está no campo Barras (depois de tocar num item, fechar uma janela, tocar numa área vazia da tela), ler um código com o leitor de código de barras embutido no aparelho Android não faz nada: o leitor lê, mas o código não tem onde ser escrito. Quem opera precisa tocar no campo Barras antes de cada leitura e, quando esquece, o código lido se perde sem nenhum aviso.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Com a venda aberta, ler um código com o leitor embutido do aparelho adiciona o item sem tocar no campo Barras antes
- [ ] #2 Depois de tocar num item, de fechar uma janela (Receber, pesquisa F1, observações) ou de tocar numa área vazia, a próxima leitura entra na venda
- [ ] #3 Quem está digitando em outro campo, ou com uma janela aberta, não perde a digitação: o cursor só volta ao campo Barras quando nada mais está sendo preenchido
- [ ] #4 Venda fechada, cancelada ou de outro PDV (sem o campo Barras na tela) não puxa o cursor, e a leitura não faz nada
- [ ] #5 O teclado virtual do Android não fica cobrindo a venda a cada leitura quando o cursor volta sozinho ao campo
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Análise (20/10/2026): só leitura do código, ainda sem teste no aparelho.

- negocios/src/pages/IndexPage.vue:45-69 (`hotkeys`): ao apertar um dígito, V ou N sem campo em foco, joga o foco no #inputBarras. Depende de `keydown`, ou seja, do leitor se comportar como teclado físico. O leitor embutido do Android costuma entregar o texto pelo teclado virtual (IME) ou por broadcast: sem campo em foco não há `keydown`, e esse gancho nunca roda.
- negocios/src/components/offline/InputBarras.vue:426: o campo só tem `autofocus`, que vale na montagem da tela. Não existe código que devolva o foco ao campo depois de fechar uma janela, tocar num item ou botão, ou o foco cair no body. Tocar fora do campo basta para o leitor perder o destino.
- A leitura em si funciona: o `@change` do campo (Enter do leitor, ou saída do campo) chama buscarBarras. Falta só o campo estar em foco.
- Causa provável, a confirmar no aparelho: mesma da task do quiosque, o leitor digita no campo em foco (e termina com Enter) ou manda broadcast. Confirmar o modelo e o modo do leitor junto.

Direção: devolver o foco ao campo Barras sempre que o foco cair no body e não houver outro campo ou janela aberta (blur do campo, fechamento de dialog, depois de cada leitura). O campo só existe com `sNegocio.podeEditar` (IndexPage.vue:533), o que já cobre a venda não editável.
Atenção: o campo tem `inputmode="tel"`, e devolver o foco com isso pode abrir o teclado virtual do Android a cada leitura. Decidir com quem opera se o aparelho com leitor usa `inputmode="none"` (sem teclado, só leitor) ou mantém o numérico para digitar quantidade (2*).
Mesma causa da TASK-244 (quiosque); aproveitar a solução, mas telas e testes são separados.
<!-- SECTION:NOTES:END -->
