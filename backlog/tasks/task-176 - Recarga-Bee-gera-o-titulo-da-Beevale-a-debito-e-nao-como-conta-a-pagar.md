---
id: TASK-176
title: 'Recarga Bee gera o titulo da Beevale a debito, e nao como conta a pagar'
status: In Progress
assignee:
  - '@eduardo'
created_date: '2026-09-26 14:11'
updated_date: '2026-09-26 14:43'
labels:
  - pessoas
dependencies: []
priority: high
type: bug
ordinal: 190000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Ao gerar o lote de recarga do cartao Bee (pessoas > RH > Recarga Bee), o titulo da Beevale sai A DEBITO (tipo 120 Adto Fornecedor), quando deveria ser conta a pagar (credito). O Financeiro nao consegue pagar pela tela de Liquidacao: baixar esse titulo vira RECEBIMENTO (entrada no banco onde houve saida), e a Beevale aparece devendo o valor do lote para a empresa.

Causa: BeeRecargaService::gerar (api/app/Mg/Rh/BeeRecargaService.php:34 e :263) cria o titulo com CODTIPOTITULO_ADTO = 120. O TituloService::criar lanca debito/credito pelo cadastro do tipo (tbltipotitulo.credito), e o 120 e' debito. Alem disso o trigger fntbltituloai grava a implantacao com o tbltipotitulo.codtipomovimentotitulo, que no 120 e' 600 'Liquidacao' -- sem portador, porque o DialogRecargaAvulsa sempre manda codportador null. Ou seja: o sistema registra um pagamento a Beevale no instante em que o RH gera o lote, sem banco nenhum.

No banco, Adto Fornecedor = 'ja paguei adiantado': o Financeiro cria na hora do PIX, com o banco como portador, e compensa depois com uma Duplicata A Pagar (927). Ex.: vale-alimentacao Machado - Centro, conta 312 (titulos 643485 x 644251 na liquidacao 154475; 647481 x 648183 na 155562) e os lancamentos manuais da Beevale de 30/07/2026 (650372 e 650373, com portador e codigo PIX). A automacao copiou so o lado do pagamento, mas o RH gera o lote ANTES de pagar. Nao da pra corrigir editando: TituloService::atualizar recusa trocar o tipo entre debito e credito.

Decisao (26/09/2026): o lote passa a gerar Duplicata A Pagar (927), a credito, nascendo em aberto (implantacao 100); o Financeiro paga pela Liquidacao. Portador e vencimento continuam como estavam (nulo / data de hoje).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Lote novo gera o titulo da Beevale como Duplicata A Pagar (credito), sem Liquidacao na criacao
- [ ] #2 Telas e mensagens da recarga deixam de chamar o titulo de adiantamento
- [ ] #3 Lotes ja gerados em producao corrigidos junto com o Financeiro
- [ ] #4 Movimento do titulo mostra quem gerou a recarga (Criado por no app de contas)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Lotes ja gerados em PRODUCAO continuam com o titulo a debito (o banco de dev so tem copia de prod ate ~03/08/2026, nao da pra ver daqui). NAO rodar nada sem o Financeiro validar a lista e sem autorizacao.

Levantamento (so leitura, em producao):
  SET search_path TO mgsis, public;
  SELECT b.codbeerecarga, b.codperiodo, b.status, b.valor, t.codtitulo, t.debito, t.saldo, t.codportador, t.estornado,
         (SELECT string_agg(m.codtipomovimentotitulo || COALESCE(' liq ' || m.codliquidacaotitulo, ''), ', ')
            FROM tblmovimentotitulo m WHERE m.codtitulo = t.codtitulo) AS movimentos
    FROM tblbeerecarga b JOIN tbltitulo t ON t.codtitulo = b.codtitulo
   WHERE b.inativo IS NULL AND t.codtipotitulo = 120 ORDER BY b.codbeerecarga;

Por situacao:
- Titulo intocado (so o movimento 600 da criacao): numa transacao, via tinker, TituloService::estornar no antigo + TituloService::criar com tipo 927 e os mesmos filial/pessoa/conta/valor/datas/observacao + apontar tblbeerecarga.codtitulo para o novo. Preserva lote e itens (a planilha ja foi para a Bee; regerar mudaria o numero do lote, e lote confirmado nem pode ser inativado).
- Titulo ja baixado como recebimento: estornar a liquidacao antes e seguir como acima.
- Financeiro ja compensou com titulo proprio: caso a caso com ele.

26/09/2026 - Implementado (aguardando validacao, sem commit):
- BeeRecargaService: CODTIPOTITULO_ADTO = 120 virou CODTIPOTITULO_PAGAR = 927 (Duplicata A Pagar), com o porque no comentario da constante. Portador e vencimento sem mudanca (nulo / hoje).
- Texto "adiantamento" trocado por "titulo a pagar da Beevale" nos comentarios do BeeRecargaService/BeeRecarga, na mensagem do inativar, no dialogo de inativar e no tooltip do portador (RecargaDashboard) e no tooltip da observacao (DialogRecargaAvulsa). BeeRecargaService:85 e DialogRecargaAvulsa:340 falam do adiantamento ao COLABORADOR - nao mexidos.
- DialogRecargaAvulsa: os 2 q-input (busca e observacao) viraram MgInput (regra do CLAUDE.md). O MgInput nao repassa slot default, entao o tooltip da observacao foi para uma div em volta do campo (unico q-input com tooltip dentro no projeto inteiro).
- Teste no banco de dev, com rollback: lote avulso de 10,00 (periodo 9, empresa 1) gerou titulo tipo 927, credito 10,00, saldo -10,00, movimento 100 Implantacao (antes era 600 Liquidacao). Inativar o lote: movimento 900, saldo 0, estornado. Nada gravado.
- Lotes antigos com titulo 120 continuam inativando normalmente (estornar funciona igual).

26/09/2026 - Pedido na validacao: o movimento de implantacao aparecia no app de contas (Titulo > Movimentos) com o "Criado por" em branco. Causa: quem grava a implantacao e' a trigger fntbltituloai, que nao sabe o usuario (e' assim em todo titulo criado fora do MGsis - milhares por mes, nao so a recarga).
- BeeRecargaService::gerar: depois do TituloService::criar, carimba codusuariocriacao/codusuarioalteracao do movimento com o criador do titulo (quem gerou a recarga). DB::table e nao o model, para o Eloquent nao carimbar `alteracao`.
- Teste com rollback (usuario 302245 simulado): implantacao saiu com codusuariocriacao = codusuarioalteracao = 302245 e alteracao = criacao.
- Correcao de producao (criterio #3): via tinker nao ha usuario logado, entao o titulo novo e o movimento dele devem receber o codusuariocriacao do proprio lote (tblbeerecarga.codusuariocriacao), para continuar mostrando quem fez a recarga.
<!-- SECTION:NOTES:END -->
