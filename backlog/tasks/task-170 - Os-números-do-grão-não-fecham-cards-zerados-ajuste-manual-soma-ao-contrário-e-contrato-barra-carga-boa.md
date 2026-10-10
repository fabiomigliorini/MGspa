---
id: TASK-170
title: >-
  Os números do grão não fecham: cards zerados, ajuste manual soma ao contrário
  e contrato barra carga boa
status: Done
assignee:
  - '@eduardo'
created_date: '2026-09-23 21:04'
updated_date: '2026-10-01 19:07'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 202000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
SINTOMA: os números do grão não fecham. No extrato, os cards 'A colher' e 'Disponível p/
negociar' mostram sempre zero. Um ajuste manual lançado como ORIGEM (retirada de silo) SOMA ao
saldo em vez de baixar. E a trava de excesso de carregamento barra carga legítima, com um
número que não bate com o 'Saldo a entregar' que o operador vê no formulário da carga.

São três divergências da MESMA conta (a razão de grãos, MovimentoGrao) em lugares diferentes;
a correção é alinhar tudo numa fonte única, como a antiga TASK-127 já pedia.

Consolida as antigas TASK-126, TASK-127 e TASK-128 (arquivadas).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Cards 'A colher' e 'Disponível p/ negociar' do extrato mostram valor de verdade, não zero
- [ ] #2 Ajuste manual lançado como ORIGEM baixa o saldo em vez de somar
- [ ] #3 Cancelar uma carga nunca é barrado pela trava do contrato
- [ ] #4 Entregue da safra separa venda de compra
- [ ] #5 Contrato entregue a mais mostra o excesso em vez de zerar o saldo
- [ ] #6 Estoque & Extrato mostra todos os lançamentos e todos os contratos, silos e talhões nos selects
- [ ] #7 Colhido é o mesmo no Início, na Safra, na Fazenda e na Cultura
- [ ] #8 Ajuste manual grava exatamente o que o operador informou
- [ ] #9 Carga que passa do saldo do contrato é aceita (o caminhão completa a carga); o pátio avisa quanto passa, com o mesmo 'Saldo a entregar' da tela (ajuste manual conta, estornado não)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Detalhe técnico das tasks consolidadas (2026-09-23)

### TASK-126 — dois KPIs leem campos que a API não devolve

ExtratoPage.vue le kpis.acolherkg e kpis.disponivelkg, mas GET v1/safra/{cod}/comercial (SafraService::resumoComercial) nao devolve nenhum dos dois: os campos existentes sao 'disponivel' (em SACAS, ja arredondado) e nao ha 'acolher'. Resultado: os cards 'A colher' e 'Disponivel p/ negociar' mostram sempre 0 sc e - kg. Os outros dois cards (estoquekg/entreguekg) estao certos. A SafraDetailPage consome o MESMO endpoint com os nomes corretos (estoquesc, entreguesc, disponivel), entao o desalinhamento e so da ExtratoPage. Decidir: (a) corrigir o front para 'disponivel' (sacas) e (b) criar 'acolherkg' no backend (producao estimada - colhido) ou remover o card.

### TASK-127 — trava de excesso do contrato conta errado

CargaService::validarOverloadContrato soma o ja entregue assim: MovimentoGrao::where(contatipo CONTRATO)->where(papel DESTINO)->when(codcarga, fn => where('codcarga','!=',$carga->codcarga))->sum('liquido'). Dois defeitos na mesma consulta: 1) falta whereNull('inativo') - um ajuste manual ESTORNADO continua contando como entregue e bloqueia carga legitima; 2) o 'codcarga != X' e SQL de tres valores: linha com codcarga NULL (todo ajuste manual) avalia NULL e fica de fora - ou seja, entregas manuais deixam de contar assim que a carga ja tem codcarga, e contam quando a carga ainda e nova. O mesmo contrato pode aprovar/reprovar dependendo de a carga ja ter sido sincronizada. Alem disso o numero diverge do 'Saldo a entregar' que o operador ve no CargaForm, que vem de ContratoResource::saldokg (carregadokg = withSum com whereNull('inativo') e TODOS os papeis). Alinhar as duas contas numa fonte unica.

### TASK-128 — ajuste manual ignora o papel e sempre SOMA

No extrato automatico o sinal vem do par papel+contatipo (CargaService::sinal: UNIDADE +destino/-origem; PLANTIO e CONTRATO sempre +). No ajuste MANUAL nao: MovimentoGraoService::lancarManual grava liquido = bruto - desconto e nunca olha o papel. A tela (ExtratoPage) pede papel ORIGEM/DESTINO e mostra 'Liquido = bruto - desconto', entao um ajuste lancado como ORIGEM/UNIDADE de 1.000 kg (retirada de silo) ACRESCENTA 1.000 kg ao saldo em vez de baixar. Para subtrair o operador precisa adivinhar que tem de digitar bruto negativo. Decidir: aplicar o mesmo sinal do automatico no lancarManual (e migrar os lancamentos ja gravados), ou remover o campo papel do form e deixar explicito 'entrada/saida'.

29/09/2026 — regra aceita pelo negócio (doc-3): o contrato PODE ser carregado além do saldo; o caminhão completa a carga para aproveitar o frete e o comprador aceita. O servidor deixa de bloquear (hoje validarOverloadContrato recusa com 422, cenários C1 e E3 da bateria) e o pátio passa a AVISAR quanto passa do contratado. Saíram os critérios 'dois caminhões juntos não passam do contratado' e 'reativar respeita o teto'; a trava com lock de contrato do plano (Fase 4) deixa de ser necessária. Continuam: cancelar nunca é barrado (C5) e a tela mostrar o excesso (C8).

30/09/2026 — implementado, aguardando validação. Backend: validarOverloadContrato removido (D12: aceita e avisa; cancelar nunca barra); lancarManual com sinal por papel (CargaService::sinal público), retirada de silo sem desconto, só líquido vira bruto, FKs de outro tipo zeradas; ContratoResource.saldokg sem piso; resumoComercial com entregue só VENDA, recebidokg (COMPRA), acolherkg e disponivelkg; colhido de Cultura/Fazenda na mesma regra da Safra (safra do movimento, plantio/safra ativos). Front: extrato e selects buscam todas as páginas; form de ajuste com Entrada/Retirada do silo; contrato mostra 'Entregue a mais'; pátio mostra 'passa X kg do contratado'; safra mostra recebido de compra; Início usa o colhido do servidor quando online. PROD: api/database/agro_movimento_manual_sinal.sql (D4, conferir a lista antes do UPDATE). Bateria todos: OK 62 · FALHA 32 (era 57/38), nenhuma FALHA nova; A6, C1–C9 OK.
<!-- SECTION:NOTES:END -->
