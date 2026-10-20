---
id: TASK-244
title: >-
  Leitor de código de barras embutido no aparelho Android não faz nada na
  consulta de preço (/quiosque)
status: To Do
assignee: []
created_date: '2026-10-20 16:54'
updated_date: '2026-10-20 16:54'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 254000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato (20/10/2026): o aparelho Android da consulta de preço (/quiosque) tem leitor de código de barras nativo, embutido no próprio aparelho. Ao ler um código nessa tela nada acontece: o produto não é consultado, e o cliente fica olhando a tela de espera. O leitor embutido é o jeito de usar o aparelho, então sem ele a consulta de preço só funciona pela pesquisa (F1) digitada.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 No aparelho Android com leitor embutido, ler um código com a tela de espera aberta consulta o produto, sem tocar na tela antes
- [ ] #2 Depois de uma consulta, de fechar a pesquisa (F1) ou de tocar em qualquer parte da tela, a próxima leitura continua consultando o produto
- [ ] #3 O teclado virtual do Android não abre sozinho nem cobre a tela do quiosque
- [ ] #4 O leitor USB ou o teclado, que funcionam hoje, continuam funcionando e a mesma leitura não consulta duas vezes
- [ ] #5 A digitação na pesquisa (F1) continua indo para o campo da pesquisa, e não para a consulta
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Análise (20/10/2026): só leitura do código, ainda sem teste no aparelho.

- negocios/src/pages/QuiosquePage.vue:116-209 lê o código por um `keydown` global no document, "sem input": junta as teclas de 1 caractere (`e.key.length === 1`) e consulta no Enter. Isso só recebe leitor que se comporta como teclado físico (USB ou Bluetooth).
- O leitor embutido do Android costuma entregar o código como texto digitado no campo em foco (pelo teclado virtual, IME) ou como broadcast de intent. A tela do quiosque não tem nenhum campo editável: sem campo em foco o texto não tem para onde ir e o navegador não dispara o `keydown`. Pelo IME, quando dispara, `e.key` costuma vir "Unidentified" (keyCode 229), que o filtro `e.key.length === 1` descarta.
- Causa provável, a confirmar no aparelho: modelo do aparelho e modo do leitor (digita no campo em foco, ou manda broadcast?), e se o leitor tem opção de emular teclado e de terminar a leitura com Enter. Se for só broadcast, a página não recebe e a saída é outra (configurar o leitor para digitar).

Direção: campo de texto real na tela, invisível e sempre em foco, com `inputmode="none"` para o teclado virtual não abrir (testar no aparelho se o leitor ainda digita com ele); devolver o foco ao campo no blur, depois de cada consulta e ao fechar a pesquisa F1; ler o valor do campo no Enter ou change, não tecla a tecla.
Cuidados: o onKeydown (linhas 163-167) faz `blur()` no elemento ativo a cada tecla para o Enter do leitor não virar clique em botão, o que tiraria o foco do campo novo (poupar o campo, só botões); manter o caminho por keydown e o paste para leitor USB sem ler duas vezes; com a pesquisa F1 aberta o campo dela cuida da digitação (linha 123).
Mesma causa da task do PDV (campo Barras sem foco); telas e testes são separados.

A task irmã do PDV é a TASK-245.
<!-- SECTION:NOTES:END -->
