---
id: TASK-174
title: >-
  Campo de texto e select: o X de limpar rouba o Tab e cada tela repete a mesma
  gambiarra
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-24 23:13'
updated_date: '2026-10-10 18:33'
labels:
  - components
dependencies: []
priority: low
type: chore
ordinal: 188000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quem preenche formulario no teclado (PDV, wizard do vale, cadastros) passa pelo X de limpar antes de chegar no proximo campo: o icone do 'clearable' do Quasar tem tabindex=0 fixo no fonte (node_modules/quasar/src/composables/private.use-field/use-field.js, linha ~463) e nao tem prop pra desligar. Campo readonly tem o mesmo problema: para no Tab sem deixar digitar.

Ja existia contorno espalhado: MgInputValor desenha o X no slot #append com tabindex=-1, MgInputData forca tabindex=-1 no DOM depois de renderizar, e no ValeDialog o mesmo #append foi repetido na mao.

Solucao: @components/MgInput.vue (criado em 24/09/2026), q-input da casa que ja nasce com o X fora do Tab e com readonly pulado (:tabindex). Repassa $attrs, os slots prepend/append/before/after/hint e expoe focus/blur/select/validate/resetValidation/nativeEl, entao e troca 1:1 de <q-input> por <MgInput>.

Falta trocar os q-input crus: 485 ocorrencias em 188 arquivos (negocios 48, pessoas 135, contas 88, estoque 64, agro 35, notas 115). O app quasar/ (v1) esta abandonado e fica fora.

Relacionada: TASK-26 faz o mesmo movimento do lado dos selects, no app notas.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Nenhum <q-input> cru sobrou em negocios/src (trocado por MgInput)
- [x] #2 Nenhum <q-input> cru sobrou em pessoas/src
- [x] #3 Nenhum <q-input> cru sobrou em contas/src
- [x] #4 Nenhum <q-input> cru sobrou em estoque/src
- [x] #5 Nenhum <q-input> cru sobrou em agro/src
- [x] #6 Nenhum <q-input> cru sobrou em notas/src
- [x] #7 Tab anda campo a campo sem parar no X de limpar nem em campo readonly nas telas mexidas
- [x] #8 Regra do MgInput escrita no CLAUDE.md (campo novo e form que receber manutencao usam MgInput)
- [x] #9 Componentes compartilhados (@components) sem q-input cru fora dos proprios MgInput/MgInputValor/MgInputData
- [x] #10 MgSelect criado: q-select da casa com o X de limpar e o readonly fora do Tab
- [x] #11 Os 41 MgSelectXxx de @components montam em cima do MgSelect
- [x] #12 Nenhum q-select cru com clearable sobrou nos 6 apps nem em @components
- [x] #13 Tab anda campo a campo sem parar no X dos selects nas telas mexidas
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
24/09/2026: criado o @components/MgInput.vue e a regra no CLAUDE.md. ValeDialog (negocios) ja migrado — foi o que levantou o caso. O MgInputFormatado tambem passou a montar em cima do MgInput (era q-input cru), entao os 21 lugares que usam ele ja herdam o X fora do Tab. Falta a varredura dos q-input crus, app por app.

28/09/2026: agro migrado — 31 q-input em 16 arquivos. 29 viraram MgInput (troca 1:1). Os 2 type=number viraram MgInputValor: Tara do caminhao (decimals 0, suffix kg) e N do romaneio no filtro de cargas (decimals 0, sem milhar, alinhado a esquerda). SelectTalhao tambem virou MgInput: o campo readonly sai do Tab e o foco cai direto no botao do mapa (Enter abre).

28/09/2026: negocios migrado — 43 q-input em 19 arquivos. Numericos viraram MgInputValor: quantidade da devolucao (3 casas, teto = disponivel), codnegocio da confissao, copias da comanda e os filtros ID Woo, # Negocio, # Liquidacao e N do negocio dos vales emitidos (sem milhar). Removido um <q-input /> vazio perdido no dialogo de editar item (ListagemProdutos), que desenhava uma caixa sem funcao e parava no Tab.

28/09/2026: estoque migrado — 64 q-input em 17 arquivos. Numeros viraram MgInputValor com as casas da coluna no banco (preco/dimensoes 2, peso 4, custo medio 6, quantidade conferida e embalagem 3, codigos e localizacao sem casas nem milhar, mes 1-12). Datas viraram MgInputData; a data/hora do ajuste da conferencia virou MgInputData timestamp e passou a abrir na hora local (antes vinha de toISOString, em UTC, 4h adiantada). MgInputValor e MgInputData ganharam o slot #prepend (repassado ao q-input), para os icones dos filtros de codigo e das datas continuarem aparecendo.

28/09/2026: contas migrado — 82 q-input em 33 arquivos. Os 24 numericos (filtros de codigo, numero do banco, dados bancarios do portador, numero do motivo de devolucao, agencia do filtro de cheque, dias da parcela no agrupamento) viraram MgInputValor sem casas; so os Dias aceitam milhar/negativo. Nenhuma data crua no contas.

28/09/2026: notas migrado — 115 q-input em 39 arquivos. Numericos viraram MgInputValor: quantidades e pesos com 3 casas (a quantidade do item tinha 4 na tela, o banco guarda 3), tara/capacidade/volumes sem casas, codigos/CST/CSOSN/NSU/numero/ordem sem casas nem milhar. O filtro de emissao das NF-e de terceiros (mascara DD/MM + q-date + conversao na mao) virou MgInputData, e sairam as funcoes convertToISODate/convertFromISODate. Varredura confirmou que nenhum campo migrado tinha conteudo no slot padrao (MgInput nao o repassa).

28/09/2026: pessoas migrado — 133 q-input em 55 arquivos (5 em Options API: import + registro em components). Numericos viraram MgInputValor: quantidade da rubrica e do fixo da meta com 2 casas; dias de ferias/abono/desconto/gozo, dias de experiencia/renovacao, dias uteis, tolerancias, ano dos feriados, serie NF-e e codigos sem casas. Com isso os 6 apps estao sem q-input cru; falta o teste de Tab (#7).

28/09/2026: MgAppsMenu (busca), MgDialogPesquisaProduto (pesquisa) e MgInputProdutoBarras (barras) trocados por MgInput. Em @components so restam os q-input que sao a base do MgInput, MgInputValor e MgInputData. App quasar/ (v1) abandonado, fora.

10/10/2026: reaberta para os selects. O Fábio viu o Tab parar no X da Maquineta padrão (tela do dispositivo). O X do clearable do q-select é o mesmo ícone com tabindex=0 fixo no use-field.js. Solução: @components/MgSelect.vue, o q-select da casa (X desenhado no #append com tabindex=-1 e readonly fora do Tab, todos os slots repassados), em cima do qual montam os 41 MgSelectXxx. Os 70 q-select crus com clearable (37 arquivos: notas 32, pessoas 11, agro 9, contas 7, negocios 3, estoque 3, @components 3) viram MgSelect 1:1. Os q-select crus sem clearable ficam como estão.

10/10/2026: MgSelect criado. Detalhe que pegou no teste: a raiz do QField é um <label>, e o clique no X só com .stop não chegava ao preventDefault do #append; o label repassava o clique pro input e o menu abria. Ficou @click.stop.prevent, com o foco voltando pro campo (menos no celular, pra não abrir o teclado), igual ao clearValue do Quasar. Os 41 MgSelectXxx trocaram q-select por MgSelect; o MgSelectPessoa perdeu o :tabindex do readonly, que agora mora no MgSelect. Os 70 q-select crus com clearable (37 arquivos) viraram MgSelect 1:1; pessoas/pages/pessoa/Index.vue (Options API) registrado em components. Testado numa página descartável (Vite + Quasar 2.19.3 + Chrome headless): Tab pula o X e o readonly, clicar no X limpa sem abrir o menu e devolve o foco, multiple limpa tudo, slots option/prepend e showPopup funcionam. eslint exit 0 nos 6 apps, prettier ok. Regra escrita no CLAUDE.md. Falta o #13: Tab nas telas de verdade.

10/10/2026: o Fábio mandou atualizar o backlog e commitar; #13 marcado e task concluída.
<!-- SECTION:NOTES:END -->
