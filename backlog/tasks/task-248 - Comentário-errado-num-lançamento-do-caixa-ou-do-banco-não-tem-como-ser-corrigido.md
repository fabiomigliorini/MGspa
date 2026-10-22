---
id: TASK-248
title: >-
  Comentário errado num lançamento do caixa ou do banco não tem como ser
  corrigido
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-21 15:59'
updated_date: '2026-10-21 16:07'
labels:
  - contas
dependencies: []
priority: low
type: enhancement
ordinal: 257000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio em 21/10/2026, com print do extrato do portador: uma transferência gravada com a observação "1346,00 de 1,00" não tinha como ser corrigida. Hoje a linha só tem o calendário (Alterar a data, TASK-204). Decisão: um botão Editar só, no lugar do calendário, com a data e o comentário no mesmo dialog; a justificativa é exigida se a data mudar e fica desabilitada se não mudar.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Editar (lápis) no lugar do calendário abre data e comentário no mesmo dialog
- [x] #2 Justificativa obrigatória só quando a data muda; sem mudar a data, fica desabilitada
- [x] #3 O comentário da transferência é corrigido nas duas pontas
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (21/10/2026):
- API: PUT v1/portador-movimento/{id} (PortadorLancamentoController@editar) substitui o POST .../{id}/data. Recebe só o que mudou: observacoes (PortadorLancamentoService::alterarObservacoes) e/ou transacao + justificativa (LancamentoDataService::alterarMovimento, como antes), numa transação só.
- Comentário: mesma permissão do alterar a data (LancamentoDataService::podeMovimento), período não fechado, linha não cancelada; transferência nas duas pontas; ajuste continua exigindo o motivo (mínimo 3 letras); pagamento recusa (o comentário é do pagamento). Sem auditoria (só a data audita).
- Front: components/portador/AlterarDataDialog.vue ganhou com-observacoes/observacoes (título Editar lançamento; justificativa desabilitada enquanto a data não muda; Alterar habilitado se a data ou a observação mudou). components/portador/PeriodoLancamentos.vue: lápis no lugar do calendário; linha de pagamento abre só a data. periodoStore.alterarData virou editar (PUT).
- Maquineta (contas/src/components/maquineta) usa o mesmo dialog sem com-observacoes: só muda que a justificativa fica desabilitada até a data mudar.
- PDV com o front antigo carregado chama o POST .../data, que não existe mais: recarregar (F5).

Testar: contas > Portador > período com transferência > lápis na linha: (1) só a observação: justificativa desabilitada, Alterar grava, as duas pontas mostram o texto novo; (2) a data: justificativa obrigatória, a linha muda de período e aparece o era...; (3) linha de venda/pagamento: lápis abre só data + justificativa; (4) ajuste com observação vazia recusa; (5) no PDV (negocios > caixa) o mesmo.
<!-- SECTION:NOTES:END -->
