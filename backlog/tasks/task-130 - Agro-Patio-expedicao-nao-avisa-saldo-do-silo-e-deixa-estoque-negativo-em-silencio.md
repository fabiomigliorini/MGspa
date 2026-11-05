---
id: TASK-130
title: >-
  Agro/Patio: expedicao nao avisa saldo do silo e deixa estoque negativo em
  silencio
status: Done
assignee:
  - '@eduardo'
created_date: '2026-09-21 21:33'
updated_date: '2026-11-05 19:51'
labels:
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 2000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A store do patio ja tem saldoUnidadeOffline(cod) (snapshot do servidor + delta das cargas locais pendentes) e o snapshot saldosUnidades e baixado a cada sync - mas NENHUM componente usa: grep por saldoUnidadeOffline/saldosUnidades nos .vue nao acha nada. O CargaForm mostra 'Saldo a entregar' so para DESTINO do tipo CONTRATO. Consequencia: numa SAIDA ou TRANSFERENCIA o operador carrega 40 t de um silo com 10 t e nada avisa - nem no front nem no backend (CargaService::validar so cobra rateio e over-load de contrato). O saldo da unidade fica negativo e so aparece na tela de Estoque. Exibir o saldo da unidade na linha de ORIGEM (mesmo padrao do contrato) e decidir se o backend bloqueia ou so avisa.

**Estado no código em 22/10/2026 (leitura):** 0 de 4 critérios no código. A linha de ORIGEM só tem o select (o saldo existe só no destino CONTRATO); `saldoUnidadeOffline`/`saldosUnidades` seguem sem consumidor e `puxarSaldos` roda a cada minuto sem `codsafra`. O fechamento não pede confirmação e o Estoque não destaca silo negativo. O #4 hoje faz o contrário da regra D6 do doc-5: CargaService::gerarMovimento aplica o sinal ao líquido e MovimentoGraoService::saldo soma o líquido, então o silo de origem baixa o líquido e o desconto vira grão fantasma no silo. **Pergunta em aberto:** a D6 vale só para cargas novas ou o extrato das expedições já finalizadas é regerado?
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Linha de ORIGEM (silo) mostra o saldo do silo na safra da carga, como a do contrato mostra o saldo a entregar
- [x] #2 Saída de silo baixa o peso bruto; o destino recebe o líquido e a quebra aparece no relatório
- [x] #3 Silo sem saldo na safra da carga não despacha: a tela avisa e o servidor recusa a finalização
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): o AC 'Silo inativo não recebe carga nova' saiu: a tela já só oferece silo ativo desde jun/26 (agro/src/stores/carga.js unidadesAtivas, SelectUnidade); a recusa no servidor ficou na TASK-181.

05/11/2026 — escopo simplificado pelo Fábio: silo sem saldo não despacha (substitui a D1 'silo pode ficar negativo' e o pedir confirmação; o destaque de silo negativo no Estoque saiu junto). Servidor: CargaService::validarSaldoSilo na finalização compara o bruto que cada silo de origem entrega com o saldo dele na safra da carga, sem o extrato da própria carga, com trava por silo. D6 no gerarMovimento: silo de origem baixa o bruto, desconto 0; o destino recebe o líquido (só cargas finalizadas/editadas daqui pra frente). saldosUnidades devolve também o saldo por safra (safras[]). Tela: linha do silo de origem mostra 'Saldo no silo nesta safra · esta carga · faltam', vermelha e com regra quando falta. Testado no dev (rollback): 30 t de silo com 90,6 t aceita; 100 t recusa 'SILO tem 90.665 kg desta safra e a carga tira 100.000 kg' (os 180 t de soja do mesmo silo não contam); 30 t com 600 kg de desconto grava silo −30.000 e contrato 29.400.

05/11/2026 (revisão): reativar expedição cancelada passa pela mesma conferência de saldo (testado: 80 t aceita, cancela, outra de 80 t, reativar a 1ª recusa 'SILO tem 10.665 kg'). Cancelar carga antiga fora da matriz segue aceito (validarPontos não roda em carga inativa). Saldo offline: carga local pendente desconta do silo a parte do bruto (D6). Gap conhecido: ajuste manual de silo sem safra não entra no saldo por safra (no dev não há nenhum).

05/11/2026 — fechamento. A D6 não afeta outras telas: nenhum serviço soma o desconto do extrato de grão, e o relatório de romaneios (CargaRelatorioService) soma o desconto da própria carga, então a quebra segue aparecendo nele. O ajuste manual da tela de Estoque sempre leva a safra selecionada (extrato.js novoAjuste); ajuste de silo sem safra só existe chamando a API direto.
<!-- SECTION:NOTES:END -->
