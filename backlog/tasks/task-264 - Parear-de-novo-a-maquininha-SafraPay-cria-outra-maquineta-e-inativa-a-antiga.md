---
id: TASK-264
title: Parear de novo a maquininha SafraPay cria outra maquineta e inativa a antiga
status: Done
assignee: []
created_date: '2026-11-04 15:42'
updated_date: '2026-11-21 20:19'
labels:
  - contas
dependencies: []
priority: critical
type: bug
ordinal: 3000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Sintoma: no contas → Maquinetas → Parear de novo, quando o S2 Pay é reinstalado ou o aparelho é trocado, a leitura do QR cria outra maquineta e inativa a antiga. Pagamentos, períodos (conferência do borderô) e a maquineta padrão do PDV ficam divididos em dois registros. Aconteceu em produção em 04/11/2026: maquininha travada, app reinstalado, re-pareamento criou registro novo.

Mesmo erro, outro sintoma (21/11/2026): venda feita na BOT Golf aparece no negócio como "POS BOT Foxtrott" (e as da BOT Delta como "BOT Charlie"). Cobrança e cartão certos; o nome vinha da cópia gravada no pinpad em 2025, que nunca mais acompanhou o cadastro.

Por que Critical: prioridade de quem prioriza. Maquininha travada exige reinstalar ou trocar o aparelho na hora, e hoje cada pareamento parte o registro da maquineta em produção.

Causa: o M3 da TASK-188 (doc-3) amarrou a maquineta SafraPay ao aparelho (tblmaquineta.codsauruspinpad, "maquineta = pinpad") e copiou apelido, serial, filial e inativo da maquineta para as tabelas da SafraPay e da PagarMe. A migração api/database/maquineta.sql criou uma maquineta por pinpad: as 7 maquininhas pareadas de novo em 2025 viraram 2 ou 3 registros (10 inativos). O Parear repete o erro a cada aparelho novo.

Regra nova (Fábio): o que é comum a todas as maquinetas e 1:1 fica na tblmaquineta; o que é específico da operadora ou N:1 fica na tabela da operadora.

Desenho:
- tblmaquineta: apelido, serial, codfilial, compartilhada, codpessoa, integracao, codpagarmepos, inativo; codsauruspinpad sai e entra codsauruspdv (o PDV Saurus, único; nulo até o primeiro Parear).
- tblsauruspdv (PDV Saurus = credencial na Saurus; a chavepublica é o QR Code da tela): id, numero, chavepublica, autorizacao, vencimento, contratoid. Saem apelido, codfilial e inativo.
- tblsauruspinpad (histórico N:1 dos aparelhos de cada tblsauruspdv): id (uuid da Saurus), codsauruspdv, inativo (aparelho substituído). Saem apelido, serial, codfilial e observacoes. Um aparelho cobra por vez (produção 21/11/2026: as 5 trocas de 26/03/2025 com corte limpo).
- tblpagarmepos: só o código (âncora de pedido e pagamento PagarMe). Saem apelido, serial, codfilial e inativo; a PagarMe acha a maquineta por serial + filial da conta.
- Divergência maquineta × operadora em filial e serial: zero no dev e em produção (21/11/2026).

Fluxo: a maquineta SafraPay se cria como as outras (nome, filial) e se pareia dentro dela, com um botão Parear para o primeiro pareamento e para o de novo. O Parear registra o tblsauruspdv com a filial da maquineta (ou renova o mesmo), confere a leitura do QR Code, ativa o tblsauruspinpad que leu e inativa os outros do mesmo tblsauruspdv e o mesmo aparelho em outro tblsauruspdv. A cobrança vai para o tblsauruspdv e manda no IdPinPad o uuid do tblsauruspinpad ativo; sem aparelho ativo, a maquineta aparece na lista e a cobrança recusa ("Maquineta sem aparelho pareado: pareie no contas."). O cartão continua apontando para o pinpad que cobrou.

Decisões do Fábio (21/11/2026): as 10 duplicadas de 2025 são juntadas na ativa do mesmo tblsauruspdv; o serial das 30 SafraPay que guarda o uuid da Saurus fica como está (troca à mão quando digitarem o do aparelho); a sequência do numero do tblsauruspdv é por filial, pela maquineta.

SQL: api/database/maquineta_campo_unico.sql (go-live, junto com o deploy).

Teste em dev: tinker em transação com rollback. Cuidado com saurus/qrcode e saurus/confirmar no dev: o api/.env do dev tem SAURUS_S2PAY_* (fala com a Saurus de verdade) e os PDVs Saurus têm os ids da produção — ler no pinpad um QR Code gerado no dev pareia o aparelho no PDV Saurus de produção.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Parear de novo mantém a mesma maquineta com qualquer aparelho que leia o QR (o mesmo, com o app reinstalado, um novo ou um vindo de outro QR): pagamentos, períodos e maquineta padrão do PDV ficam nela; só o pinpad troca e o anterior é inativado
- [x] #2 O cartão cobrado no QR cai na maquineta do QR, sem nascer outra: inclusive do pinpad antigo confirmado depois do novo pareamento e do aparelho cuja leitura ainda não foi confirmada (hoje dá erro)
- [x] #3 A maquineta duplicada em produção (153) foi juntada na 108 sem perder nada
- [x] #4 Pagamento SafraPay no negócio mostra o nome da maquineta do cadastro (venda da BOT Golf aparecia como BOT Foxtrott)
- [x] #5 Nome, serial, filial e inativo da maquineta ficam só no cadastro; SafraPay e PagarMe guardam só o que é delas
- [x] #6 Maquineta SafraPay se cria como as outras e se pareia dentro dela (um botão Parear)
- [x] #7 As maquinetas duplicadas de 2025 viram uma por tblsauruspdv, sem perder pagamento
- [x] #8 Tela da maquineta SafraPay avisa quando ela está sem aparelho pareado
- [x] #9 Maquineta SafraPay criada por engano, que nunca foi pareada, pode ser excluída
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
CORREÇÃO DE DADOS EM PRODUÇÃO (04/11/2026) — juntar a 153 na 108, por SQL, sem perder nada.

Diagnóstico de produção (cópia de segurança, antes da correção):
- QR (PDV Saurus) da 108: pinpad 19, id 0ba11877-b74c-418f-9b82-bb158e229520, criado 2025-09-26 11:06:29, inativado 2026-11-04 10:47:42 → maquineta 108 (inativada 2026-11-04 10:47:42).
- Pinpad 32, id b8a36e05-cbaa-4c62-8f38-af44b43de0d0, criado 2026-11-04 10:43:24, ativo → maquineta 153 (ativa).
- 153: 0 pagamentos, 0 períodos, 0 cartões Saurus. 108: 16 pagamentos. 5 cobranças pendentes no QR nos últimos 3 dias (conferir as de antes das 10:43 antes de corrigir).

Por que não apontar a 108 para o pinpad 32: sem esta task no ar, o pinpad 19 ficaria sem maquineta e todo cartão antigo re-consultado (ou usado numa baixa de título, já no portadorPrevisto) criaria maquineta fantasma via daSaurusPinPad. Então o pinpad 19 recebe o id do aparelho novo; a 153 e o pinpad 32 saem. Efeito até esta task entrar: re-consultar na Saurus um cartão do aparelho velho dá erro sem gravar nada.

Ensaio no dev (transação com ROLLBACK, 04/11/2026): incidente simulado na 108 (pinpad novo 32, maquineta nova 140, um cartão Saurus no pinpad novo) → depois da correção a 108 fica ativa no pinpad 19 com o id novo, 140 e 32 saem, pagamentos (10) e períodos (1) da 108 iguais, cartão passa para o 19, id novo resolve na 108. Trava: com pagamento na nova, o DELETE falha na fk_tblpagamento_tblmaquineta e nada é gravado.

Para a implementação: com o pinpad 19 levando o id novo, o consultarPedido desta task não pode sobrescrever com nulo o codsauruspinpad já gravado de um cartão antigo (pinpad não achado → mantém o gravado).

Executado em produção em 04/11/2026. Conferência: só a 108, ativa, no pinpad 19 com o id b8a36e05-cbaa-4c62-8f38-af44b43de0d0 (ativo), 16 pagamentos, pinpad 32 removido.

IMPLEMENTAÇÃO (21/11/2026) — regra do campo único, na árvore, aguardando validação.

- SQL api/database/maquineta_campo_unico.sql (idempotente; rodado no dev): junta as 10 maquinetas de 2025 na ativa do mesmo tblsauruspdv (31 SafraPay → 21), preenche tblmaquineta.codsauruspdv (único), índice único em tblsauruspinpad (id, codsauruspdv) (Fábio, 21/11), acerta tblsauruspinpad.inativo (só o mais novo de cada QR ativo) e apaga as colunas copiadas. Go-live: junto com o deploy.
- Backend: MaquinetaService (criar aceita SafraPay sem tblsauruspdv; atualizar/inativar/ativar só na maquineta; qrCode e confirmarLeitura do Parear), MaquinetaController (saurus/qrcode e saurus/confirmar por codmaquineta), SaurusService (registrarPdv pela maquineta, numero por filial pela maquineta, CNPJ da filial da maquineta, gravarLeitura ativa o aparelho e inativa os outros do QR e ele mesmo em outro QR, pinpadAtivo, consultarPedido sem 500, maquineta do pedido = a do QR), ApiService (IdPinPad = uuid do pinpad ativo), CobrancaService/PdvController (sem aparelho ativo: 422 "Maquineta sem aparelho pareado: pareie no contas."), PagarMeService (POS por serial + filial da maquineta), resources Saurus/PagarMe (apelido e serial da maquineta; as chaves da Saurus não saem mais no JSON), PdvService (saem SaurusPosS/PagarMePosS), models e Filial.
- Front: contas → MaquinetaDialog (SafraPay salva como as outras; q-select → MgSelect), MaquinetaParearDialog (Parear único), maquinetaStore, Detalhe (tooltip Parear); components/cobranca/FormaCartao (opção SafraPay por maquineta).
- Conferido em dev (script com rollback, 24 de 24): resource do pedido da BOT Golf → "BOT Golf" no pedido e no cartão; leitura nova não cria maquineta; aparelho pareado em outro tblsauruspdv sai do primeiro; aparelho antigo que volta reativa o mesmo pinpad; cobrança sem QR ou sem aparelho ativo recusa com a mensagem; SafraPay nova nasce sem QR; PagarMe acha o POS por serial + filial (o 1731113916 nas filiais 101 e 102 continua em duas maquinetas); atualizar e inativar não mexem na operadora.

- Gaps fechados com o Fábio (21/11): a tela da maquineta SafraPay mostra "Sem aparelho pareado" (MaquinetaResource devolve pareada); SafraPay que nunca foi pareada (sem tblsauruspdv) pode ser excluída, como a manual.

- Testes no dev (21/11, a pedido do Fábio), sem chamar Saurus nem PagarMe: API pelo kernel HTTP com rollback (21 casos: CRUD das três integrações, Parear sem PDV Saurus, QR Code com a chave válida, excluir, cobrança sem aparelho, lista do PDV); services com rollback (24 + 12: leitura do aparelho, cartão que vira pagamento cai na maquineta do PDV Saurus inclusive os de 2025 juntados, aparelho desconhecido sem 500, maquineta padrão do PDV antigo); tela do contas no Chrome headless (14: lista, 118 sem etiqueta e sem lixeira, Parear da 102 com QR Code, nova SafraPay com etiqueta e lixeira, excluir). Nenhum bug no código; o pinpadQueCobrou saiu do consultarPedido para ser testável.

Como testar: contas → Maquinetas → Nova (SafraPay) salva sem tblsauruspdv; abrir a maquineta → Parear mostra o QR Code (no dev fala com a Saurus de verdade: não ler o QR Code com um pinpad de produção). Editar e inativar/ativar uma SafraPay e uma Stone. Negócio → cobrança SafraPay de uma venda da BOT Golf mostra "POS BOT Golf"; cobrança Stone mostra o nome da maquineta.
<!-- SECTION:NOTES:END -->
