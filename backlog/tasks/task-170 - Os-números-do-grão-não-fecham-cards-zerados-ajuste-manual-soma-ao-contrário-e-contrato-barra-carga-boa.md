---
id: TASK-170
title: >-
  Os números do grão não fecham: cards zerados, ajuste manual soma ao contrário
  e contrato barra carga boa
status: To Do
assignee: []
created_date: '2026-09-23 21:04'
updated_date: '2026-09-23 21:04'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 179000
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
- [ ] #3 Trava de excesso do contrato não conta ajuste estornado nem ignora lançamento manual, e bate com o 'Saldo a entregar' que o operador vê na carga
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
<!-- SECTION:NOTES:END -->
