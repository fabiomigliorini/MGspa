---
id: TASK-155
title: >-
  Excecao depois de a SEFAZ autorizar derruba a transmissao: nota fica AUT e o
  cupom nao sai
status: To Do
assignee: []
created_date: '2026-09-23 20:18'
updated_date: '2026-09-23 21:01'
labels:
  - api
dependencies: []
priority: high
type: bug
ordinal: 164000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: varredura da TASK-123 (achado U14). Ficou de fora da TASK-147, que foi fechada so com o item do progresso orfao — esta task cobre o item (2) que sobrou.

NFePHPService::vincularProtocoloAutorizacao (~:527) grava a autorizacao no banco logo no inicio ($nf->nfeautorizacao = nProt; $nf->save(), que dispara o observer e deixa a nota AUT) e SO DEPOIS faz o trabalho acessorio:
  - file_get_contents(pathNFeAssinada) — arquivo pode ter sumido do disco
  - Complements::toAuthorize() — pode lancar
  - file_put_contents(pathNFeAutorizada) — disco cheio, permissao, NFS fora
  - NFePHPMailJob::dispatch() — Redis fora

Qualquer excecao dali sobe por processarProtocolo -> enviarSincrono -> NFePHPEnvioService::executar(), cujo catch grava status 'erro' e relanca. Resultado: a nota esta autorizada na SEFAZ E no banco, mas o front recebe 'erro', nao imprime o cupom nem abre o DANFE. O card aparece Autorizado e o operador fica sem cupom — e sem poder reemitir (ja tem chave e status AUT). Acontece SEM trocar de negocio, o que casa com parte do relato do suporte.

Fix proposto (confirmar): depois do $nf->save() a autorizacao e fato consumado; o que vem depois e recuperavel (o botao Consultar e o robo revinculam o protocolo e regeram o XML). Envolver o trecho pos-save em try/catch, logar como erro alto (Log::error, para alguem olhar) e devolver true mesmo assim, em vez de derrubar a transmissao inteira. Avaliar se o XML autorizado ausente precisa de alerta proprio — ele e o documento fiscal e nao pode ficar faltando em silencio.
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
created: 2026-09-23 21:01
---
Dobrada na TASK-123 (regra 3 do CLAUDE.md): virou o critério de aceite #2 da task-mãe, e o detalhe técnico desta descrição está preservado nas notas da TASK-123. Arquivada para sair do board — não foi descartada.
---
<!-- COMMENTS:END -->
