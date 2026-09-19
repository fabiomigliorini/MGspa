---
id: TASK-108
title: 'Patio de Cargas: resumo cortando texto e botao principal sem retorno'
status: In Progress
assignee: []
created_date: '2026-09-17 19:06'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 107000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Dois defeitos achados no teste da tela nova (TASK-101), em 17/09/2026.

**1. Drawer direito cortava letras.** O `q-scroll-area` do drawer DIREITO nao recebeu o `content-style` que o esquerdo ja tinha, entao o conteudo crescia ate a largura natural e o que passava dos 300px era cortado, sem barra pra rolar: o liquido saia "44.84", o "kg" sumia, "747,4 sacas de 60" e o chip "Queb 1,0%" ficavam pela metade. Os rotulos da barra de etapas tambem nao cabiam em ~60px por segmento.

**2. O botao principal nao dava retorno nenhum.** Todos os caminhos de saida eram mudos:
- sucesso: gravar/avancar/finalizar nao emitia aviso, entao finalizar parecia nao ter funcionado;
- `avancar()` tinha `if (!prox) return` sem aviso — etapa fora do fluxo do sentido travava o botao pra sempre;
- `q-form` barra o submit em silencio quando uma `:rules` falha, e o campo vermelho pode estar fora da tela (classificacao tem ate 5 campos);
- erro ao gravar no Dexie virava promessa rejeitada sem tratamento.

**Correcao:** `scrollAreaFit` compartilhado nos dois drawers (MainLayout), rotulo `curto` por etapa com tooltip do nome inteiro, aviso no `@validation-error` nomeando o campo, aviso no `!prox` e try/catch no persistir, mais confirmacao de sucesso ("Carga finalizada — N kg liquidos").

**Como testar:** sistema-dev:8088/carga (Ctrl+Shift+R antes). O resumo da direita nao pode cortar nada; o botao azul tem que responder SEMPRE, com mensagem de erro ou de sucesso.
<!-- SECTION:DESCRIPTION:END -->
