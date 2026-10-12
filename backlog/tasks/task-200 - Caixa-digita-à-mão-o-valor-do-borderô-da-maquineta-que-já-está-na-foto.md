---
id: TASK-200
title: Caixa digita à mão o valor do borderô da maquineta que já está na foto
status: To Do
assignee: []
created_date: '2026-10-07 02:45'
labels:
  - contas
dependencies:
  - TASK-39
priority: low
type: feature
ordinal: 213000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No lançamento do borderô da maquineta de parceiro (TASK-39, contas → portador em espécie → período → Borderô de maquineta), o caixa fotografa o borderô e ainda digita o total em dinheiro que está impresso nele. Ideia: ler a foto com uma LLM com visão (Claude, uma chamada só com resposta estruturada { dinheiro, data, confianca }) e preencher o campo Valor como sugestão; o caixa confere e clica em Lançar (nada é gravado sozinho; um botão, uma coisa). Sem leitura ou com confiança baixa, o campo fica vazio, como hoje.

Como seria: endpoint novo que recebe a foto (o MaquinetaCaixaDialog já tem o base64 do MgSlim), service no backend com o SDK PHP da Anthropic (composer require anthropic-ai/sdk, ANTHROPIC_API_KEY no .env, servidor com acesso a api.anthropic.com). Hoje o projeto não usa LLM nem OCR. Alternativa descartada: OCR local (Tesseract) + regra por parceiro, frágil com layout diferente por parceiro e papel térmico apagado.

Custo estimado (06/10/2026, ~1.600 tokens de entrada por foto): Claude Opus 5 ~US$ 0,01/foto; Claude Haiku 4.5 ~US$ 0,002/foto; ~300 fotos/mês = US$ 3 ou US$ 0,70.

A decidir antes de codar: modelo (Opus 5 ou Haiku 4.5, testar os dois com ~20 borderôs reais); ler só o dinheiro ou também a data (avisar borderô de outro dia); o borderô sai do servidor para a Anthropic (normalmente só totais, sem dado pessoal).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Ao fotografar o borderô no diálogo, o valor em dinheiro lido da foto aparece preenchido no campo Valor, para o caixa conferir antes de lançar
- [ ] #2 Foto que não dá para ler (ou leitura duvidosa) deixa o campo vazio e avisa, sem impedir o lançamento
- [ ] #3 Modelo escolhido com teste em borderôs reais dos parceiros (Bilhete Agora, Redeflex, Rede Card)
<!-- AC:END -->
