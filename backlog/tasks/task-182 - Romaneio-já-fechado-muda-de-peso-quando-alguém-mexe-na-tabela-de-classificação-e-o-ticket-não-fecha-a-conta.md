---
id: TASK-182
title: >-
  Romaneio já fechado muda de peso quando alguém mexe na tabela de
  classificação, e o ticket não fecha a conta
status: Done
assignee: []
created_date: '2026-09-28 21:11'
updated_date: '2026-09-30 14:30'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 3000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Toda regravação da carga (inclusive cancelar pelo pátio) recalcula o desconto com a tabela de classificação vigente: editar tolerância ou fator muda romaneio já finalizado e o extrato. O cálculo é em gramas e o ticket imprime kg inteiro, então bruto menos desconto impresso não bate com o líquido (ex.: 9.999 - 51 impresso, líquido 9.949). O kg de cada origem/destino gravado no ponto é o do aparelho, e o extrato usa o rateio do servidor: divergem, e a NF usa o do ponto. Parâmetros com a mesma ordem deixam a cascata sem ordem definida (pátio e servidor chegam a divergir 33 kg), e o servidor aceita leitura sem limite. Decisões de 28/09: kg inteiro em tudo; tabela congelada no 1º FINALIZADO (tblcarga.parametrosclassificacao jsonb); rateio feito pelo servidor e gravado no ponto. Plano: /home/usuario/.claude/plans/valide-valores-saldos-e-merry-pnueli.md (Fase 3).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Alterar a tabela de classificação não muda romaneio finalizado
- [ ] #2 Descontos, líquido e rateio ficam em kg inteiro, e o ticket fecha a conta
- [ ] #3 O kg de cada origem e destino é o mesmo no ticket, na ficha, na nota e no extrato
- [ ] #4 Ticket reimpresso é igual ao gravado
- [ ] #5 Pátio e servidor calculam o mesmo desconto
- [ ] #6 Ordem repetida, tolerância 100 e leitura fora de 0–100 são recusadas com mensagem
- [ ] #7 Trocar o talhão para outra cultura depois de classificar avisa em vez de zerar o desconto
- [ ] #8 A carga não fica presa em erro depois que a tabela muda
<!-- AC:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-30 14:30
---
Revisada em conjunto com a TASK-129 em 30/09/2026, a pedido do responsavel, que considerou o cenario do titulo (alguem editar a tabela de classificacao manualmente) raro na pratica -- ajustes hoje sao feitos por query SQL direta, nao pela tela. Fechada sem implementacao. Registro pra nao perder o levantamento: (1) a tela agro/ParametroClassificacaoPage.vue existe e permite editar tolerancia/fator pela UI, mas o mecanismo real independe disso -- CargaService::sincronizar() sempre busca a tabela vigente e recalcula em QUALQUER regravacao de carga ja finalizada (cancelar pelo patio, reabrir pra corrigir campo, retry apos falha de rede, trocar talhao), inclusive a propria query manual de ajuste tem esse efeito colateral na proxima regravacao de carga antiga da mesma cultura. (2) Os criterios #2 a #8 sao bugs independentes, com causa propria no codigo, sem depender do #1: arredondamento em grama vs ticket em kg inteiro (CargaService::calcular vs utils/ticket.js), dois algoritmos de rateio diferentes pra ponto (CargaPonto.liquido) vs extrato (CargaService::gerarMovimento/ratear), calculo duplicado cliente/servidor (utils/desconto.js replica CargaService, sem garantia de convergencia), falta de validacao de ordem unica/tolerancia<100/leitura 0-100 em ParametroClassificacaoStoreRequest e CargaSincronizarRequest, e troca silenciosa de cultura ao trocar talhao em CargaBlocoPontos.vue::onPlantioSelecionado sem aviso. Se o assunto voltar (reclamacao de romaneio fechado que nao bate, ticket que nao fecha conta), comecar por esses pontos.
---
<!-- COMMENTS:END -->
