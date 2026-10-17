---
id: TASK-236
title: >-
  Baixa de títulos lançada na forma ou no banco errado obriga a selecionar todos
  os títulos de novo
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-17 19:58'
updated_date: '2026-10-17 22:54'
labels:
  - contas
dependencies: []
priority: medium
type: feature
ordinal: 247000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pedido do Fábio (17/10/2026): o usuário amarra a baixa ao banco errado, ou registra em dinheiro o que foi cartão. Com vários títulos, cancelar e refazer obriga a selecionar tudo de novo no Receber ou Pagar Títulos.

Decisão: a forma não se troca no lugar. Ficam duas ações independentes no detalhe do pagamento do contas: Cancelar (já existe, estorna as baixas e reabre os títulos) e Duplicar (abre a baixa com os mesmos títulos e valores já preenchidos). Duplicar não cancela o original sozinho.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Botão Duplicar no detalhe do pagamento de títulos (contas) abre o Receber ou Pagar Títulos com os mesmos títulos, valores, pessoa, data, referente e observação já preenchidos; a forma é escolhida de novo
- [ ] #2 Duplicar não cancela o original; com o original ainda valendo, recusa dizendo para cancelar antes
- [ ] #3 No Editar do pagamento lançado à mão em dinheiro, banco ou cartão da empresa, o portador troca por outro do mesmo tipo: o razão sai de um e entra no outro, as baixas acompanham e a auditoria guarda o antes e o depois com justificativa; período igual ao alterar data (operador com os dois abertos, gestor também pendente, fechado ninguém)
- [ ] #4 Editar e Receber/Pagar usam o mesmo formulário (mesma aparência e campos: data, pessoa, referente, observação); o que não pode mudar fica em somente leitura
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (17/10/2026, aguardando teste do Fábio; nada commitado):

Servidor
- PagamentoDetalheResource: cada linha de baixa ganha duplicavel (Liquidação 600 e Amortização 300) e o título ganha operacao (DB a receber, CR a pagar, como na tela de baixa). O pagamento ganha duplicavel = não é venda nem acerto de RH, não é baixa do banco (boleto) e tem linha de baixa. Vale e adiantamento (Implantação) ficam de fora.
- Nenhuma rota nova: a baixa duplicada é a mesma POST v1/pagamento.

Telas
- Detalhe do pagamento (contas): botão Duplicar (content_copy) no cabeçalho, ao lado do lápis, com :to para pagamento/novo?duplicar=ID.
- Receber ou Pagar Títulos (pagamento/Nova.vue): com ?duplicar=, lê o pagamento e abre com os títulos, valores (capital, juros, multa, desconto, total), pessoa, data com hora, referente e observação dele. A forma se escolhe de novo no wizard. Original ainda valendo: recusa ao abrir ('Cancele o pagamento #N antes de duplicar: os títulos dele continuam baixados') e a tela fica vazia, como o PIX já usado.
- MgBaixaTitulos ganhou a prop inicial; sem ela (PDV e contas sem duplicar) nada muda.

Testes
- Lint e prettier: contas (Nova, Detalhe) e negocios (usos do MgBaixaTitulos) sem erro.
- Tinker, transação desfeita: o pagamento cancelado 80247912 (dinheiro, Caixa Centro, 2 títulos, um a receber e um a pagar) duplicado com o mesmo mapa da tela, em dinheiro no Caixa Financeiro: grava os mesmos títulos e valores, na data do original, e o original continua cancelado. Com a data de hoje, também grava. O efetivado 80248409: o servidor recusa pelo saldo. Venda, vale e boleto: duplicavel false. Nada ficou gravado.
- Não testado no navegador: gerar token de acesso para o Chrome headless foi recusado pela permissão da sessão.

Limite conhecido: 2 pagamentos antigos baixam o mesmo título em duas linhas; duplicados, o servidor recusa ('O mesmo título está duas vezes na baixa').

Roteiro de teste
1. Contas, Pagamentos, um recebimento de títulos ainda valendo: Duplicar recusa pedindo para cancelar antes.
2. Cancelar esse pagamento; no detalhe cancelado, Duplicar: a tela abre com os mesmos títulos marcados e os mesmos valores; no botão de baixo, data, pessoa, referente e observação do original.
3. Receber, escolher a forma certa (outro banco, cartão, dinheiro): grava e abre o detalhe do pagamento novo; o original continua cancelado.
4. Pagamento de venda, vale/adiantamento e boleto não mostram o Duplicar.

Revisão (17/10/2026, depois do commit 5df4f298f; aguardando teste do Fábio, nada commitado):

Pedido do Fábio: trocar o portador por outro do mesmo tipo no lápis, idempotente, com a regra de período do alterar data; e o Editar com a mesma aparência do Receber, num formulário só, o que não muda em somente leitura.

Servidor
- LancamentoDataService::alterarPortador: só o trocável (PagamentoTituloService::portadorTrocavel: efetivado, sem venda/acerto/devolução, manual, em dinheiro, meio de banco ou cartão da empresa na saída); mesmo tipo, ativo; exigirAberta; permissão igual à da data com o portador de onde sai e o para onde vai (antes do go-live, só o gestor); dinheiro vai para a sessão do caixa novo na hora do pagamento; sincronizar o razão; baixas (tblmovimentotitulo.codportador) levam o portador novo; auditoria tipo 21 Portador alterado (fotoPagamento ganhou codportadororigem/destino; só o que mudou fica).
- Lápis (PagamentoTituloService::atualizar): data primeiro, depois o portador; aceita referente. O meio continua sem mudar ('cancele e use o Duplicar').
- Resource: portadortrocavel e portadortipo.

Telas
- @components/MgPagamentoForm.vue: o card do pagamento (título e valor, data | pessoa, referente, observação; slot para campos da tela, rodape e acoes). Usado pelo Receber/Pagar (MgBaixaTitulos, contas e PDV) e pelo Editar do detalhe.
- Editar: portador com o meio como rótulo, só do mesmo tipo e onde o usuário é operador ou gestor; somente leitura quando não troca. Data em somente leitura no integrado (antes sumia). Justificativa quando a data ou o portador muda. Referente agora também no Editar (o botão próprio do detalhe continua, para venda e acerto).

Testes (tinker, transação desfeita)
- Banco para banco (80248455, Bb Matriz → Bb Fdf): razão inativa no 1/período 114 e cria no 2/período 128; baixa leva o 2; auditoria tipo 21 com antes/depois e justificativa; referente gravado; repetir não muda razão nem auditoria.
- Dinheiro (80248459, Caixa 06 Centro → Gaveta Dev Fabio): sessão 105 → 47, razão e baixa acompanham.
- Recusas: sem justificativa; banco para caixa (tipo diferente); PIX que caiu na conta (não trocável); destino fechado (administrador); destino pendente com operador; depositante nos dois.
- Gravam: operador com tudo aberto; gestor com destino pendente (processo novo: o PortadorAutorizador guarda o papel em memória).
- Lint e prettier (contas e negocios) e compilação dos quatro .vue sem erro. Não testado no navegador.
<!-- SECTION:NOTES:END -->
