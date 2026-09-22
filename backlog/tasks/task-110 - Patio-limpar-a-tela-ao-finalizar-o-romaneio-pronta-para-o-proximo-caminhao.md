---
id: TASK-110
title: 'Patio: limpar a tela ao finalizar o romaneio, pronta para o proximo caminhao'
status: Done
assignee: []
created_date: '2026-09-17 19:21'
updated_date: '2026-09-22 13:54'
labels:
  - agro
dependencies: []
priority: medium
type: feature
ordinal: 109000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido de quem opera a balanca (17/09/2026): ao fechar uma carga, o centro da tela deve voltar em branco, pronto pro proximo caminhao — sem precisar apertar F2.

**Decisoes tomadas com o solicitante:**
- Limpa SO ao finalizar (etapa FINALIZADO). Nas etapas do meio (bruto, classificacao, tara) o caminhao ainda volta a balanca, entao a carga continua aberta no centro pra receber o proximo peso.
- Depois de limpar aparece uma CARGA NOVA em branco, com o cursor na placa (mesmo efeito do F2), nao a tela vazia.

**Implementacao (CargaPage.vue):** `limparParaProxima()` troca a rota pra `carga/nova` depois de gravar, quando a carga salva esta finalizada; `:key="cargaSel.uuid"` no CargaForm remonta o formulario, que e o que faz o autofocus da placa valer de novo.

**Impresso:** a carga finalizada sai do centro, entao o botao de imprimir sai junto. O aviso de "Carga finalizada" ganhou acao "Imprimir" (reabre a carga) e a legenda diz que ela esta em "Finalizadas", a esquerda.

**Como testar:** sistema-dev:8088/carga (Ctrl+Shift+R antes). Fechar um romaneio -> tela em branco com cursor na placa, carga na lista "Finalizadas", aviso com o liquido e botao Imprimir. Salvar nas etapas do meio -> a carga CONTINUA aberta.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Ajuste pedido em 17/09/2026, depois do primeiro teste: o clique da ultima etapa (Pesar tara, no recebimento) NAO grava mais direto.

Fluxo do fechamento passou a ter dois cliques:
1. "Pesar tara" -> valida tudo e PARA: mostra a operacao fechada (bruto/desconto/liquido/sacas ja estavam na tela) com um aviso verde embaixo dos numeros. O botao vira "Salvar", verde. Nada foi gravado ainda.
2. "Salvar" -> finaliza o romaneio, grava, avisa o liquido e limpa a tela pro proximo caminhao.

Vale pra qualquer sentido: a pausa acontece sempre que a proxima etapa e FINALIZADO (recebimento e transferencia param na tara, expedicao para na nota fiscal).

Mexeu em peso ou leitura durante a conferencia -> o botao volta a ser "Pesar tara": o numero conferido nao vale mais. A assinatura observada e string (getter de array dispararia com os mesmos numeros e derrubaria a revisao sozinho).

Arquivos: CargaForm.vue (`revisando`, `avancar`, `rotuloPrincipal`/`iconePrincipal`/`corPrincipal`, banner na Pesagem).
<!-- SECTION:NOTES:END -->
