---
id: TASK-182
title: >-
  Romaneio já fechado muda de peso quando alguém mexe na tabela de
  classificação, e o ticket não fecha a conta
status: To Do
assignee: []
created_date: '2026-09-28 21:11'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 195000
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
