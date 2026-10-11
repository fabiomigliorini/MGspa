---
id: TASK-219
title: >-
  PIX que cai na conta vira pagamento sozinho e não há onde ver os que ainda não
  foram usados
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-11 00:33'
updated_date: '2026-10-11 01:16'
labels:
  - contas
  - api
dependencies: []
priority: high
type: bug
ordinal: 230000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na TASK-188 (critério #31) todo PIX recebido pela chave virou pagamento avulso, sem documento, na hora da importação do banco. Na listagem de pagamentos do dev apareceram centenas de PIX 'Avulso · Sem documento' (1.186 desde 09/10), e o boleto pago por PIX entrava duas vezes no banco (o pagamento do retorno do boleto e o PIX avulso). O Fábio rejeitou o modelo (10/10/2026): 'era pra ser uma coisa simples'.

Regra nova: PIX que ninguém usou fica na tela PIX. Usar o PIX = gravar um pagamento com ele (baixa de títulos ou adiantamento, sempre o PIX inteiro; PIX maior que os títulos vira adiantamento e depois encontro de contas). Cancelou o pagamento, o PIX volta para a lista.

Prioridade alta: bloqueia o go-live do caixa (sem isso o pessoal não tem onde ver os PIX que caíram e não foram baixados).

Campos no PIX (tblpix), no padrão de criacao/alteracao: utilizacao (quando foi usado; vazio = sem uso), codusuarioutilizacao (quem) e codpagamento (o pagamento que usou; vazio quando marcado à mão). Nada do modelo novo está em produção: o SQL de go-live marca todos os PIX antigos como usados.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A importação do PIX não cria pagamento; só o PIX QR de venda paga a venda
- [x] #2 PIX que pagou boleto nosso (txid do QR do boleto) entra marcado como usado e ganha o link do pagamento quando a baixa do boleto chega: não duplica com o retorno
- [x] #3 Tela PIX do contas: filtro sem uso / usados / todos; a linha mostra a cobrança de origem (quem gerou, PDV, quando) e o link do pagamento que usou o PIX
- [x] #4 PIX sem uso: Baixar títulos abre o Receber Títulos com o PIX (data e conta travadas, títulos somando o valor do PIX); Adiantamento abre o adiantamento com o valor do PIX
- [x] #5 Marcar como usado e Desmarcar à mão, só no PIX sem pagamento ligado
- [x] #6 Wizard com PIX QR fora de venda: a confirmação do banco não cria pagamento; o pagamento nasce ao gravar e, se o wizard fechar antes, o PIX fica sem uso na tela PIX
- [x] #7 Cancelar um pagamento com PIX (inclusive pelo cancelamento da venda) devolve o PIX para a lista de sem uso
- [x] #8 SQL de go-live com os campos novos marcando os PIX antigos como usados; no dev, os pagamentos avulsos criados pela importação apagados
- [x] #9 Receber Títulos com o PIX: mostra quanto falta (ou quanto passou) do valor do PIX enquanto se escolhem os títulos, e o botão de gravar só habilita quando bate
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Implementação (10/10/2026)

- **Banco** (`api/database/pix_utilizacao.sql`, já rodado no dev): `tblpix.utilizacao`, `codusuarioutilizacao` e `codpagamento` com FKs. Índice parcial dos sem uso (`where utilizacao is null`), índice do codpagamento e do txid, e `tbltituloboleto.qrcodetxid`. A marcação dos PIX antigos como usados (com o pagamento ligado, por `codpix` ou pela cobrança) só roda na execução que cria as colunas.
- **`Mg\Pix\PixUsoService`**: `pagar` (o PIX inteiro, na data e na conta dele, e marca), `usar`, `pagamentoCancelado` (chamado por `PagamentoService::cancelar`, o único lugar que desmarca), `boleto`/`boletoBaixado` (txid = `tbltituloboleto.qrcodetxid`; a baixa do boleto pela API do BB liga o pagamento), `marcar`/`desmarcar` à mão (operador da conta do PIX; desmarcar só sem pagamento).
- **Importação** (`PixService::importarPix`): sai o `processarPixChave`. O PIX QR sem venda não cria mais pagamento (`processarPixCobNegocio` só trata a cobrança com venda e marca o PIX).
- **Baixa e adiantamento**: forma com `codpix` em `PagamentoTituloService::pagamentoDaForma`/`portadorPrevisto`; `codpix` nos dois FormRequests. `PixCobResource` devolve `codpix` e o wizard usa esse campo para o PIX QR (`baixaTitulosStore`).
- **Venda cancelada** (`PdvNegocioService::cancelar`): o pagamento PIX é cancelado junto, e o cartão integrado continua órfão como antes.
- **Rotas**: `GET v1/pix/{codpix}`, `POST|DELETE v1/pix/{codpix}/utilizacao`; a listagem aceita `uso=sem|usado|todos` e traz a cobrança de origem (usuário, PDV, data).
- **Front**: tela PIX (filtro Uso; Baixar títulos → `/pagamento/novo?codpix=`; Adiantamento no MgAdiantamentoDialog; Marcar/Desmarcar), `Nova.vue`, `MgBaixaTitulos` e `MgAdiantamentoDialog` aceitam o PIX (data travada, valor do PIX).

## Testes (10/10/2026)
- **Serviço** (tinker, transação desfeita): pagar com valor diferente recusa; pagar marca o PIX e lança no razão do banco; usar duas vezes recusa; desmarcar o usado por pagamento recusa; cancelar o pagamento devolve o PIX; marcar/desmarcar à mão; PIX de boleto 264526 entra usado e ligado ao pagamento do boleto 80246846; baixa de título e adiantamento com o PIX; PIX QR sem venda (cobrança e PIX simulados) não cria pagamento e o PixCobResource devolve o codpix; venda 4541351 cancelada cancela o pagamento PIX 4478814 e o PIX 259149 volta a ficar sem uso.
- **Tela** (Chrome headless no contas-dev): tela PIX desmarca/marca/desmarca (PIX 264017, que ficou sem uso para teste); Adiantamento com o PIX 264036 (R$ 10.500, pessoa 214 preenchida pelo CNPJ) gravou o pagamento 80248435 e a linha passou a mostrar o link; Baixar títulos com o PIX 264039 (R$ 2.254, título N04321369-1/1 parcial) gravou o pagamento 80248436 na data do PIX, na Bb Matriz, com o razão +2.254; títulos somando diferente do PIX desabilitam o Gravar com o aviso; PIX já usado na URL avisa.
- **Corrigido nos testes**: o diálogo da baixa mostrava o PIX duas vezes; o filtro Uso quebrava a linha.
- **Não testado**: o PIX QR pago de verdade no wizard, porque no dev não dá para pagar um QR.

## Pendente no dev
A limpeza dos pagamentos avulsos que a importação criou foi bloqueada pela permissão do Claude: o SQL está com o Fábio.

## Atenção no dev
O banco do dev não tem as cobranças da produção. Os PIX QR das vendas da produção que o dev importa do Sicredi (~400/dia) aparecem como sem uso no dev. Em produção eles acham a cobrança e pagam a venda.

Limpeza do dev feita em 10/10/2026: 1.182 pagamentos avulsos apagados, com as linhas deles no razão; os 4 que os testes amarraram ficaram. Os PIX deles continuam usados, sem pagamento. O saldo dos períodos abertos foi recalculado (8 períodos).
<!-- SECTION:NOTES:END -->
