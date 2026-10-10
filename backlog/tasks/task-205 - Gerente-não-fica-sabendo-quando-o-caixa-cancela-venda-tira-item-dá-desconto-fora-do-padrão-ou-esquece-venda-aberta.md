---
id: TASK-205
title: >-
  Gerente não fica sabendo quando o caixa cancela venda, tira item, dá desconto
  fora do padrão ou esquece venda aberta
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-09 00:20'
updated_date: '2026-10-10 00:44'
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

Solução: livro de ocorrências (tblocorrencia, genérica, só eventos a conferir — não é auditoria geral). Só vale para PDV monitorado: tblpdv.monitoramento (data 'monitorar a partir de'; nula = não monitora) e tblpdv.minutosesquecido (padrão 120). Gerente confere linha a linha numa tela do contas.

Decisão de 09/10/2026: todo o monitoramento é feito no SERVIDOR, nada no PDV. Não se rastreia cada clique: o servidor olha o estado do negócio no fechamento e registra nos pontos onde ele mesmo sobrescreve ou apaga (sincronização). O caixa não informa motivo; fica a justificativa do cancelamento e a observação do gerente. Uma ocorrência por negócio e por tipo, com a lista dos itens.

Painel de caixas/maquinetas ficou na TASK-203. Parecer por LLM na TASK-206.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Tabela tblocorrencia + tblocorrenciaauditoria (N:N com a tblauditoria) + colunas monitoramento/minutosesquecido no PDV (api/database/ocorrencia.sql, idempotente)
- [ ] #2 O fato (o que mudou, antes/depois, justificativa) fica só na auditoria; a ocorrência amarra N auditorias e uma auditoria pode estar em N ocorrências
- [ ] #3 Negócio cancelado vira ocorrência com quem, quando, justificativa e pagamentos que tinha
- [ ] #4 Pagamento e vale estornados viram ocorrência (sem duplicar com o cancelamento do negócio)
- [ ] #5 Desconto acima do permitido (maior entre desconto do cadastro e 5% sobre a parte à vista pix/dinheiro/débito) vira ocorrência no fechamento
- [ ] #6 No fechamento: item excluído, vale compras excluído, preço abaixo ou acima do cadastro e saída sem financeiro (uso e consumo, perda, brinde) viram ocorrência, uma por negócio e tipo
- [ ] #7 Juntar bipes repetidos, aumentar quantidade, preço antigo de mudança recente no cadastro e transferência entre filiais não geram ocorrência
- [ ] #8 Na sincronização do PDV: quantidade diminuída, pagamento apagado e parcela a prazo apagada viram ocorrência; a quantidade que volta também aparece na mesma ocorrência
- [ ] #9 Correções de lançamento da TASK-204 (data alterada, data do cancelamento, corrigido, indevido, incluído) em PDV monitorado viram uma ocorrência por correção
- [ ] #10 Negócio aberto com itens parado além do tempo do PDV vira ocorrência 'esquecido' (agendado); ao fechar o caixa, o que ainda estiver aberto vira ocorrência e o caixa fecha
- [ ] #11 Cadastro do PDV permite marcar a data de monitoramento e os minutos para esquecido
- [ ] #12 Tela Ocorrências no contas: gerente vê as da filial dele, filtra, ordena por valor e confere com observação
- [ ] #13 PDV sem nada de monitoramento: sem pedir motivo ao caixa
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Primeira versão (08/10, commits 2f6978111 e 7c7287583): motivo pedido ao caixa no PDV. No teste do Fábio só nasceram cancelado e desconto. Refeito em 09/10 todo no servidor, com o fato na auditoria da TASK-204.

Desenho (decisões do Fábio em 09/10):
- Fato na tblauditoria (tipos 6–13 novos: item excluído, quantidade alterada, preço diferente do cadastro, vale excluído, pagamento apagado, parcela apagada, negócio cancelado, estornado). tblocorrencia só agrupa: tipo, negócio, PDV, filial, quem operou, descrição, valor, conferência — sem antes/depois/justificativa. Ligação N:N em tblocorrenciaauditoria.
- Esquecido, desconto acima e saída sem financeiro são estado: ocorrência sem auditoria.
- Correções da TASK-204 (auditoria 1–5) em PDV monitorado viram ocorrência, uma por correção (tipos 15–19, tabela tblauditoria). PDV do lançamento: pagamento.codpdv; movimento do portador = gaveta do PDV (tblpdv.codportador); transferência = as duas auditorias numa ocorrência só.
- Quantidade diminuída: cada mudança vira auditoria; o aumento só quando o item já tinha diminuído (a volta fica visível, valor líquido).

Código:
- Mg\Ocorrencia\OcorrenciaService: tipos, monitorado, registrar/amarrar, doNegocio (uma por negócio e tipo; descrição e valor refeitos das auditorias amarradas), cancelado, estorno, correcao (TASK-204), esquecidos, listagem (devolve as auditorias e a justificativa delas).
- Mg\Ocorrencia\OcorrenciaPdvService: noFechamento (PdvNegocioService::fechar, dentro da transação), quantidadeAlterada (negocioAberto, antes do fill do item), pagamentosApagados/parcelasApagadas (PdvNegocioPagamentoService::importar, antes do delete).
- Ganchos da TASK-204: PagamentoCorrecaoService::registrar e LancamentoDataService (registrarPagamento e alterarMovimento).
- Preço: oficial = coalesce(pe.preco, round(p.preco*pe.quantidade,2), p.preco); aceita precoantigo de tblprodutohistoricopreco criado depois do item. Fora: natureza que não é venda, negócio com vale, Mercos, Woo, que recebeu comanda (cancelada 'Unificado no negócio #X' nos últimos 30 dias), devolução e item lançado por outro usuário (orçamento). Ruído no dev (90 dias): 245 de 13.669 negócios de PDV (1,8%); sem o histórico, 1.208. Custo ~7 ms.
- Offline só chega o estado final: diminuição feita sem rede se perde (aceito).
- PDV (negocios) voltou ao que era antes de 2f6978111; apagados OcorrenciaMotivoDialog e MgSelectOcorrenciaMotivo. Fica o cadastro do PDV e o 'Monitorado desde'. contas: ícones dos tipos novos.
- DDL: ocorrencia.sql roda depois do auditoria.sql. No dev: colunas antes/depois/justificativa/motivo/uuid tiradas à mão; os 2 cancelamentos de teste do Fábio passaram para a auditoria (23 e 24) e foram amarrados.

Esquecido não aparecia no teste porque nenhum negócio aberto do PDV 508 tinha sido criado depois de 01/10.

Bateria no dev com rollback (script PHP no container, fila fake): exclusão (juntado não conta), diminuir 3→2→1, volta 1→3 (fica, R$ 0), preço abaixo/acima, preço antigo aceito, vale excluído, pagamento e parcela apagados, desconto 20%, uso e consumo, cancelamento com justificativa na auditoria, correção de data → tipo 15 amarrado, PDV não monitorado → nada, esquecido 1 e depois 0, reenvio sem duplicar. Listagem devolve auditorias e justificativa. Teste de tela no navegador: pendente (o container reiniciou no meio).
<!-- SECTION:NOTES:END -->
