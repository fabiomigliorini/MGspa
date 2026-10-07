---
id: TASK-202
title: Exportação pro Domínio adivinha a espécie da nota pela série e erra
status: To Do
assignee: []
created_date: '2026-10-07 19:52'
labels:
  - notas
dependencies: []
priority: low
type: bug
ordinal: 215000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
**Sintoma:** NF 1644 (Marcelo Alvim Soares E Outros, série 921, SET/2026) recusada na importação do Domínio: "Nota de espécie NF-e avulsa com série fora do intervalo permitido pelo fisco, conteúdo: '921'". As outras notas do mês importaram.

**Causa:** `api/app/Mg/Dominio/Arquivo/ArquivoEntrada.php` (`criaRegistroSegmento`) deduz a espécie pela série: toda NF-e modelo 55 com série >= 890 saía como 102 (NF-e avulsa). Pelo MOC, avulsa é só a série 890-899. A série 900-999 (920-969 = produtor rural pessoa física emitindo pela SEFAZ) é NF-e comum (36). Na base há notas de entrada com série 920, 921, 923 e 925.

**Paliativo aplicado em 07/10/2026 (hotfix em produção via git am):** espécie 102 só para série 890-899; as demais séries de modelo 55 saem como 36. Ainda é adivinhação pela série.

**Fix permanente:** campo com a espécie do documento na `tblnotafiscal`, preenchido na entrada da nota; a exportação do Domínio lê o campo em vez de deduzir pela série.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 tblnotafiscal guarda a espécie do documento
- [ ] #2 Exportação do Domínio usa a espécie gravada, sem regra por série
<!-- AC:END -->
