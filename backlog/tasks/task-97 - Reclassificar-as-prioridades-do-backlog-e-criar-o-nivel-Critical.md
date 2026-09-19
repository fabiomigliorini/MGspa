---
id: TASK-97
title: Reclassificar as prioridades do backlog e criar o nivel Critical
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 17:13'
updated_date: '2026-09-12 17:17'
labels: []
dependencies: []
priority: high
type: chore
ordinal: 96000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
As prioridades do backlog foram herdadas das secoes dos arquivos 'todo' legados, nao decididas por impacto: bloco negocios/todo secao IMPORTANTES e SEGURANCA virou high em bloco (TASK-31 a 48), DESEJAVEIS virou low (TASK-49 a 58), o topo sem secao virou medium (TASK-27 a 30), e as ~59 tasks vindas de varredura de codigo e dos blueprints ficaram sem prioridade nenhuma. Resultado: 23 tasks em high (1/4 do backlog) e inversoes claras, como TASK-45 (ordenar select, cosmetico) em high e TASK-58 (itens de negocios se misturam) em low.

Escopo: adicionar o nivel Critical ao backlog/config.yml, definir os quatro niveis por IMPACTO (nao por tipo, que ja tem campo proprio), classificar as 96 tasks e atualizar a regra do CLAUDE.md para que toda task nova nasca com prioridade.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Aplicado em 2026-09-12.

1. backlog/config.yml ganhou priorities: ["Critical", "High", "Medium", "Low"]. O Backlog.md
   aceita a lista customizada nativamente — grava 'critical' no frontmatter, filtra com
   --priority critical e ordena acima de High no --sort priority. Sem gambiarra de label.

2. As 96 tasks foram lidas uma a uma (nao classificadas pelo titulo) e reclassificadas;
   65 mudaram de prioridade. A tabela com a justificativa de cada uma foi revisada antes
   de aplicar.

3. CLAUDE.md: a regra 'prioridade so quando for evidente, fora isso deixar sem prioridade'
   foi substituida por 'toda task nasce com prioridade, default Low', com os quatro niveis
   definidos por impacto. Sem isso a proxima task nasceria vazia de novo e o high voltaria
   a inflar em poucos meses.

4. TASK-68 teve a descricao corrigida: o bloqueio nao e falta de credencial, e a Bee ainda
   nao ter disponibilizado a API de recarga.

Distribuicao: antes 23 high / 4 medium / 10 low / 59 sem prioridade.
Depois (as 96) 2 Critical / 21 High / 32 Medium / 41 Low, zero sem prioridade.

Os dois Critical sao TASK-3 (modulo RH acessivel sem permissao) e TASK-69 (rota publica do
Pix enumeravel por PK sequencial) — os dois sao exposicao de dado acontecendo agora.

Rebaixamentos que valem citar: TASK-45 (ordenar select, cosmetico) saiu de high para Low, e
TASK-40 e TASK-43 sairam de high para Medium. Promocoes: TASK-16, TASK-23 e TASK-92 eram
bugs sem prioridade nenhuma e foram para High.
<!-- SECTION:NOTES:END -->
