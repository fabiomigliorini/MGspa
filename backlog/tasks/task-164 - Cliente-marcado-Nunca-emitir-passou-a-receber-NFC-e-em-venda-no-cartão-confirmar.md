---
id: TASK-164
title: Venda no cartão para cliente 'Nunca emitir' dá erro e não imprime o romaneio
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-23 20:38'
updated_date: '2026-10-10 19:53'
labels:
  - negocios
dependencies: []
priority: high
type: bug
ordinal: 173000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Regra: cliente marcado 'Nunca Emitir' não quer o CPF/CNPJ dele em nota nenhuma. Mas venda paga no eletrônico (cartão, PIX, boleto, integração) precisa de documento fiscal. Então, para esse cliente, a NFC-e sai para Consumidor, sem CPF/CNPJ.

Causa: quem escolhia NF-e ou NFC-e no fechamento era o front (IndexPage.vue, romaneioOuNotaVenda), por um switch no cadastro do cliente guardado no navegador. Esse cadastro não traz o campo Nota Fiscal, então o switch sempre caía no padrão e pedia NF-e; e o backend (NotaFiscalNegocioService::gerarNotaFiscalDoNegocio) recusava qualquer nota para Nunca Emitir.

Correção:
- Front: no fechamento automático, o PDV só decide se sai nota (pagamento eletrônico) ou romaneio, como antes; manda o pedido sem modelo. Transferência continua pedindo NF-e. Botões Nova NFe/NFCe continuam forçando o modelo.
- Backend: sem modelo, escolhe pelo cliente: Consumidor e Nunca Emitir em NFC-e, o resto em NF-e. Para Nunca Emitir a NFC-e nasce com codpessoa = Consumidor e cpf vazio; NF-e para Nunca Emitir continua recusada (não existe NF-e para Consumidor).
- O 'Sempre' do cadastro continua sem efeito no PDV, como sempre foi (decisão do Fábio): dinheiro = só romaneio.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Cliente Nunca Emitir pagando no eletrônico: NFC-e sai para Consumidor, sem CPF/CNPJ
- [x] #2 Pedir NF-e (55) para cliente Nunca Emitir continua recusando
- [x] #3 No fechamento, o PDV não escolhe NF-e/NFC-e: o backend decide pelo cadastro do cliente; os botões Nova NFe/NFCe continuam forçando
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): premissa errada: o cliente 'Nunca emitir' não recebe NFC-e. O servidor recusa qualquer nota para notafiscal = 9 (NotaFiscalNegocioService.php:56, 'Pessoa marcada para Nunca Emitir NFe!'); em 2026 não há NFC-e para essas pessoas (342 cadastradas, 212 ativas). Efeito real: o case 9 do romaneioOuNotaVenda (negocios/src/pages/IndexPage.vue:433) vai para romaneioOuNotaVendaPadrao(65); pagando no cartão/PIX/boleto chama novaNota, o caixa vê o erro vermelho e o romaneio não sai.
<!-- SECTION:NOTES:END -->
