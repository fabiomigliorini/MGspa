---
id: TASK-243
title: >-
  Decidir se o Negócios e o Pessoas continuam com o moment ou passam para as
  datas do Quasar
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-18 14:09'
updated_date: '2026-10-19 02:24'
labels:
  - negocios
  - pessoas
dependencies: []
priority: low
type: spike
ordinal: 252000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Decidir se o Negócios e o Pessoas continuam usando o moment para datas ou passam para o padrão dos outros apps, sem moment: o date do Quasar e o @components/formatters.

Origem: pedido do Fábio em 18/10/2026, depois da TASK-238 (datas em inglês por causa do idioma do moment) e da TASK-242 (limpeza do moment no Negócios e no Pessoas).

Situação em 18/10/2026:
- Só o negocios e o pessoas têm moment (package.json ^2.29.4, instalado 2.30.1). O negocios tem 14 arquivos importando o moment e o pessoas 15, com ~110 chamadas. Os usos mais comuns: add (49), format (42), startOf (25), isValid (22), subtract (21), toDate (20), fromNow (13), diff (9).
- agro, contas, estoque e notas não usam moment: usam @components/formatters (JS puro, inclusive tempoRelativo/formataFromNow para o 'há 22 minutos') e o date do Quasar (agro 1 arquivo, contas 3, notas 1). O negocios já usa o formatters em 36 arquivos e o pessoas em 58.
- O date do Quasar cobre formatDate, addToDate, subtractFromDate, startOfDate, endOfDate, getDateDiff, isSameDate, isValid e extractDate; nomes de mês e dia vêm do idioma do Quasar (pt-BR em todos os apps, menos o notas, que está en-US). Não tem tempo relativo: esse fica no tempoRelativo do formatters.
- O moment está em modo de manutenção (o próprio projeto pede para não usar em código novo) e custa ~59 KB minificado; o idioma dele no Vite tem pegadinha (ver notas da TASK-238).

Se a decisão for migrar, a TASK-242 perde o sentido ou vira parte da migração.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Levantado o que o Negócios e o Pessoas usam do moment e o equivalente no date do Quasar ou no @components/formatters, uso por uso
- [x] #2 Decisão registrada na task: continuar com o moment ou migrar
- [x] #3 Se migrar: plano de troca por app registrado na task, dizendo o que acontece com a TASK-242
- [x] #4 Conta com data num lugar só, @components/utils/datas.js, no lugar das cópias de hoje/agora/início e fim de mês/dias entre datas dos seis apps
- [x] #5 Negócios e Pessoas sem moment: telas, boot e package.json
- [x] #6 'Hoje' não vira amanhã depois das 20h (agro, estoque, notas, cheque) e o extrato do contas mostra o mês com o último lançamento no dia 1º
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decisão (18/10/2026, Fábio): aposentar o moment e padronizar as datas dos seis apps. Conta com data em @components/utils/datas.js (Date puro, sem biblioteca: nem moment, nem date do Quasar, que só converte texto com new Date e herda o bug do UTC); mostrar data continua no @components/formatters. Quando o Safari tiver o Temporal (ES2026; Chrome e Firefox já têm), troca só o miolo do datas.js. Quasar 2.35.1 (12/10/2026) ainda é Date puro e não tem o Temporal no roadmap.

datas.js: hoje, agora(segundos), somaDias, somaMeses, somaAnos, inicioMes, fimMes, inicioAno, diasEntre, duracao, paraDate, lerDataBr, verificaPassadoFuturo e verificaIdade (as duas últimas saíram do formatters, que ficou só com formatação; o apelido formataFromNow virou tempoRelativo nos 9 lugares). Recebem 'YYYY-MM-DD', 'YYYY-MM-DD HH:mm:ss' ou Date e devolvem 'YYYY-MM-DD' no horário local. O formatters passou a ler data pelo paraDate (mesma saída; número em milissegundos agora formata, data que não existe sai vazia).

Levantamento: moment no negocios (14 arquivos) e no pessoas (15) com add, format, startOf, isValid, subtract, toDate, fromNow e diff; e cópias de data espalhadas: "hoje" (~17), "agora" (8), início/fim de mês (5), dias entre datas (4), "ler YYYY-MM-DD sem o UTC" (6), formataData local do estoque (4). Equivalentes: fromNow -> tempoRelativo; add/subtract -> somaDias/somaMeses/somaAnos; startOf/endOf -> inicioMes/fimMes/inicioAno; diff dias -> diasEntre; tempo de casa -> duracao; moment(v, 'DD/MM/YYYY', true) -> lerDataBr.

Bugs que a troca corrigiu: (1) "hoje" com new Date().toISOString() virava amanhã depois das 20h em agro (recebimento, fixação/impostos, câmbio do contrato), estoque (conferência), notas (vigência da tributação, simulador, vencimento da guia ST) e no cheque da cobrança; (2) contas, extrato: com o último lançamento no dia 1º o mês sumia do filtro (primeira_data/ultima_data vêm 'YYYY-MM-DD' e eram lidas em UTC), e o rolar até o dia caía no dia anterior; (3) estoque: data sem hora (vencimento da localização, última compra) aparecia um dia antes; (4) agro, extrato: data do movimento idem.

Ficaram de fora: os internos do MgInputData (leitura estrita do que se digita, contrato próprio), os ~60 .slice(0, 10) (cortam o texto da API, corretos), o calcularPrimeiraDias do agrupamento de títulos (regra própria), os MESES dos rótulos de mês do portador e da maquineta no contas e a tradução do calendário do CardFerias (semana começa no domingo; a do formatters, na segunda).

Mudança visível: o tempo relativo do Negócios e do Pessoas sai como nos outros apps (Intl): 'ontem', 'mês passado', 'ano passado' no lugar de 'há um dia', 'há um mês', 'há um ano'; segue o idioma do navegador (decidido em 18/10). Ticket do agro e histórico da conferência do estoque: '18/10/2026 14:30:00' sem a vírgula do toLocaleString.

Teste (18/10/2026, TZ=America/Cuiaba, relógio fixado em 7 instantes: manhã, 21h30, fim de mês, fim de ano, 29/02, virada, 1º de março): datas.js 11/11 (somaMeses igual ao moment em 37 mil casos; duracao igual ao moment em 24 mil pares — no fuso local o moment errava 1 dia quando o intervalo pegava o início do antigo horário de verão, ex. 16/06 a 06/11/2016); formatters, vencimento sugerido, último dia útil e juros antes x depois iguais em ~80 mil casos (8/8); trechos das telas antes (HEAD) x depois 19/19, inclusive aniversários (3 mil pessoas, 2 anos de dias selecionados), período aquisitivo/gozo, experiência/renovação e validações, sincronização (completa hoje, incremental 7 dias, 4 h), confissão faltando e filtros. eslint e prettier limpos. quasar build -m pwa dos seis apps num worktree só com estas alterações: todos passam, sem moment nos pacotes; JS do negocios 1614 -> 1552 KB e do pessoas 1468 -> 1405 KB.

Roteiro na tela: Negócios — tooltip e cor do Sincronizar, tempo relativo nas listas (drawers, títulos, notas, devoluções, orçamentos, Woo, PagarMe, total do negócio), Confissão faltando, filtro inicial da listagem; recebimento à vista e a prazo (vencimentos sugeridos, fechamento no último dia útil), cheque (Bom para = hoje, à noite). Pessoas — aniversários, férias do ano, colaborador (experiência/renovação, tempo de casa, validações), cargo, férias (aquisitivo e gozo), grupo econômico, comissão dos caixas, metas e período do RH (sugestão do próximo). Contas — extrato (meses do filtro, rolar até o dia), título novo (vencimento +30), cor do vencimento, agrupamento (parcelas), baixa e adiantamento (hora de agora), fechar banco com corte (último dia do mês anterior). Agro — câmbio/recebimento/fixação depois das 20h (máximo = hoje), contrato (dias para o embarque), cargas (filtro do mês, hora do ciclo, ticket). Estoque — conferência, marcas, produto. Notas — tributação vigente, simulador, NF-e de terceiro (entrada, guia ST), nota nova (emissão/saída), inutilização por mês, Domínio (mês anterior).
<!-- SECTION:NOTES:END -->
