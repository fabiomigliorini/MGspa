---
id: TASK-281
title: >-
  Consulta de preços volta pra tela inicial no meio da conferência do
  colaborador
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-18 15:06'
updated_date: '2026-11-18 15:28'
labels:
  - negocios
dependencies: []
priority: low
type: feature
ordinal: 292000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No quiosque de consulta de preços (negocios /quiosque), o colaborador conferindo algo grande perde a consulta: a tela volta sozinha para a tela de espera. A volta automática continua existindo, mas o colaborador precisa poder fixar a consulta quando precisar.

Origem técnica: negocios/src/pages/QuiosquePage.vue — agendarEspera(TEMPO_ESPERA) só é chamado em consultar(); os 60s contam da última leitura, e tocar/rolar a tela do produto não reinicia a contagem (na TASK-198 isso só foi feito na pesquisa F1, DialogPesquisaProduto.vue).

Decisão (18/11/2026): o cadeado vale até destravar, não só até a próxima leitura.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Botão de cadeado 'Fixar consulta' no menu do canto do quiosque: ligado, a consulta não volta sozinha para a tela de espera até destravar
- [x] #2 Enquanto fixada, um chip 'Consulta fixada' aparece na tela; um toque nele destrava
- [x] #3 Com a consulta fixada, ler outro código mostra o novo produto e continua fixado
- [x] #4 Recarregar a página ou sair do quiosque destrava
- [x] #5 Na tela do produto, tocar, rolar ou teclar reinicia os 60s para voltar à tela de espera, como já é na pesquisa F1
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação em negocios/src/pages/QuiosquePage.vue: ref fixada (só na página, por isso recarregar/sair destrava); agendarEspera guarda o tempo pedido (60s ou 8s do não encontrado) e não liga o timer enquanto fixada; fixar() desliga o timer, destravar() religa com o tempo do que está na tela. Toque/tecla/rolagem: listeners em captura no document (pointerdown, keydown, wheel, touchstart, scroll), mesmo padrão do DialogPesquisaProduto, reiniciam só a contagem que estiver correndo. Cadeado é a 2ª ação do FAB (lock/lock_open); chip laranja 'Consulta fixada' no canto inferior esquerdo, junto do 'atualizando', destrava com um toque.

Como testar (/quiosque num PDV ativo): (1) ler um produto e rolar/tocar a tela por mais de 60s: não volta para a espera; (2) parar de mexer: volta em 60s; (3) menu > cadeado: chip aparece, esperar >60s parado: continua; (4) ler outro código: troca o produto, chip continua; (5) tocar no chip: some, e volta para a espera 60s depois; (6) fixar e dar F5: volta destravado; (7) código inexistente: 'não localizado' some em 8s (destravado).

Teste (18/11/2026): Chrome headless contra o quasar dev (negocios-dev:9900), relógio adiantado pelo Playwright, api e imagens bloqueadas (produtos gravados no Dexie). 29 cenários passaram: volta em 60s parado; rolagem, toque e tecla reiniciam; fixar pelo menu segura 5+5 min, ler outro código troca o produto e continua fixado (leitor segue com o cursor depois do clique no menu); destravar pelo chip e pelo menu volta a contar 60s; 'não localizado' some em 8s destravado e fica enquanto fixado; F5 e sair/voltar destravam. Nenhum erro no console. Nenhum bug encontrado.
<!-- SECTION:NOTES:END -->
