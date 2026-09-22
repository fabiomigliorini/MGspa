---
id: TASK-139
title: 'Agro: ficha do romaneio somente leitura e 2a via do ticket'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-22 12:23'
updated_date: '2026-09-22 12:41'
labels:
  - agro
dependencies:
  - TASK-137
priority: medium
type: feature
ordinal: 149000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Tela /cargas/:codcarga - ficha completa da carga, SOMENTE LEITURA. Abrir carga finalizada no form editavel do Patio convida edicao acidental e depende do cache offline ter aquela carga.

Escopo:
- GET v1/carga/{codcarga} - rota JA EXISTE (routes/api.php) e devolve CargaResource com o WITH completo, incluindo classificacao. Nada a fazer no backend alem do que a TASK-137 ja traz.
- CargaDetailPage.vue: NAO copiar o NotaFiscalViewPage (enorme, cheio de abas de NFe). Copiar o vocabulario do CargaResumo.vue - q-card flat bordered empilhados em q-page q-pa-md com max-width 1086px margin auto (padrao do ExtratoPage).
  Secoes: cabecalho (sentido, n, etapa, chegada, banner vermelho se cancelada) | pesagem (pbt/tara/bruto/desconto/liquido + sacas) | caminhao e motorista | safra/cultura | origens e destinos (rotulo + kg + NF) | classificacao (q-markup-table dense: parametro, leitura %, tolerancia, desconto kg) | auditoria (MgInfoCriacao) | botoes Imprimir romaneio / Voltar.

2a via do romaneio - o ponto que nao e obvio:
normalizarCargaDoServidor() NAO resolve. imprimirTicket() nao recebe uma carga, recebe um objeto de ticket ja achatado que o CargaForm monta consultando o Dexie (plantioPorId, veiculoPorId, itensCarga).
Solucao: funcao pura nova em agro/src/utils/ticket.js, ao lado de imprimirTicket:
  export function ticketDoServidor(c)
Le c.Safra.safra, c.Safra.Cultura.cultura/pesosaca, c.Veiculo.veiculo, c.CargaPontoS[].rotulo/.liquido, c.classificacao[].ParametroClassificacao.parametroclassificacao e a fazenda de Plantio.Fazenda (relacao acrescentada na TASK-137).
Depois e so imprimirTicket(ticketDoServidor(carga)).
NAO refatorar o CargaForm para usar isso - fora de escopo.

Criterio de aceite forte: imprimir o romaneio daqui e pelo CargaForm na MESMA carga tem que sair identico.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado.

Arquivos:
- agro/src/pages/CargaDetailPage.vue (novo): cabecalho + progresso de etapas, pesagem, caminhao/motorista, origens, destinos, classificacao (q-markup-table com tolerancia), observacao, MgInfoCriacao, FABs Voltar e Imprimir. Banner vermelho quando cancelada. Visual no vocabulario do CargaResumo, nao do NotaFiscalViewPage.
- agro/src/utils/ticket.js: + ticketDoServidor(c), funcao pura ao lado de imprimirTicket.

DOIS BUGS REAIS ACHADOS NA VERIFICACAO (valem pro resto do app):

1) GET v1/carga/{id} NAO vem embrulhado em "data", ao contrario do resto dos Resources.
   O JsonResource so embrulha quando o array ainda NAO tem a chave do wrapper
   (haveDefaultWrapperAndDataIsUnwrapped) -- e tblcarga TEM uma coluna `data`
   (chegada no patio). O idioma habitual "data.data ?? data" pegaria o TIMESTAMP
   e a tela inteira quebrava. Guarda usada: data?.codcarga != null ? data : data?.data

2) Relacao aninhada DENTRO de um model sai em snake_case/minusculo:
   - carga.Safra e PascalCase (o Resource poe na mao), mas Safra.cultura e minusculo
   - CargaPontoS[].Plantio e PascalCase, mas Plantio.fazenda e minusculo
   Corrigido em CargaDetailPage, CargasPage, stores/cargaListagem.js e utils/ticket.js.
   (Bonus: no show(), Plantio.talhao vem como OBJETO, nao string -- a relacao Talhao
   colide com a coluna homonima. Nao afeta aqui porque o rotulo vem pronto do backend.)

Verificado com o payload REAL do endpoint (carga #3), rodando ticketDoServidor em node:
titulo=ROMANEIO DE RECEBIMENTO, numero=3, fazenda=Renascer, cultura=Soja,
safra=Soja 2026/2027, placa=MAB6966, motorista OK, pesosaca=60, liquido=39462.532,
sacas=657.7, itens=[01 - Soja boa, 39463 kg], classificacao=[Impureza 8, Umidade 17,
Avariados 4, Quebrados 3], assinaturas de recebimento. Tudo resolvido.

FALTA A VALIDACAO VISUAL: comparar o romaneio impresso aqui com o impresso pelo
CargaForm na MESMA carga -- devem sair identicos.
<!-- SECTION:NOTES:END -->
