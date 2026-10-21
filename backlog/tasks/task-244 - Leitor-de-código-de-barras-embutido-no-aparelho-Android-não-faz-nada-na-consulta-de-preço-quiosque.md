---
id: TASK-244
title: >-
  Leitor de código de barras embutido no aparelho Android não faz nada na
  consulta de preço (/quiosque), e no PDV só com o cursor no campo Barras
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-20 16:54'
updated_date: '2026-10-21 18:48'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 254000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato (20/10/2026): os aparelhos Android do negócio têm leitor de código de barras nativo, embutido no próprio aparelho, e o leitor só funciona quando há um campo de texto com o cursor.

- Consulta de preço (/quiosque): ao ler um código nada acontece: o produto não é consultado, e o cliente fica olhando a tela de espera. O leitor embutido é o jeito de usar o aparelho, então sem ele a consulta de preço só funciona pela pesquisa (F1) digitada.
- PDV: quando o cursor não está no campo Barras (depois de tocar num item, fechar uma janela, tocar numa área vazia da tela), ler um código não faz nada: o leitor lê, mas o código não tem onde ser escrito. Quem opera precisa tocar no campo Barras antes de cada leitura e, quando esquece, o código lido se perde sem nenhum aviso.

Juntada em 21/10/2026 com a antiga TASK-245 (PDV), que era o mesmo problema na outra tela.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Quiosque: no aparelho Android com leitor embutido, ler um código com a tela de espera aberta consulta o produto, sem tocar na tela antes
- [x] #2 Quiosque: depois de uma consulta, de fechar a pesquisa (F1) ou de tocar em qualquer parte da tela, a próxima leitura continua consultando o produto
- [x] #3 Quiosque: o teclado virtual do Android não abre sozinho nem cobre a tela do quiosque
- [x] #4 Quiosque: o leitor USB ou o teclado, que funcionam hoje, continuam funcionando e a mesma leitura não consulta duas vezes
- [x] #5 Quiosque: a digitação na pesquisa (F1) continua indo para o campo da pesquisa, e não para a consulta
- [x] #6 PDV: com a venda aberta, ler um código com o leitor embutido do aparelho adiciona o item sem tocar no campo Barras antes
- [x] #7 PDV: depois de tocar num item, de fechar uma janela (Receber, pesquisa F1, observações) ou de tocar numa área vazia, a próxima leitura entra na venda
- [x] #8 PDV: quem está digitando em outro campo, ou com uma janela aberta, não perde a digitação: o cursor só volta ao campo Barras quando nada mais está sendo preenchido
- [x] #9 PDV: venda fechada, cancelada ou de outro PDV (sem o campo Barras na tela) não puxa o cursor, e a leitura não faz nada
- [x] #10 PDV: o teclado virtual do Android não fica cobrindo a venda a cada leitura quando o cursor volta sozinho ao campo
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Análise (20/10/2026): só leitura do código, ainda sem teste no aparelho.

Causa comum, a confirmar no aparelho: o leitor embutido do Android costuma entregar o código como texto digitado no campo em foco (pelo teclado virtual, IME) ou como broadcast de intent. Sem campo em foco o texto não tem para onde ir e o navegador não dispara o `keydown`. Pelo IME, quando dispara, `e.key` costuma vir "Unidentified" (keyCode 229). Confirmar o modelo do aparelho e o modo do leitor (digita no campo em foco, ou manda broadcast?), e se o leitor tem opção de emular teclado e de terminar a leitura com Enter. Se for só broadcast, a página não recebe e a saída é outra (configurar o leitor para digitar).

### Quiosque

- negocios/src/pages/QuiosquePage.vue:116-209 lê o código por um `keydown` global no document, "sem input": junta as teclas de 1 caractere (`e.key.length === 1`) e consulta no Enter. Isso só recebe leitor que se comporta como teclado físico (USB ou Bluetooth). A tela não tem nenhum campo editável, e o filtro `e.key.length === 1` descarta o "Unidentified" do IME.

Direção: campo de texto real na tela, invisível e sempre em foco, com `inputmode="none"` para o teclado virtual não abrir (testar no aparelho se o leitor ainda digita com ele); devolver o foco ao campo no blur, depois de cada consulta e ao fechar a pesquisa F1; ler o valor do campo no Enter ou change, não tecla a tecla.
Cuidados: o onKeydown (linhas 163-167) faz `blur()` no elemento ativo a cada tecla para o Enter do leitor não virar clique em botão, o que tiraria o foco do campo novo (poupar o campo, só botões); manter o caminho por keydown e o paste para leitor USB sem ler duas vezes; com a pesquisa F1 aberta o campo dela cuida da digitação (linha 123).

### PDV

- negocios/src/pages/IndexPage.vue:45-69 (`hotkeys`): ao apertar um dígito, V ou N sem campo em foco, joga o foco no #inputBarras. Depende de `keydown`, ou seja, do leitor se comportar como teclado físico: sem campo em foco não há `keydown`, e esse gancho nunca roda.
- negocios/src/components/offline/InputBarras.vue:426: o campo só tem `autofocus`, que vale na montagem da tela. Não existe código que devolva o foco ao campo depois de fechar uma janela, tocar num item ou botão, ou o foco cair no body. Tocar fora do campo basta para o leitor perder o destino.
- A leitura em si funciona: o `@change` do campo (Enter do leitor, ou saída do campo) chama buscarBarras. Falta só o campo estar em foco.

Direção: devolver o foco ao campo Barras sempre que o foco cair no body e não houver outro campo ou janela aberta (blur do campo, fechamento de dialog, depois de cada leitura). O campo só existe com `sNegocio.podeEditar` (IndexPage.vue:533), o que já cobre a venda não editável.
Atenção: o campo tem `inputmode="tel"`, e devolver o foco com isso pode abrir o teclado virtual do Android a cada leitura. Decidir com quem opera se o aparelho com leitor usa `inputmode="none"` (sem teclado, só leitor) ou mantém o numérico para digitar quantidade (2*).

Direção (21/10/2026): uma regra só, 'o cursor descansa no campo do leitor' (negocios/src/composables/useCursorNoLeitor.js). Depois de um clique ou troca de foco, se o cursor ficou no vazio ou num botão, sem janela (.q-dialog/.q-menu) e sem outro campo em foco, volta ao campo do leitor (focus com preventScroll; não age com texto selecionado). Ao fechar janela o Quasar já devolve o cursor ao campo (refocusTarget do QDialog).
- Quiosque: <input> nativo invisível (inputmode=none) recebe a leitura no change; saiu o acumulador de teclas do keydown (USB e embutido pelo mesmo caminho); F1 e paste ficam.
- PDV: InputBarras usa a regra; inputmode 'none' quando o cursor volta sozinho, 'tel' ao tocar no campo (pointerdown), 'none' de novo no blur. Saiu o atalho 0-9/V/N do hotkeys do IndexPage.
Primeiro teste no aparelho: o leitor embutido ainda escreve com inputmode=none?
<!-- SECTION:NOTES:END -->
