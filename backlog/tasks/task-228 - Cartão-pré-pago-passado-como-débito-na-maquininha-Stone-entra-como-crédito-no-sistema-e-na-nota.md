---
id: TASK-228
title: >-
  Cartão pré-pago passado como débito na maquininha Stone entra como crédito no
  sistema e na nota
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-16 15:47'
updated_date: '2026-10-16 16:26'
labels:
  - api
dependencies: []
priority: high
type: bug
ordinal: 239000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Relatado pelo Fábio em 16/10/2026 conferindo o período da maquineta Stone (POS PB09243472712, Andre Maggi Caixa 01) de 14/10 17:15 a 15/10 17:32: total bate (23 · 473,10), mas a Stone mostra 16 débitos (280,25) e 7 créditos (192,85), e o sistema 10 débitos (195,58) e 13 créditos. As 6 a mais no crédito (1,00 · 4,00 · 9,99 · 29,97 · 10,90 Mastercard e 28,81 Visa = 84,67) são cartão pré-pago.

Causa: a Stone manda o tipo do cartão em account_funding_source (debit, credit, prepaid, voucher) e PagarMeService::cartaoDoPedido traduz prepaid para Cartão de Crédito (meio 3). Para a Stone pré-pago é débito em tudo: relatório de vendas, recebimento D+1 e taxa de débito (venda de 4,00 em 15/10 10:50 recebida em 16/10 com 0,05 de taxa).

Tamanho (SQL em produção, 16/10): todo pré-pago da Stone foi gravado como crédito — ago 392 (7.081,45), set 311 (7.142,10), out até 16/10 192 (4.457,16), mais da metade do que aparece como crédito. Desde o go-live (12/10): 52 cobranças em 7 períodos da maquineta (6 já conferidos) e 51 notas emitidas com tPag 03 (970,31).

Onde o meio errado aparece: período da maquineta (débito × crédito trocados), nota (tPag = tblpagamento.meio, via NotaFiscalNegocioService), alerta de desconto dos 5% à vista (OcorrenciaPdvService::MEIOS_AVISTA inclui débito e não crédito) e conciliação DIMP (DimpConciliacaoService compara meio do pagamento com tPag das notas).

Saurus/Safra conferida e coerente: lá o tipo é o que o operador escolhe e a Saurus devolve o mesmo (nunca diverge em ~200 mil cobranças no dev); 4 períodos da Safra (13 a 15/10, 188 vendas) batem com o relatório da Safrapay em débito, crédito, parcelado e bandeira. A regra das duas integrações fica: o meio é o que a adquirente mostra.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Cartão pré-pago da Stone entra como débito no pagamento, no período da maquineta e na nota
- [x] #2 Histórico corrigido: toda cobrança pré-paga da Stone gravada como crédito passa a débito, desde o começo (decisão do Fábio em 16/10: todo o histórico; as notas já emitidas não mudam)
- [x] #3 Voucher entra como Outros (99) na Stone e na Saurus, integrada e manual (decisão do Fábio em 16/10)
- [x] #4 Venda da Stone que fecha sozinha às vezes grava o cartão como Outros, sem bandeira, e fora do período da maquineta; só vira Crédito Mastercard e entra no período depois que alguém abre a cobrança e aperta Consultar (relatado pelo Fábio em 16/10, venda #04639340 de 13/10 13:02, R$ 1,25)
- [x] #5 Hora em que o cartão passou gravada no fuso certo: Stone manda hora de São Paulo sem fuso (ficava +1h), Saurus manda UTC (ficava +4h); a baixa de títulos e o adiantamento no cartão usam essa hora como data do pagamento e, à noite, caíam no dia seguinte (decisão do Fábio em 16/10: entra nesta task)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Venda #04639340 (13/10, R$ 1,25), linha do tempo em produção (16/10):
- 13:02:26 tatiane cria o pedido Stone 638154
- 13:02:33 a consulta automática do PDV (a cada 3s) acha o pedido pago e grava o pagamento 80250366, mas nenhuma cobrança da Stone (tblpagarmepagamento) foi gravada: cartaoDoPedido cai em Outros (99), sem bandeira; Outros não é cartão, fica fora do período da maquineta
- 13:02:41 venda fecha e a nota 3594103 sai com tPag 99
- o webhook nunca gravou a cobrança (não há linha sem usuário)
- 14/10 16:36:41 evilin aperta Consultar: grava a cobrança 599925 (pré-pago) e o pagamento vira crédito Mastercard
Stone ainda como Outros em produção: set 11 (183,01), out 9 (1.226,35); 1 ou 2 por mês antes.
Achado à parte (não é deste sintoma, a relatar): tblpagarmepagamento.transacao fica 1h adiantada desde jan/2026; pagar() (baixa/adiantamento) usa essa hora no pagamento.

Causa de fundo do Outros (16/10): o webhook da Stone está recusado desde a migração para o api/. No laravel antigo a rota pagar-me/webhook ficava num grupo público; na importação (a8901f4b8, 23/05/2026) ela caiu dentro do Route::middleware(['auth:api']) da linha 660 de api/routes/api.php, sem withoutMiddleware. No dev, de jun/2026 em diante nenhuma cobrança da Stone foi gravada pelo webhook (todas pela consulta do PDV); antes, a maioria vinha dele. Sem o webhook, quando a consulta automática do PDV acha o pedido pago antes de a Stone mandar a transação (charge sem last_transaction), o pagamento nasce Outros e ninguém corrige.
Hora da Stone no dev: até jul/2025 UTC com Z (certo); ago-nov/2025 às vezes UTC sem Z (+4h); desde jan/2026 hora de São Paulo sem fuso (+1h), no webhook e na consulta. Saurus: UTC (+4h).
A rota pix/webhook (linha 824) está no mesmo grupo com auth: outro assunto (PIX), a relatar.

Script de dados: api/database/pagamento_cartao_stone.sql (parte 1: Stone gravado como Outros, 29 em produção, todos antes do go-live; 6 com a cobrança no banco, 23 só com o débito/crédito do pedido, sem bandeira; parte 2: pré-pago como crédito). Testado no dev com ROLLBACK: 8 Outros corrigidos, 0 restantes; rodado de novo, nada muda.

16/10: o Fábio rodou em produção a parte 1 (Outros): 29 pagamentos alterados, nenhum Stone como Outros sobrando.

Hora antiga (decisão do Fábio em 16/10): só a baixa/adiantamento 80253034 (Safra, CEN Echo, 52,95, 15/10) foi afetada desde 11/10: gravada 18:34, certa 14:34, mesmo dia e mesmo período (60). Corrige só esse pagamento; a hora das cobranças antigas (tblpagarmepagamento/tblsauruspagamento) fica como está.

16/10: o Fábio rodou em produção o UPDATE do 80253034 (1 linha).
<!-- SECTION:NOTES:END -->
