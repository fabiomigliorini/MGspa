---
id: TASK-128
title: 'Agro/Extrato: ajuste manual ignora o papel e sempre SOMA no saldo'
status: To Do
assignee: []
created_date: '2026-09-21 21:33'
updated_date: '2026-09-23 21:04'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 139000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No extrato automatico o sinal vem do par papel+contatipo (CargaService::sinal: UNIDADE +destino/-origem; PLANTIO e CONTRATO sempre +). No ajuste MANUAL nao: MovimentoGraoService::lancarManual grava liquido = bruto - desconto e nunca olha o papel. A tela (ExtratoPage) pede papel ORIGEM/DESTINO e mostra 'Liquido = bruto - desconto', entao um ajuste lancado como ORIGEM/UNIDADE de 1.000 kg (retirada de silo) ACRESCENTA 1.000 kg ao saldo em vez de baixar. Para subtrair o operador precisa adivinhar que tem de digitar bruto negativo. Decidir: aplicar o mesmo sinal do automatico no lancarManual (e migrar os lancamentos ja gravados), ou remover o campo papel do form e deixar explicito 'entrada/saida'.
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-23 21:04
---
Dobrada na TASK-170 (regra 3 do CLAUDE.md): virou o critério de aceite #2 da task-mãe, e o texto desta descrição está preservado nas notas da TASK-170. Arquivada para sair do board — não foi descartada.
---
<!-- COMMENTS:END -->
