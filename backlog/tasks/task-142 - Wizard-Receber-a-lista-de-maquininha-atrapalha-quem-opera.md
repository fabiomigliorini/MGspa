---
id: TASK-142
title: 'Wizard Receber: a lista de maquininha atrapalha quem opera'
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-09-22 20:09'
updated_date: '2026-09-23 21:02'
labels:
  - negocios
dependencies: []
priority: medium
type: enhancement
ordinal: 152000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Operadores se confundem com a lista cheia de maquininhas da filial na etapa Cartao do wizard Receber. A etapa passa a abrir com a maquininha padrao do PDV na tecla 0 (ja pre-selecionada) e Manual na tecla 1; as demais da filial ficam abaixo, separadas pelo cabeçalho 'Outras Maquinetas', numeradas de 2 em diante. Tudo numa tela so - sem sub-etapa e sem esconder opcao.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Etapa Cartão abre com a maquininha padrão do PDV na tecla 0 e Manual na tecla 1; as outras da filial abaixo de 'Outras Maquinetas', todas visíveis
- [ ] #2 Cartão manual não lista mais 13 pinpads repetidos e desabilitados por falta de serial
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
1a tentativa (22/09, commit 0932885d) escondia as outras maquininhas atras de uma linha 'Selecione outra maquininha' que abria a sub-etapa 'outras'. RECUSADA na validacao: esconder opcao nao foi aceito. O commit foi revertido (FormaCartao.vue e FormaPix.vue voltaram ao estado de b52e2fa5) e a etapa foi refeita numa tela so.

Solucao atual, em negocios/src/components/offline/receber/FormaCartao.vue (opcoesModo): lista unica, sem sub-etapa. Ordem fixa 0 = maquininha padrao do PDV (ja pre-selecionada via :inicial=valorPadrao), 1 = Manual, e as demais da filial sob o cabeçalho 'Outras Maquinetas' em 2, 3, 4... O cabeçalho sai do campo 'grupo' que o ListaOpcoes ja suportava - basta marcar as 'outras'; a padrao fica sem grupo, no topo.

Sem padrao configurado (ou negocio de outro estoque local) nao ha o que destacar: Manual volta ao 0 e as maquininhas contam de 1, sob o cabeçalho 'Maquinetas'. Filial sem maquininha nenhuma mantem a linha desabilitada 'Nenhuma maquininha cadastrada nesta filial'. Negocio nao sincronizado desabilita todas as maquininhas (Manual continua valendo) e a selecao inicial cai no Manual, como antes.

DEDUPLICACAO (achado na 1a validacao, mantido): SaurusPosS vem do PdvService::estoqueLocal com uma linha POR PINPAD, e um PDV com varios aparelhos repetia na lista - mesmo apelido, mesmo 'valor' (saurus-{codsauruspdv}), teclas diferentes apontando para a mesma maquininha, e key duplicada no v-for do ListaOpcoes. No Botanico eram 14 linhas para 8 maquininhas reais (BOT Alfa 3x, BOT Bravo 3x, BOT Foxtrot 3x); no Centro, 11 para 9. Quem recebe a cobranca e o PDV, entao maquinetasEnvio deduplica por 'valor'. Sem isso a nova numeracao ficaria 'Alfa 2, Alfa 3, Alfa 4'. A lista do cartao MANUAL nao pode ser deduplicada do mesmo jeito (la o que importa e o serial de cada pinpad) - e a TASK-143.

ListaOpcoes.vue: o cabeçalho de grupo ganhou 'q-mt-md q-pb-xs text-subtitle1 text-weight-bold' - respiro antes do titulo e o titulo em 16px/700 (o default do q-item-label header e 14px/400, cinza demais para separar bloco). So classes utilitarias, sem style inline e sem bloco <style>. Afeta tambem os cabeçalhos 'Da filial'/'Outras filiais' do PIX, que ficam com o mesmo tratamento.

ATENCAO AO TESTAR: o Deposito (101001) tem UMA maquininha so cadastrada (DEP Alfa), entao la aparecem so 0 e 1 - nao ha 'Outras Maquinetas'. Testar no Botanico (102001, 8 maquininhas) ou Centro (103001, 9).

Verificado: eslint limpo nos tres arquivos; logica de opcoesModo rodada fora do componente nos 5 cenarios (padrao no meio da lista, sem padrao, padrao unico, filial sem maquininha, negocio nao sincronizado), conferindo ordem, teclas e deduplicacao; screenshot headless da etapa em 900px e 400px.

## Detalhe técnico das tasks consolidadas (2026-09-23)

Dobradas nos critérios de aceite desta task conforme a regra 3 do CLAUDE.md; o texto
original está preservado aqui.

### TASK-143 — pinpads sem serial poluem a lista do cartão manual

Descoberto durante a TASK-142. Na etapa 'maquineta' do cartao MANUAL (FormaCartao.vue, opcoesMaquineta) a lista vem de posSaurus, que traz uma linha POR PINPAD. Como nenhum pinpad tem serial cadastrado no banco (todos null em tblsauruspinpad.serial), o Botanico mostra 13 linhas, todas desabilitadas com 'Sem serial cadastrado - digite o serial acima', e com apelidos repetidos (BOT Alfa 3x, BOT Bravo 3x, BOT Foxtrot 3x). Pior: o valor cai no fallback 'sem-serial-${codsauruspdv}', que repete entre pinpads do mesmo PDV e vira key duplicada no v-for do ListaOpcoes. Diferente da lista de ENVIO (deduplicada na TASK-142), aqui nao da pra deduplicar por codsauruspdv: o que importa e o serial de cada aparelho. Decidir entre esconder os sem serial, agrupar por PDV ou cadastrar os seriais (TI). Ver tambem TASK-100, que removeu o pedido do serial no 1o uso.

### Decisão registrada (era a TASK-144) — não esconder opção

Esconder opção foi RECUSADO na validação, tanto no cartão quanto no PIX. A regra que vale
daqui pra frente, para qualquer lista do wizard: destacar as opções do dia a dia no topo,
com tecla baixa, e as demais abaixo de um cabeçalho — todas visíveis, sem sub-etapa.
Contexto original:

Na etapa QR Code do PIX a lista traz todos os portadores, de todas as filiais. A 1a tentativa (22/09, commit 0932885d) deixava visiveis so o Sicredi da empresa mae e o BB da filial do negocio, com uma linha 'Selecione portador de outra filial' abrindo a etapa 'outras'. RECUSADA na validacao junto com a do cartao (TASK-142): esconder opcao nao foi aceito. FormaPix.vue foi revertido para o estado de b52e2fa5 - volta a listar tudo, com as contas da filial do negocio primeiro e os cabecalhos 'Da filial' / 'Outras filiais' (TASK-124).

Se a lista voltar a incomodar, a saida NAO e esconder: seguir o padrao da TASK-142 (destacar as contas do dia a dia no topo, com tecla baixa, e as demais abaixo de um cabecalho, todas visiveis). Nao reabrir a sub-etapa.
<!-- SECTION:NOTES:END -->
