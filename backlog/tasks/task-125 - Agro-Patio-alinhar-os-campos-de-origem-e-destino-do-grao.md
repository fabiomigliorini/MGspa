---
id: TASK-125
title: 'Agro/Patio: alinhar os campos de origem e destino do grao'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-21 18:33'
updated_date: '2026-09-22 13:51'
labels:
  - agro
dependencies: []
priority: medium
type: enhancement
ordinal: 136000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
As linhas de Origem/Destino do CargaForm nao alinham: os selects com :rules ganham a classe q-field--with-bottom (padding-bottom 20px do Quasar) e o select de tipo (Origem/Destino), que nao tem rules, fica 10px mais baixo que os vizinhos por causa do items-center. Some-se a isso o botao X centralizado na altura cheia da linha, o ritmo diferente entre as colunas (q-mb-xs na origem, q-mb-sm no destino), a legenda 'Saldo a entregar' indentada com q-pl-sm (nao bate com 'Soma:') e o campo de talhao com borda tracejada por ser readonly, parecendo desabilitado ao lado dos outros. Primeiro passo do refino geral da UI do agro.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Causa: Quasar aplica q-field--with-bottom (padding-bottom 20px) so nos campos com :rules. Num .row items-center, quem nao tem rules (SelectContaTipo) centraliza 10px abaixo dos vizinhos.

Feito em CargaForm.vue: bottom-slots nos dois SelectContaTipo (reserva o mesmo espaco, sem mudar a altura da linha); classe .ponto-remover (margin-bottom 20px) no botao X pra centralizar na caixa do campo e nao na linha inteira; destino de q-mb-sm para q-mb-xs (mesmo ritmo da origem); legenda 'Saldo a entregar' sem q-pl-sm, alinhada com 'Soma:' e com a borda dos campos.

Em SelectTalhao.vue: borda solida no campo readonly (o tracejado do Quasar fazia parecer desabilitado ao lado dos selects).

Pendente de refino nesta tela: o rotulo do talhao trunca na largura atual (col-4 do tipo + col da entidade).
<!-- SECTION:NOTES:END -->
