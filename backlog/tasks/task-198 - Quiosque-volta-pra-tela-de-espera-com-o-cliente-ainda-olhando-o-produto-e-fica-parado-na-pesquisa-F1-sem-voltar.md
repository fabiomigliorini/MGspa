---
id: TASK-198
title: >-
  Quiosque volta pra tela de espera com o cliente ainda olhando o produto, e
  fica parado na pesquisa (F1) sem voltar
status: Done
assignee:
  - '@fabio'
created_date: '2026-10-05 16:09'
updated_date: '2026-10-05 21:53'
labels:
  - negocios
dependencies: []
priority: low
type: bug
ordinal: 211000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Na tela do quiosque (negocios /quiosque), o tempo para voltar à tela de espera é de 90s contados a partir da ÚLTIMA CONSULTA, não do último uso: se o cliente está passando as fotos ou rolando a tela, a contagem não reinicia e a tela some no meio do uso. E quando a pesquisa F1 fica aberta (com texto digitado e resultados), o timer não age sobre ela: o quiosque fica parado na pesquisa indefinidamente.

Origem técnica: negocios/src/pages/QuiosquePage.vue — TEMPO_ESPERA = 90000; agendarEspera() só é chamado em consultar() e no 'não encontrado' (8s); o timeout chama sQuiosque.limpar(), que limpa produto/detalhe mas não fecha o dialog de pesquisa (sProduto.dialogPesquisa) nem limpa sProduto.textoPesquisa/resultadoPesquisa.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Tempo sem uso para voltar à tela de espera cai de 90s para 60s
- [x] #2 O aviso de 'produto não encontrado' continua sumindo rápido (8s)
- [x] #3 A janela de pesquisa (F1) fecha sozinha após 60s sem uso, no caixa e no quiosque; cada toque, tecla ou rolagem reinicia a contagem
- [x] #4 A pesquisa do caixa e a do quiosque são o mesmo componente (sem cópia do dialog)
- [x] #5 Menu do quiosque tem atalho para abrir a sincronização
- [ ] #6 Consulta de preços antiga do MGLara desabilitada: sai do menu e as rotas produto/quiosque e produto/consulta/{barras} deixam de existir
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação: pesquisa offline extraída para negocios/src/components/offline/DialogPesquisaProduto.vue (setas + Enter copiados do @components/MgDialogPesquisaProduto, timer de 60s sem uso com listeners em captura no document), usada por InputBarras.vue e QuiosquePage.vue no lugar das duas cópias do dialog. Janela 'Sincronizar...' extraída do BtnSincronizacao.vue para DialogSincronizacao.vue, aberta também pelo FAB do quiosque. QuiosquePage: TEMPO_ESPERA 60000. Teclado também cobre o AC3 da TASK-173.
<!-- SECTION:NOTES:END -->
