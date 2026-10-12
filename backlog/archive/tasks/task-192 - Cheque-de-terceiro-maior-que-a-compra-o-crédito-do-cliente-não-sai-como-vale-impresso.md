---
id: TASK-192
title: >-
  Cheque de terceiro maior que a compra: o crédito do cliente não sai como vale
  impresso
status: To Do
assignee: []
created_date: '2026-10-02 15:10'
updated_date: '2026-10-02 15:15'
labels:
  - negocios
  - contas
dependencies: []
priority: high
type: feature
ordinal: 205000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
## Situação

Cliente chega com cheque de terceiro de valor maior que a compra. Hoje não há troco simples: a ideia é o caixa (PDV) ou o financeiro (contas) lançar o cheque como **adiantamento de cliente** — o lançamento do M8 da TASK-188 (`MgAdiantamentoDialog` / `TituloAdiantamentoService`, que já gera o `tblcheque` quando a forma é cheque). Isso cria um título de crédito do cliente com saldo.

O que falta é entregar ao cliente o **vale impresso na térmica, com o código de barras `VAL{codtitulo}`**, para ele usar o saldo depois (ou na mesma hora, na própria venda).

## Por que hoje não dá

A impressão do vale existe, mas é **do negócio**, não do título:

- `api/app/Mg/Pdv/ValeService.php` (`pdf`/`comprovantes`/`imprimir`) recebe um `Negocio` e descobre os títulos pelo negócio (vales vendidos em `tblnegociovale`, saldo de vale resgatado e crédito de devolução);
- rotas `GET v1/pdv/negocio/{codnegocio}/vale` (PDF, signed) e `POST v1/pdv/negocio/{codnegocio}/vale/{impressora}` (manda pro Ably/`printing`);
- view `resources/views/negocio/vale.blade.php`; disparo automático em `ImprimirValesNegocioJob` (fechamento e devolução);
- no front só existe dentro do negócio (`negocios/.../ListagemItensVale.vue`, card do Contra Vale).

Um adiantamento lançado em Pagamentos não tem negócio, então não tem como imprimir.

E mesmo impresso, o vale **não seria aceito** na bipagem: `PdvController::buscarVale` (`GET v1/pdv/vale/{codtitulo}`) recusa tudo que não seja `codtipotitulo = 3` (Vale Compras); o card de Contra Vale (`PdvNegocioPagamentoService`, linha ~266) e o comprovante de saldo (`ValeService::comprovantes`) também filtram só tipo 3. O adiantamento nasce como 220 (Adto Cliente) ou 230 (Crédito Cliente).

## Como (proposta — a fechar antes de implementar)

**Backend — impressão vira do título**
1. Mover o núcleo para o domínio do título (ex.: `Mg\Titulo\TituloValeService`): `pdf(array $codtitulos)` e `imprimir(array $codtitulos, $impressora)`. A view passa a receber títulos; os dados do kit (escola/aluno/turma/lista) continuam saindo quando existe `tblnegociovale` com aquele `codtitulo`.
2. Rotas novas por título: `GET v1/titulo/{codtitulo}/vale` (PDF 80mm, signed para a impressora buscar) e `POST v1/titulo/{codtitulo}/vale/{impressora}`.
3. O caminho do negócio (`ValeService::comprovantes` + `ImprimirValesNegocioJob` + rotas `pdv/negocio/.../vale`) fica só decidindo **quais** títulos do negócio imprimir e delega ao serviço do título — sem mudar o que sai hoje na venda/devolução.
4. Quais títulos podem virar vale: 3 Vale Compras e 220 Adto Cliente, com saldo em aberto. Os demais recusam com mensagem.

**Bipagem/uso do crédito no PDV**
5. **Ressalva — não é desta task:** a bipagem do 220 no PDV (`buscarVale`, card de Contra Vale, comprovante de saldo) está sendo corrigida em outro trabalho. Antes de implementar, conferir que já está no master; esta task não mexe nisso.

**Frontend — imprimir de qualquer lugar que tenha o título**
6. Componente compartilhado em `@components` (no molde do `MgNotaFiscalAcoes`): recebe `codtitulo` e `impressora`, abre o PDF no `abrirPdf` (tamanho cupom) com botão de mandar pra térmica.
7. Plugar em: listagem/detalhe de Pagamentos (contas e negócios), tela do título no contas e cards de vale/contra vale do negócio (trocando a chamada atual).
9. Comprovante na hora: todo Adto Cliente lançado pelo `MgAdiantamentoDialog` sai com o vale assim que é gravado — no PDV direto na térmica do caixa; no contas abre o PDF do vale com o botão de mandar pra térmica escolhida (lá não há impressora fixa).
8. Impressora: no PDV vem do `padrao.impressora`; no contas, escolher com `MgSelectImpressora`.

## Decisões (02/10/2026)

- **Escopo é só a impressão.** O lançamento do adiantamento (`MgAdiantamentoDialog` / `TituloAdiantamentoService`) fica exatamente como está: mesmos campos, tipos, formas e validações. A única coisa que muda nele é disparar o comprovante depois de gravar.

- Cheque de terceiro entra como **220 Adto Cliente**.
- O vale fica **em nome do cliente selecionado** no lançamento, como já é hoje.
- **Todo adiantamento de cliente sai com comprovante na hora** (item 9), além do botão para reimprimir.

Depende do M8 da TASK-188 (lançamento de adiantamento), ainda na árvore de trabalho.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Decidir o tipo do título do adiantamento, ao portador x identificado e impressão automática x botão
- [ ] #2 Vale imprime a partir do título (PDF e térmica), sem depender de negócio
- [ ] #3 Venda e devolução continuam imprimindo os mesmos vales de hoje, agora pelo caminho do título
- [ ] #4 Antes de implementar: conferir que a bipagem do Adto Cliente (220) no PDV já foi corrigida no outro trabalho (não é escopo desta task)
- [ ] #5 Botão de imprimir vale em Pagamentos (contas e negócios) e na tela do título
- [ ] #6 No contas a impressora é escolhida na hora; no PDV usa a térmica do caixa
- [ ] #7 Todo adiantamento de cliente sai com o vale na hora: térmica no PDV, PDF no contas
<!-- AC:END -->
