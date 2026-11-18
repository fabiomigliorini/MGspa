---
id: TASK-280
title: >-
  Toda nota de serviço da FDF pede para digitar de novo as alíquotas do Simples
  do mês
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-18 10:17'
updated_date: '2026-11-18 21:19'
labels:
  - notas
dependencies: []
priority: low
type: feature
ordinal: 291000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje cada nota de serviço (TASK-279) pede dois percentuais que mudam todo mês com o faturamento dos últimos 12 meses do Simples, iguais em todas as notas do mês:
- alíquota efetiva do ISS no Simples (issaliquota; só vai na DPS com ISS retido — E0625);
- % aproximado total dos tributos do Simples (tributospercentual / pTotTribSN; obrigatório para ME/EPP — E0712).

Pedido do Fábio (18/11/2026): informar esses percentuais uma vez por mês num cadastro da filial e a nota puxar automaticamente, pela filial e pelo mês da competência.

Onde mexe: cadastro da filial no pessoas (lista de meses com os percentuais), api (tabela por filial e mês + endpoint), e o formulário da nota de serviço no notas (MgNotaFiscalServicoForm / notaFiscalServicoStore), que hoje começa com os dois campos vazios.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Cadastro na filial (pessoas) dos percentuais por mês: alíquota do ISS no Simples e % aproximado dos tributos do Simples
- [x] #2 Nota de serviço preenche os dois percentuais sozinha pela filial e pelo mês da competência (inclusive ao trocar filial ou competência)
- [x] #3 Definido com o Fábio o que acontece quando o mês da competência não tem percentual cadastrado e se a nota deixa alterar o valor puxado
- [x] #4 Duplicar pega os percentuais do cadastro do mês da nova competência, e Enviar recusa a nota com percentual faltando
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado (18/11/2026):

- Banco: tblfilialsimples (codfilial, competencia = 1º dia do mês, tributospercentual numeric(4,2), issaliquota numeric(5,2), auditoria), um mês por filial (uk codfilial+competencia). api/database/filial_simples.sql. Go-live: rodar o SQL ANTES do deploy — o Duplicar da nota de serviço e o formulário já leem a tabela. Rodado no banco de dev.
- API (Mg\Filial\FilialSimples*): GET/POST v1/filial/{codfilial}/simples, PUT/DELETE v1/filial/{codfilial}/simples/{codfilialsimples} e GET v1/filial/{codfilial}/simples/{AAAA-MM-DD} (o mês da competência; mês sem cadastro devolve data null). A competência é normalizada para o 1º dia; mês repetido dá 409 'Já existe percentual para MM/AAAA'. Mesmos limites da nota: Tributos > 0 e até 99,99; ISS > 0, até 5 e nunca acima dos Tributos. Sem permissão própria, igual ao cadastro da filial.
- Pessoas: card 'Simples Nacional (NFS-e)' na coluna lateral do detalhe da filial (/filial/{codfilial}), só nas filiais que emitem NFS-e: lista dos meses (Tributos · ISS) com editar/excluir e dialog de novo mês (Competência, Tributos, Alíquota ISS).
- Notas: a nota nova puxa os dois percentuais do mês da competência; trocar a filial ou o mês puxa de novo (mês sem cadastro esvazia); trocar só o dia não busca. Abrir nota salva só consulta, sem mudar o que foi gravado.
- AC #3, decisões do Fábio (18/11/2026): a nota deixa trocar o valor puxado e avisa ('No cadastro da filial: 6,00%'), como a retenção da TASK-282; mês sem cadastro deixa os campos vazios com o aviso 'Mês sem percentual no cadastro da filial' e dá para digitar à mão.
- Duplicar (decisão do Fábio): os percentuais vêm do cadastro do mês da nova competência, nunca da nota original; sem o mês cadastrado ficam vazios. O Enviar recusa a nota com o % de Tributos faltando (filial do Simples) ou com ISS retido sem alíquota, com a mensagem 'Falta o % de Tributos do Simples: cadastre o mês na filial ou informe na nota.' — antes de numerar, sem virar ERR.

Ajustes do teste (18/11/2026):
- Pedido do Fábio: a Competência do cadastro se digita só como MM/AAAA (MgInput com máscara; a API recebe o 1º dia do mês). O novo mês vem com o seguinte ao último cadastrado; sem nenhum, o mês atual.
- Bug achado no teste: na nota, ao trocar para um mês sem cadastro, os campos esvaziavam e o erro 'Informe o percentual' escondia o aviso. Agora o erro do campo vazio já diz 'Mês sem percentual no cadastro da filial' (Tributos, e a Alíquota quando há retenção).
- Testado: API pelo PHP com rollback (criar, mês repetido 409, editar, outra filial 404, mês do dia 30, validação, Duplicar retido/não retido/sem cadastro, Enviar recusando sem numerar); telas no Chrome headless com a API simulada (card do pessoas: padrão do mês, MM/AAAA inválido, ISS > Tributos, salvar, 409, excluir; nota: nova preenche, mês sem cadastro esvazia e avisa, troca só do dia não busca, divergência, troca rápida de mês, edição mantém o gravado e avisa).
<!-- SECTION:NOTES:END -->
