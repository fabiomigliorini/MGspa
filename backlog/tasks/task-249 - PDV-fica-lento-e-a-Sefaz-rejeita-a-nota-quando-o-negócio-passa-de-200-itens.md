---
id: TASK-249
title: PDV fica lento e a Sefaz rejeita a nota quando o negócio passa de 200 itens
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-20 16:28'
updated_date: '2026-10-21 16:10'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 254000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relato do Fábio (20/10/2026): negócio com mais de 200 itens fica muito lento no PDV e a Sefaz rejeita a nota. Decisão: limite de 200 itens ativos (linhas de mercadoria, não quantidade) só para negócio do PDV; a compra que vem da nota do fornecedor fica livre.

Histórico dos últimos 2 anos (fechados, >200 itens ativos): 152 Transferência Saída do PDV (até 1136 itens), 12 Compra fora do PDV (até 323), 1 Venda do PDV (222).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 PDV recusa produto novo a partir do 201º item, com a mensagem dizendo para fechar este negócio e abrir outro; somar quantidade em item já lançado continua livre
- [x] #2 Servidor recusa fechar negócio do PDV com mais de 200 itens ativos (cobre comanda unificada e venda duplicada)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação:
- negocios/src/stores/negocio.js: const LIMITE_ITENS = 200; itemAdicionar conta os itens ativos e recusa produto que ainda não está no negócio quando já tem 200 (Notify + som de erro + fala), devolvendo false. Somar quantidade em item já lançado passa. Devolve true quando adiciona.
- negocios/src/components/offline/InputBarras.vue: leitura de barras e escolha pela pesquisa param no false (sem o 'Código adicionado' nem o som de sucesso).
- api/app/Mg/Pdv/PdvNegocioService.php: const LIMITE_ITENS = 200; fechar() recusa acima disso, exceto venda reaberta (a nota já saiu, não dá mais pra dividir). Pega comanda unificada e venda duplicada.
- Compra vinda da nota do fornecedor (NegocioService::fechar, sem PDV) fica livre, por decisão.
<!-- SECTION:NOTES:END -->
