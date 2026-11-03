---
id: TASK-262
title: >-
  Comissão de quem trabalha só alguns dias no xerox e desconto de faltas são
  feitos de cabeça
status: In Progress
assignee:
  - '@eduardo'
created_date: '2026-11-03 19:06'
updated_date: '2026-11-03 20:22'
labels:
  - pessoas
dependencies: []
priority: medium
type: feature
ordinal: 270000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quem cobre alguns dias num xerox recebe comissão calculada à mão: fatias fixas de 20% para quem vem de Vendas (Igor, Vitor), fatias quebradas no Botânico (13,9% / 6,9% / 5,6%) e um Fixo R$ 204 'Xerox - Centro' para o João Lucas pelos dias que cobriu no Centro. Falta também não desconta nada nas rubricas.

Origem: até 29/07/2026 havia dias trabalhados por colaborador x setor (tblperiodocolaboradorsetor.diastrabalhados) e a chave 'Desconta Absenteísmo' por rubrica. O commit bf0b040e3 ('Primeira Refatoracao Tela Metas') + api/database/rh_rubricas.sql (itens 3 e 4) removeram os dois de propósito; o tipo Q que deveria substituir nunca foi adotado.

Decisões (03/11/2026):
- Xerox = fatia x dias: o gerente continua ditando a fatia de quem trabalha o período inteiro (TASK-4); cada um recebe fatia x dias / dias úteis. O valor de cada pessoa não depende dos outros; o que não se distribui continua sendo perda.
- Faltas informadas no novo acerto: sem falta = 100% das rubricas; com falta, desconto proporcional nas rubricas marcadas.
- Chave 'Desconta absenteísmo' só no catálogo de Rubricas, nula por padrão (nula = não desconta).
- Presenteísmo continua manual. Salário e recibo fora do escopo.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Faltas informadas no acerto descontam proporcionalmente as rubricas marcadas no catálogo; dias da rubrica têm precedência
- [ ] #2 Catálogo de Rubricas tem o toggle 'Desconta absenteísmo' (nulo = não desconta) e a coluna na listagem
- [ ] #3 Período novo copia os dias da rubrica recorrente; faltas não copiam
- [ ] #4 Tela do colaborador mostra faltas e dias da rubrica
- [ ] #5 Rubrica percentual tem 'Dias no setor' e paga percentual × dias ÷ dias úteis (Xerox Centro per. 9: fulano 6% com 16 dias = R$ 277,29; Zezinho 6% com 10 dias = R$ 173,30)
- [ ] #6 Sem legendas de rateio e sem 'Fatia do pool %'/'% do pool': o percentual da rubrica é a comissão que o gerente deu; sobra do setor é o que o RH não distribui
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
IMPLEMENTADO 03/11/2026 — aguardando validação (nada commitado).

REGRA (uma só): CalculoRubricaService::fatorDias(diastrabalhados, faltas, descontaabsenteismo, diasuteis), espelhada em pessoas/src/utils/rhFormatters.js:
1. rubrica com dias no setor -> min(dias, du) / du (precedência)
2. senão, catálogo com descontaabsenteismo e faltas > 0 -> max(du - faltas, 0) / du
3. senão -> 1 (sempre 1 se du = 0)
Aplicado ao valor de P, F e Q em calcularColaborador. rateioDistribuido (painel do setor) soma percentual x fator linha a linha.

BANCO: api/database/rh_dias_trabalhados.sql (idempotente) — tblcolaboradorrubrica.diastrabalhados, tblperiodocolaborador.faltas, tblrubrica.descontaabsenteismo, todas nulas. Rodado no dev em 03/11/2026. PROD: rodar à mão antes do deploy.

API: PATCH v1/rh/periodo/{codperiodo}/colaborador/{codpc}/faltas (PeriodoColaboradorController::atualizarFaltas: valida 0..diasuteis, recusa encerrado, grava e recalcula). AcertoService::buscarTitulos devolve faltas/diasuteis. select/rubrica devolve descontaabsenteismo. PeriodoService copia diastrabalhados das recorrentes para o período novo (faltas não).

FRONT: AcertoModal (campo Faltas + botão Aplicar; Confirmar travado com faltas não aplicadas), ColaboradorDetalhe (Dias no setor no dialog da rubrica percentual, painel do rateio com fatia x dias, faltas no cabeçalho), CardRubricas ('6 de 26 dias' / '- 2 faltas'), RubricaForm + RubricaCatalogo (toggle e coluna Desc. faltas).

CONFERIDO no banco do dev, em transação com rollback: Xerox Centro per. 9 (venda 7.509,83, 26 dias úteis, fatias 39%): Eva com 6 faltas e Xerox marcado = 135,18; Rafael = 175,73; João com 6 dias = 40,55; painel 78% distribuído. Alimentação R$ 390 per. 13 (25 dias): 2 faltas sem a chave = 390; com a chave = 358,80; dias 10 na rubrica = 156 (precedência); dias 40 = 390 (limita ao período).

DEPOIS DE VALIDADO: RH troca o Fixo 'Xerox - Centro' (R$ 204) do João por rubrica percentual no indicador Xerox Centro com dias.

DADOS DE TESTE NO DEV (03/11/2026): per. 9 — Eva (pc 637) faltas 6; João (pc 638) rubrica nova 1755 Xerox 2,34% no Xerox Centro com 6 dias, fixo 'Xerox - Centro' (1038) com concedido=false; per. 13 — catálogo Alimentação (3) marcado, Elizandra (pc 986) faltas 2. Desfazer: DELETE da rubrica 1755, 1038 concedido=true, faltas NULL em 637/986, tblrubrica 3 descontaabsenteismo NULL, e recalcular.

03/11/2026 (pedido do usuário): removidas as legendas de rateio — o quadro azul do diálogo da rubrica (ColaboradorDetalhe) e a linha 'Comissão do setor X% = R$ · N% distribuído' do card do setor (CockpitUnidade, exibição da TASK-4). DashboardController deixou de calcular o rateio (rateioDoIndicador removido); CalculoRubricaService::rateioDistribuido voltou ao original (só fornece o pool para o campo Fatia do pool %). Não há margem fixa no cálculo: o '20%' do quadro era a fatia do Igor (1,2 de 6) no Xerox André Maggi; a sobra é o que o RH não distribui.

03/11/2026 (pedido do usuário): saiu o conceito de pool. O percentual da rubrica é a comissão que o gerente deu para a pessoa (ex.: 6% da venda do xerox). Cobertura: Zezinho cobriu fulano 10 dias -> RH põe 16 dias na rubrica do fulano (ou 10 faltas no acerto) e 10 dias na do Zezinho; sem ligação automática entre rubricas. Removidos: campo 'Fatia do pool %' (ColaboradorDetalhe), legenda '% do pool' (CardRubricas), bloco rateio do PeriodoColaboradorController::index e CalculoRubricaService::rateioDistribuido (exibição da TASK-4). Cálculo inalterado. Dados de teste do dev: Eva rubrica 866 = 6% com 16 dias (era 2,34%), João 1755 = 6% com 10 dias, faltas da Eva zeradas.
<!-- SECTION:NOTES:END -->
