---
id: TASK-4
title: Descontar rateio de xerox quando nao fecha 100%
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-09-16 14:31'
labels:
  - pessoas
dependencies: []
priority: high
type: bug
ordinal: 97000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: pessoas/todo — "FIX: Quando rateio xerox nao fecha 100% sistema nao esta descontando"
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
REGRA DEFINIDA pelo Fabio em 16/09/2026:

1. O pool de 6% sobre a venda de xerox do setor FICA.
2. Depois de fechado o periodo, o GERENTE dita quanto cada operador recebe, como % desses 6%.
3. Se a soma das fatias der MENOS que 100% do pool, a sobra e considerada 'perdas'. Nao tem todo mes, NAO precisa ser modelada no codigo nem gravada. Portanto: nao normalizar, nao escalar pra cima. A sobra nao e paga a ninguem.
4. Unico caso ERRADO e a soma PASSAR de 100% do pool. Decisao: AVISAR na tela, sem bloquear a gravacao.
5. Quem digita continua sendo o RH (nenhuma mudanca de permissao).
6. RESTRICAO (16/09/2026): sem alteracao de banco. Nada de DDL.

DIAGNOSTICO

O 6% existia gravado em tblmeta.percentualcomissaoxerox = 6,00 em todos os periodos (mar/2025 a fev/2026), junto com percentualcomissaovendedor = 0,60. O rh_rubricas.sql (refatoracao Metas & Variaveis) COLAPSOU o rateio dentro do percentual da rubrica de proposito — 'dobra-se o rateio no proprio percentual (50% de 5% = 2,5%)' — e dropou tblperiodocolaboradorsetor.percentualrateio. O 0,60 sobreviveu (indicador do vendedor e INDIVIDUAL, nao ha o que dividir); o 6% virou conta de cabeca, porque o indicador do xerox e COLETIVO.

Sem o pool a vista, a multiplicacao manual desanda: em 28 combinacoes setor x periodo, 12 nao fecham. Periodo 9: Xerox Botanico soma 5,076 de 6 (84,6%, sobra R$ 100,18) e Xerox Centro 4,680 (78%, sobra R$ 99,13). Periodo 8: Xerox Botanico PASSOU (6,534 = 108,9%) e ja foi pago. Rastro do contorno manual no banco: rubrica batizada 'Xerox - calcular porcentagem' e uma rubrica FIXA de R$ 204 por cima da percentual.

Perdas nao existem em lugar nenhum do sistema (nenhuma coluna de perda/quebra/avaria, nenhuma rubrica negativa entre as 980 lancadas) e, conforme item 3, seguem sem existir.

SOLUCAO IMPLEMENTADA (sem DDL)

O calculo do PAGAMENTO nao foi tocado: CalculoRubricaService continua pagando percentual efetivo x indicador, sem normalizacao. O que mudou e que o pool passou a ser um dado visivel e a conta aparece na tela.

Pool: fica no catalogo, em tblrubrica.valorpadrao (coluna que ja existia, campo 'Percentual Padrao %' da tela de Rubricas). Numa rubrica percentual sobre indicador de SETOR ele significa o percentual CHEIO do setor. Fatia de cada um = percentual / pool; distribuido do setor = soma dos percentuais / pool.

Backend: CalculoRubricaService::rateioDistribuido() (uma query, sem N+1) devolve pool/soma/distribuido por indicador; DashboardController::unidade e PeriodoColaboradorController::index expoem o bloco 'rateio' (pool_percentual, pool_valor, soma_percentual, distribuido, sobra).

Front: cockpit do setor mostra 'Comissao do setor 6% = R$ 450,59 - 78% distribuido - sobram 22% (perda do mes)', em vermelho quando passa de 100%; o dialog da rubrica ganhou o campo 'Fatia do pool %' (o numero que o gerente dita, que grava o percentual efetivo) mais um painel com a soma do setor; o card de rubricas mostra '39% do pool' embaixo do percentual.

PENDENTE PARA ATIVAR: digitar 6 em 'Percentual Padrao %' nas rubricas 'Xerox' (29) e 'Xerox - calcular porcentagem' (30), na tela de catalogo de Rubricas. Sem isso o rateio nao aparece (proposital: sem pool a tela nao inventa numero).

VERIFICADO: rateioDistribuido rodado contra o banco real devolveu Imperial e Andre Maggi 100%, Botanico 84,6% (sobra 15,4%) e Centro 78% (sobra 22%) — teste com o pool=6 dentro de transacao com rollback, sem gravar nada.
<!-- SECTION:NOTES:END -->
