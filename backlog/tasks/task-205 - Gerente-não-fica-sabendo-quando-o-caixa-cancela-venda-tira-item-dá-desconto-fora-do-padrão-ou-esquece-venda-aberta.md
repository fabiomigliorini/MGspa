---
id: TASK-205
title: >-
  Gerente não fica sabendo quando o caixa cancela venda, tira item, dá desconto
  fora do padrão ou esquece venda aberta
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-09 00:20'
updated_date: '2026-10-09 01:36'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 217000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio em 08/10/2026. O caixa consegue fazer o registro bater com a gaveta e ninguém fica sabendo: cancela venda paga em dinheiro, tira item ou diminui quantidade depois de cobrar, remove pagamento, dá desconto acima do padrão, ou deixa a venda aberta e segue para a próxima. Hoje o banco não guarda boa parte disso (quem/quando cancelou o negócio; diminuição de quantidade e preço sobrescrevem a linha; pagamento removido antes de fechar é DELETE físico).

Solução: livro de ocorrências (tblocorrencia, genérica, só eventos a conferir — não é auditoria geral). Caixa informa o motivo na hora (combo fixo sem pré-seleção; Outro exige texto). Só vale para PDV monitorado: tblpdv.monitoramento (data 'monitorar a partir de'; nula = não monitora) e tblpdv.minutosesquecido (padrão 120). Gerente confere linha a linha numa tela do contas.

Plano: /home/usuario/.claude/plans/precisamos-fazer-um-dashboard-velvety-elephant.md (M1–M6). Painel de caixas/maquinetas fica na TASK-203. Parecer por LLM em task separada.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tabela tblocorrencia + colunas monitoramento/minutosesquecido no PDV (api/database/ocorrencia.sql, idempotente)
- [ ] #2 Negócio cancelado vira ocorrência com quem, quando, justificativa e pagamentos que tinha
- [ ] #3 Pagamento e vale estornados viram ocorrência (sem duplicar com o cancelamento do negócio)
- [ ] #4 Desconto acima do permitido (maior entre desconto do cadastro e 5% sobre a parte à vista pix/dinheiro/débito) vira ocorrência no fechamento
- [ ] #5 No PDV monitorado, remover item, diminuir quantidade, baixar preço e excluir pagamento pedem motivo e viram ocorrência, inclusive offline
- [ ] #6 Juntar bipes repetidos e aumentar quantidade não geram ocorrência
- [ ] #7 Negócio aberto com itens parado além do tempo do PDV vira ocorrência 'esquecido' (agendado); ao fechar o caixa, o que ainda estiver aberto vira ocorrência e o caixa fecha
- [ ] #8 Cadastro do PDV permite marcar a data de monitoramento e os minutos para esquecido
- [ ] #9 Tela Ocorrências no contas: gerente vê as da filial dele, filtra, ordena por valor e confere com observação
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado (08/10/2026), aguardando validação do Fábio. Plano: M1–M6.

Backend: api/database/ocorrencia.sql (rodado 2x no dev; entra no go-live depois do maquineta_periodo.sql — doc-3); Mg\Ocorrencia\{Ocorrencia,OcorrenciaService,OcorrenciaController}; ganchos em PdvNegocioService (negocioAberto/negocioFechado importam as do PDV; fechar → desconto acima; cancelar → negócio cancelado com foto dos pagamentos), PagamentoTituloService::estornar (pagamento/vale estornado — o cancelamento do negócio não passa por ele, sem duplicar), PortadorPeriodoService::fecharCaixa (esquecidos da gaveta), comando ocorrencia:negocio-esquecido a cada 10 min; NegocioResource devolve ocorrencias; PdvService::update recusa minutosesquecido < 10. Rotas GET v1/ocorrencia, POST/DELETE v1/ocorrencia/{id}/conferir.

PDV (negocios): getter monitorado (pdv.monitoramento vem do PUT dispositivo, na sincronização); OcorrenciaMotivoDialog (combo sem pré-seleção, Outro exige texto) em excluir item, '−', editar com quantidade/preço menor e excluir pagamento; juntar bipes e '+' não geram; ocorrências locais não enviadas sobrevivem à resposta do servidor. Cadastro do PDV: Monitorar a partir de + minutos.

contas: menu Movimento → Ocorrências (/ocorrencia), filtros no drawer, conferir com observação, reabrir.

Testado no dev com rollback (tinker): 12 esquecidos na 1ª passada e 0 na 2ª; desconto forjado de 20% → tipo 13; PUT do negócio com ocorrência → 1 linha ligada ao item, reenvio não duplica, motivo inválido recusado; cancelamento → tipo 10 com quem/quando; listagem/conferir. Telas não testadas no navegador.

Revisão exaustiva (08/10/2026 à noite):
- Bateria de backend no dev com rollback (monitorado, importação do PDV, cancelamento aberto/fechado, desconto, esquecidos, fechamento de caixa, estorno de recebimento, listagem/permissões, cadastro do PDV): 0 falhas depois das correções.
- Corrigido: data de criação inválida ou adiantada vinda do PDV derrubava a sincronização (agora vira o momento atual); salvar o PDV com data vazia dava erro de SQL; a ocorrência agora é da filial do PDV (loja do gerente), não da filial do estoque do negócio; texto do vazio na tela.
- HTTP via kernel: listagem, validações 422, conferir/reabrir, 404.
- Navegador headless: contas /ocorrencia (conferir com Enter, filtros, reabrir, ordem por valor, celular sem rolagem lateral) ok; PDV: '−' pede motivo e recusa sem motivo, '+' não pede, excluir item com Outro exige texto.
<!-- SECTION:NOTES:END -->
