---
id: TASK-270
title: >-
  Pátio: classificação da carga lista até defeito zerado e enche as porcentagens
  de legenda
status: Done
assignee:
  - '@eduardo'
created_date: '2026-11-05 20:19'
updated_date: '2026-11-05 21:39'
labels:
  - agro
dependencies: []
priority: low
type: enhancement
ordinal: 278000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (05/11/2026). O card Classificação da carga no pátio (/carga/:uuid) lista todos os parâmetros num grid, inclusive os de leitura 0% ou vazia, com legenda embaixo de cada porcentagem ('acima da tolerância', '− X kg'). Pedido: uma linha no mesmo desenho do card Pesagem (TASK-269) só com os parâmetros que o grão tem (leitura > 0), sem legenda, e um botão Detalhes no estilo do lápis de editar que abre um diálogo com o desconto de cada parâmetro (Parâmetro · Leitura · Tolerância · Desconto) e o total. Arquivo: agro/src/components/carga/CargaBlocoClassificacao.vue. O diálogo de digitação da classificação não muda. Começa depois da validação da TASK-269.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 O card mostra, no desenho da pesagem, só os parâmetros com leitura acima de zero
- [x] #2 O card não tem legenda embaixo das porcentagens
- [x] #3 O botão Detalhes, no estilo do lápis, abre o desconto de cada parâmetro e o total
- [x] #4 O diálogo de digitação mostra embaixo da leitura só o desconto em kg (quando há), sem 'acima da tolerância'
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
05/11/2026 — CargaBlocoClassificacao.vue: o grid de todos os parâmetros virou uma linha com as mesmas classes da linha da Pesagem (TASK-269, sem o fundo cinza que o Fábio tirou), só com os parâmetros de leitura > 0 (computed leituras), rótulo sem o número da ordem. Sem legenda; acima da tolerância a porcentagem fica laranja. Sem nada a mostrar: 'Ainda sem leitura.' ou 'Todas as leituras em 0%.'. Botão Detalhes (ícone info, mesmos props do lápis, tooltip Detalhes, só com leitura) abre diálogo só leitura no molde dos diálogos da carga: Parâmetro · Leitura · Tolerância · Desconto (colunas da ficha do romaneio), todo parâmetro lido inclusive 0%, e Total = calc.desconto (o Descontos da pesagem); antes da tara mostra '—' e 'O desconto em kg sai depois da tara.'. O diálogo segura o F3 (a página escuta no document e avançaria a etapa por baixo). descontoParam passou a '?? null' (0 kg aparece). Diálogo de digitação sem mudança. Conferido com página temporária no Vite + Chrome headless (1280 e 390 px) com a carga 21494 real do dev: card com Impureza 5,0 · Umidade 17,0 · Avariados 9,0 · Esverdeados 9,0 (laranja) · Quebrados 3,0 (dentro da tolerância de 30); diálogo 2.222 · 1.841 · 554 · 554 · 0 kg, Total 5.171 kg = Descontos da pesagem (e o desconto do servidor, 5.170,627); Avariados/Esverdeados em 0 somem do card; tudo 0 e sem leitura com as frases certas; sem tara com '—' e o aviso. Teste: Ctrl+Shift+R e abrir /carga/a870aae1-b74b-47ff-8463-0d9b85a0b3e3 (carga 21494).

05/11/2026 (validação) — a pedido do Fábio, labels do diálogo de descontos maiores: cabeçalho 12→14px e linhas 13→16px (classe tabela-descontos + :deep no th/td, padrão da tabela-cargas da CargasPage; as classes de tipografia do Quasar perdem para a regra do q-table). No celular (≤599px, diálogo maximizado) cabeçalho 13px, linhas 15px e respiro lateral de 6px, senão a coluna Desconto saía cortada em 390px; conferido em 360, 390 e 1280px.

05/11/2026 (validação) — a pedido do Fábio, o diálogo de digitação perdeu a legenda 'acima da tolerância' (a leitura acima da tolerância já fica laranja no campo); embaixo do campo ficam a dica 'Tol. X%' e, quando há desconto, só o '− X kg'. Conferido com a carga 21494: Impureza/Umidade/Avariados/Esverdeados com '− kg', Quebrados (3% dentro de 30%) só com a dica.
<!-- SECTION:NOTES:END -->
