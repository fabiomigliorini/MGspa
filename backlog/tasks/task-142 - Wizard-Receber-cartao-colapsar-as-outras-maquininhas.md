---
id: TASK-142
title: 'Wizard Receber cartao: colapsar as outras maquininhas'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-22 20:09'
updated_date: '2026-09-22 20:33'
labels:
  - negocios
dependencies: []
priority: medium
type: enhancement
ordinal: 152000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Operadores se confundem com a lista cheia de maquininhas da filial na etapa Cartao do wizard Receber. Passa a mostrar so Manual (0) e a maquininha padrao do PDV (1), com uma linha 'Selecione outra maquininha' que abre a sub-etapa 'outras' com a lista completa (2,3,4...). Sub-etapa em vez de expandir in-place porque o ListaOpcoes guarda o indice: expandindo no lugar, o cursor de quem chega no pivo por seta+Enter cai na primeira 'outra' maquininha e um segundo Enter cobraria na maquininha errada.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado em negocios/src/components/offline/receber/FormaCartao.vue (unico arquivo; ListaOpcoes.vue nao foi tocado).

Etapa 'modo' passa a mostrar so: 0 Manual, 1 maquininha padrao do PDV (pre-selecionada) e a linha 'Selecione outra maquininha' (avatar cinza expand_more, sem atalho numerico). Escolher essa linha vai para a nova sub-etapa 'outras', que lista as maquininhas mantendo a numeracao (1 = padrao, 2, 3, 4...).

Ajuste (22/09, apos validacao): a etapa 'outras' nao repete a opcao Manual - ela ja foi oferecida na etapa anterior e ficava redundante. montarModo ganhou o parametro comManual (default true). A numeracao nao muda, porque Manual tem tecla fixa 0 e as maquininhas sempre contam de 1.

DEDUPLICACAO (achado na 1a validacao, 22/09): SaurusPosS vem do PdvService::estoqueLocal com uma linha POR PINPAD, e um PDV com varios aparelhos repetia na lista - mesmo apelido, mesmo 'valor' (saurus-{codsauruspdv}), teclas diferentes apontando para a mesma maquininha, e key duplicada no v-for do ListaOpcoes. No Botanico eram 14 linhas para 8 maquininhas reais (BOT Alfa 3x, BOT Bravo 3x, BOT Foxtrot 3x); no Centro, 11 para 9. Quem recebe a cobranca e o PDV, entao maquinetasEnvio agora deduplica por 'valor'. Isso era boa parte da confusao relatada. A lista do cartao MANUAL nao pode ser deduplicada do mesmo jeito (la o que importa e o serial de cada pinpad) - virou a TASK-143.

Por que sub-etapa e nao expandir a lista no lugar: o ListaOpcoes guarda o indice selecionado e so o recalcula enquanto 'interagiu' for falso. Expandindo in-place, quem chega na linha pela seta e da Enter fica com indice=2, que apos a expansao vira a PRIMEIRA 'outra' maquininha - um segundo Enter (tecla presa/duplo toque de caixa) dispararia criarSaurusPedido/criarPagarMePedido na maquininha errada, acendendo a de outro caixa. Clique e teclado tambem divergiriam, porque @click nao marca 'interagiu'. Com sub-etapa o compilador do Vue da keys distintas a cada branch do v-if (modo=2, outras=3), forcando unmount+mount: instancia nova, selecao recomeca na padrao. De brinde o Esc colapsa pelo historico, sem ganhar um segundo significado.

Detalhes: :inicial=valorPadrao tambem na etapa 'outras' (Enter acidental recai na padrao, nunca numa arbitraria); colapsa havendo padrao do PDV e mais de 1 maquininha; o pivo fica desabilitado com o mesmo motivo quando o negocio nao esta sincronizado; sem padrao configurado ou negocio de outro estoque local, lista cheia como antes. O sort de ordenacao virou find+filter (o comparador antigo nao definia ordem total, so funcionava pela estabilidade do sort do V8). Texto usa 'maquininha', palavra que o resto da tela usa.

ATENCAO AO TESTAR: o Deposito (101001) tem UMA maquininha so cadastrada (DEP Alfa, 0 Stone), entao la nao aparece nem pode aparecer o link - nao ha outra. Testar no Botanico (102001, 8 maquininhas) ou Centro (103001, 9).

Verificado: eslint limpo; SFC compila (script+template, bindings ok); keys dos branches conferidas no render gerado; script setup real executado com stubs nos 9 cenarios de borda e depois com os dados REAIS das 5 filiais que tem maquininha (consultados no Postgres), conferindo que nao sobra valor duplicado; screenshots headless das duas etapas em desktop e 400px. quasar build nao rodou: dist/spa pertence a outro usuario (EACCES), nao e erro de codigo.

Ver TASK-144 (PIX), que aplica o mesmo padrao na etapa QR Code.
<!-- SECTION:NOTES:END -->
