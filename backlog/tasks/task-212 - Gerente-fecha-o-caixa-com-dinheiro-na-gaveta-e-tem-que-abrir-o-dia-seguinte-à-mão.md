---
id: TASK-212
title: >-
  Gerente fecha o caixa com dinheiro na gaveta e tem que abrir o dia seguinte à
  mão
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-10 20:41'
updated_date: '2026-10-21 16:00'
labels:
  - contas
dependencies: []
priority: medium
type: feature
ordinal: 224000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A pedido do Fábio (10/10/2026): ao fechar o período em espécie que tem saldo para carregar (cédulas, moedas e itens do caixa como chips), o servidor já abre o período seguinte e a tela do portador atualiza as abas sem recarregar. Automação de evento autorizada por quem prioriza (CLAUDE.md, 'automação de evento só a pedido'); precedente: conferir a maquineta encerra o aberto e abre o seguinte (doc-4).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Fechar o último período com contagem final maior que zero abre o seguinte (início no segundo seguinte ao fim, contagem inicial copiada da final)
- [x] #2 Fechamento que fica pendente (diferença acima da tolerância) também abre o seguinte: o dinheiro continua na gaveta
- [x] #3 Contagem final zerada não abre o seguinte
- [x] #4 A resposta do fechar já traz o período novo e a tela mostra a aba nova e avisa, continuando no período fechado
- [x] #5 Com período anterior ainda pendente, fechar o seguinte não é recusado: ele grava o fim e a contagem, fica pendente e abre o próximo; fecha com o mesmo Fechar depois que o anterior fechar
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Backend: PortadorPeriodoService::fecharCaixa, no fim: se o período é o último (sem seguinte) e saldoInicialSeguinte (contagem final: cédulas, moedas e itens) > 0, chama abrir() com inicioDoNovo — início no segundo seguinte ao fim (ou no próprio fim, se fechou neste segundo) e contagem inicial copiada da final. Vale também quando fica pendente. Não muda rota nem resource: a resposta do fechar já devolve em 'periodos' o fechado e os seguintes (desde), o novo vem junto e o aplicar() do periodoStore o põe nas abas; o botão abrir some porque há um aberto.

Front: periodoStore.fechar() guarda os períodos de antes e, se veio um novo, completa o aviso com '; o seguinte já está aberto com R$ X'. A tela continua no fechado.

Efeito: reabrir o fechado depois que o seguinte nasceu deixa ele pendente (não é mais o último), não aberto; para voltar a vender nele, unificar o novo (vazio) com ele.

Conferido (tinker, transação com rollback, Gaveta Dev Fabio #47, saldo 303,90): contando 303,90 fecha e nasce #119 aberto com 303,90 e a contagem inicial igual à final; contando 253,90 fica pendente (dif -50) e nasce o seguinte com 253,90; contagem zerada fica pendente e não abre nada. Tela conferida (AC 4).

Testar: contas → /portador/{gaveta}/{período aberto} → contar o final pelo resumo → Fechar (cadeado). Deve aparecer a aba nova 'aberto' com o saldo e o aviso 'Período fechado; o seguinte já está aberto com R$ X', sem recarregar.

Tela aprovada pelo Fábio (10/10/2026).

21/10/2026 — furo achado em prod: caixa com dois períodos pendentes não deixava fechar o segundo para abrir o novo. fecharCaixa recusava inteiro ('Feche antes o período de …') com anterior por fechar. Agora, com anterior sem fechamento, segue o fluxo (grava fim, saldo final, diferença) mas não grava fechamento: fica pendente, e o TASK-212 abre o seguinte. O Fechar de novo, depois que o anterior fechar, fecha este (fim mantido). Transferência a confirmar continua recusando. Front: periodoStore.fechar() avisa 'Há período anterior pendente: este ficou pendente e fecha depois dele' quando a diferença está dentro da tolerância. doc-4 atualizado.
<!-- SECTION:NOTES:END -->
