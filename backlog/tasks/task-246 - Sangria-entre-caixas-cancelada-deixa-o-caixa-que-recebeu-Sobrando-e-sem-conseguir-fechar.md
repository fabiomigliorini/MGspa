---
id: TASK-246
title: >-
  Sangria entre caixas cancelada deixa o caixa que recebeu Sobrando e sem
  conseguir fechar
status: To Do
assignee: []
created_date: '2026-10-21 13:59'
labels:
  - contas
dependencies: []
priority: high
type: bug
ordinal: 256000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Bug em PRODUÇÃO (relato de 19/10/2026; não foi reproduzido no dev): o Caixa Botanico 04 fez uma sangria de R$ 20,00 para o Caixa Botanico 02, para complementar troco. Com o 02 já estável, o gerente cancelou a sangria (justificativa "PEGUEI O DINHEIRO DE VOLTA", cancelada por gustavo). No fim do dia o 04 fechou sem diferença; o 02 ficou com +R$ 20,04 (Sobrando), acima da tolerância de R$ 2,00, e não fechou: o período de 17 a 19/10 está Pendente, e o de 20/10 também. As notas foram contadas várias vezes, sempre com o mesmo resultado. Prioridade High: o caixa fica pendente, os períodos seguintes esperam atrás dele, e a saída que a tela oferece (Ajuste) esconde a causa.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 O período de 17 a 19/10 do Caixa Botanico 02 fecha e, junto com o 04, o dinheiro de cada gaveta bate com o que o sistema espera, sem Ajuste que esconda a causa
- [ ] #2 Dos dados de produção sai a causa confirmada (as duas pontas da sangria, a hora do cancelamento e a da contagem), registrada nas notas da task
- [ ] #3 Cancelar sangria ou reforço entre caixas avisa, na hora de cancelar, o que o cancelamento faz nos dois caixas e que o dinheiro não se move sozinho
- [ ] #4 A Diferença do resumo do período diz quando ela vem de lançamento cancelado, em vez de só mostrar o valor acima da tolerância
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Ocorrido em PRODUÇÃO (Caixa Botanico 02 e 04, 19/10/2026). Análise feita só com o código e os prints do relato: o dado de produção não está nesta máquina e nada foi consultado no dev. O que está marcado "a confirmar" depende de rodar os SELECTs abaixo em produção.

O que o sistema faz hoje (verificado no código, api/app/Mg/Portador):
- A sangria entre caixas são duas linhas de tblportadormovimento ligadas pelo par: a saída no 04 e a entrada no 02. PortadorLancamentoService::cancelarTransferencia marca as duas como canceladas, na mesma transação, se nenhum dos dois períodos estiver fechado, e recalcula os dois caixas. A linha continua na hora original (12:51 de 19/10): cancelar reescreve o período lá atrás.
- O esperado do caixa (PortadorPeriodoService::movimento) ignora linha cancelada, e a diferença é contado final menos esperado (recalcular e fecharCaixa). Cancelar não mexe na contagem: a contagem feita antes continua valendo e a diferença muda na hora.
- Dentro da tolerância o período fecha; acima, fica Pendente. Fecha-se do mais antigo para o mais novo, então o de 20/10 espera atrás dele. O período seguinte já começa com a contagem final do anterior (saldoInicialSeguinte), então a sobra não se repete, só trava a fila.
- Cancelar não move dinheiro, e o sistema não tem como saber se o dinheiro chegou a trocar de mão. Desenho em doc-4: cancelar só com o período não fechado nos dois lados.

O que os números do print dizem (período de 17 a 19/10 do 02): esperado 421,11 (479,40 inicial + vendas 991,71 - transferências 1.050,00), contado 441,15, diferença +20,04. Com a sangria valendo o esperado seria 441,11 e a diferença 0,04. Ou seja, a contagem do 02 bate com a gaveta com os 20,00 dentro: eles estavam lá na hora da contagem e o sistema deixou de esperá-los depois do cancelamento. Os 0,04 são moedas, dentro da tolerância.

Causa, a confirmar com dado de produção (na ordem do quanto combina com o relato "o 04 fechou coerente e o 02 sobrou 20"):
1. Pontas desencontradas (defeito): só a ponta do 02 foi cancelada e a saída do 04 continua valendo. O 04 fica coerente (o dinheiro saiu dele de verdade) e o 02 sobra 20, exatamente o relato. Pelo código as duas pontas se cancelam juntas, pelo par, então só aconteceria com o par trocado ou linha mexida por fora.
2. Contagem anterior à retirada: o 02 contou com os 20,00 na gaveta, o gerente cancelou e devolveu o dinheiro ao 04 depois (o 04 fica coerente), e a contagem do 02 não foi refeita depois da retirada.
3. Dinheiro não voltou: os 20,00 ficaram na gaveta do 02. Pelo código o 04 passaria a esperar os 20,00 de volta e fecharia faltando 20, não coerente; só encaixa se o relato do 04 estiver impreciso.

Para separar as três, rodar em produção (só SELECT):

```sql
select m.codportadormovimento, m.codportadormovimentopar as par, pt.portador, m.codportadorperiodo,
       m.valor, m.estado, m.transacao, m.criacao, m.cancelamento, u.usuario as cancelou, m.justificativa
from tblportadormovimento m
join tblportador pt on pt.codportador = m.codportador
left join tblusuario u on u.codusuario = m.codusuariocancelamento
where m.tipo = 'T' and m.transacao >= '2026-10-19' and m.transacao < '2026-10-20' and abs(m.valor) = 20
order by m.codportadormovimento;

select pt.portador, p.codportadorperiodo, p.inicio, p.fim, p.fechamento,
       p.saldoinicial, p.saldofinal, p.diferenca, p.alteracao
from tblportadorperiodo p
join tblportador pt on pt.codportador = p.codportador
where pt.portador in ('Caixa Botanico 02', 'Caixa Botanico 04') and coalesce(p.fim, now()) >= '2026-10-17'
order by pt.portador, p.inicio;
```

Leitura: se as duas linhas da sangria não estão com estado C, ou o par de uma não aponta para a outra, é a causa 1 (defeito). Se as duas estão C, comparar o horário do cancelamento com o alteracao do período do 02 e perguntar ao gustavo onde estava o dinheiro e quando contou (causas 2 e 3).

Furo do produto, qualquer que seja a causa:
- A janela de cancelar (components/portador/PeriodoLancamentos.vue, função cancelar) só pede o motivo. Não diz que cancelar desfaz os dois lados desde a hora original, nem que o dinheiro não se move. O gerente usou Cancelar para uma coisa que foi devolver dinheiro (justificativa: peguei o dinheiro de volta). Cancelar serve para o lançamento feito por engano, quando o dinheiro nunca mudou de mão; dinheiro que trocou de mão e voltou é uma sangria no sentido contrário (02 para 04), que mantém a história e as contagens.
- O resumo (components/portador/PeriodoResumo.vue) só mostra +20,04 Acima Tolerância, sem dizer que o período tem um lançamento cancelado de 20,00. A saída que a tela oferece é um Ajuste, que aceita a diferença sem explicação, esconde a causa e deixa o 04 sem acerto.

Não fazer antes de saber a causa: Ajuste no 02 para fechar o pendente.
<!-- SECTION:NOTES:END -->
