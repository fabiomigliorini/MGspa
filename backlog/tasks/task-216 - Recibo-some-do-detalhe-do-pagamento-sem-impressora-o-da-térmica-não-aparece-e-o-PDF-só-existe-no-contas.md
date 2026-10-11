---
id: TASK-216
title: >-
  Recibo some do detalhe do pagamento: sem impressora o da térmica não aparece e
  o PDF só existe no contas
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-10 21:18'
updated_date: '2026-10-11 01:35'
labels:
  - negocios
  - contas
dependencies: []
priority: low
type: bug
ordinal: 228000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No PDV (negócios), o detalhe do pagamento escondia o botão do recibo térmico quando o PDV não tinha impressora térmica configurada, e o recibo em PDF (Recibo, Recibo de Recebimento, Recibo de Pagamento) só existia no contas. No contas também escondia os recibos quando não havia título amarrado, quando cancelado e conforme recebimento/pagamento. Pedido do Fábio (10/10/2026): mostrar sempre os dois recibos (térmica e PDF) nos dois apps; sem pré-requisito, a ação recusa com mensagem.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Detalhe do pagamento mostra sempre os botões de recibo (PDF e térmica) no negócios e no contas
- [x] #2 Recibo da térmica abre o cupom para ver; no PDV o Imprimir manda para a térmica e, sem impressora, recusa com mensagem
- [x] #3 Recibo que não se aplica (cancelado, sem título, sem recebimento/pagamento) recusa com a mensagem do servidor em vez de sumir
- [x] #4 Número do título na parcela do diálogo de pagamento do negócio sai como link da casa (azul, sem sublinhado), não roxo e sublinhado do navegador
- [x] #5 Cupom da térmica: quem recebe o dinheiro declara e assina (cliente pagou: a loja; a loja pagou: a contraparte), com texto próprio de vale colaborador e de adiantamento (cliente e fornecedor)
- [ ] #6 Recibo A5 do contas/PDV é um só (sai Recibo Recebimento e Recibo Pagamento) e diz o mesmo que o cupom: cabeçalho com o que é (vale, adiantamento, recebimento, pagamento), quem recebe declara e assina, forma de pagamento, venda quando não tem título e o não amarrado
- [ ] #7 Recibo no jeito do bloco de papel (VALE / RECIBO grande, Nº, Valor, Nome ou Recebi(emos) de, A importância de, Referente a, Local/Data, Assinatura), A5 e cupom; observação do pagamento/título sai no Referente a; vale com 'autorizo o desconto em folha'; títulos e forma miúdos embaixo
- [ ] #8 Recebimento/pagamento de título: os títulos entram no recibo (A5 e cupom) com principal, juros, multa, desconto e total, e a linha de total
- [x] #9 Vale do colaborador: confissão de dívida com quem pagou (empresa e CNPJ da filial do pagamento), desconto autorizado em qualquer empresa do grupo (CLT ou terceirizado) e nota promissória com beneficiário em branco, vale e promissória na mesma folha A5 retrato com a mesma régua; no cupom também
- [x] #10 Campo 'Referente a' no pagamento (texto livre que sai no recibo/vale), preenchido no Receber/Pagar títulos e no Vale/Adiantamento e alterável pelo lápis; a observação fica interna e não sai no recibo
- [x] #11 Adiantamento a fornecedor: mesmo padrão do vale sem promissória (A5 paisagem e cupom), fornecedor declara e assina, texto de produto ou serviço abatido no que a loja ou o grupo dever e devolução se não houver o fornecimento; singular para pessoa física
- [x] #12 Vale de crédito (Vale Compras / Contra Vale, com o código VAL… para bipar no caixa) sai do título, desacoplado da venda: o mesmo papel na venda, na tela do título (contas) e na do pagamento; o recibo do adiantamento de cliente é esse vale; no PDV o card do título volta a ter o botão do vale
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Recibos saem dos slots dos apps e vão para o MgPagamentoDetalhe, sem v-if: Recibo / Recebimento / Pagamento (PDF A4, v1/pagamento/{id}/recibo*) e Recibo térmica (cupom, GET v1/pdv/pagamento/recibo/{id}, abrirPdf size cupom). Prop imprimirTermica: o negócios passa a função que manda para a térmica (recusa 'Nenhuma impressora térmica selecionada!'); no contas o cupom só abre. Arquivos: components/MgPagamentoDetalhe.vue, negocios/src/pages/PagamentoPage.vue, contas/src/pages/pagamento/Detalhe.vue.

Link da parcela: negocios/src/components/offline/PagamentoDialog.vue, o <a> do título ganhou class text-primary e text-decoration none (mesmo padrão do MgPagamentoDetalhe).

Texto do cupom (recibo-termica.blade.php): entrada 'Recebemos de {pessoa} (cód), CPF/CNPJ'; saída 'Recebi(emos) de {empresa}, CNPJ' ('Recebi' no vale). Referente: vale 'como vale, e autorizo o desconto deste valor em folha de pagamento'; adiantamento fornecedor 'a ser abatido em fornecimentos futuros'; adiantamento cliente 'a ser abatido em compras futuras'; resto 'referente aos títulos abaixo'. Assinatura com CPF/CNPJ; sem documento (Consumidor) não imprime. Constantes TipoTituloService::TIPO_VALE_COLABORADOR/ADIANTAMENTO_FORNECEDOR/ADIANTAMENTO_CLIENTE (120/121/211, fixos no tipo_titulo_limpeza.sql). Conferido renderizando 80248397, 80248399, 80248410, 80246812, 80247800.

Unificação: PagamentoReciboService::montar (dados e texto, um só) + carregar/pdf/pdfTermica. Layouts: pagamento/recibo.blade.php (A5 deitado, paginação de antes, rubricas da folha) e pagamento/recibo-termica.blade.php; a frase é o partial pagamento/_recibo-texto. Saíram as rotas v1/pagamento/{id}/recibo-recebimento e recibo-pagamento, os blades recibo-recebimento/_recibo-recebimento/recibo-pagamento/_recibo-pagamento, PdvPagamentoService::reciboPdf, PagamentoTituloService::temRecebimento/temPagamento e as flags recebimento/pagamento do PagamentoDetalheResource. Data = transacao. Venda sem título: 'referente à venda #'. Cancelado: 422. Conferido renderizando 80248397, 80248399, 80248410, 80246812, 80247800, 80248226 (venda), 80246616 (folha), 80247875 (não amarrado 2,50) e 80248335 (cancelado → 422). Recibos do acerto de RH (rh/acerto-recibos) não mexidos.

Layout bloco (10/10): recibo.blade.php e recibo-termica.blade.php montam os mesmos campos de PagamentoReciboService::montar (titulo VALE/RECIBO, numero, rotuloDe/de, extenso, referente, observacoes, declaracao do vale, formas, assinante com endereço, localData). Sai o partial _recibo-texto. Detalhe dos títulos flui em páginas (360 títulos do #80016998 = 11 páginas, cabeçalho da tabela repetido).

Títulos dentro do formulário (11/10): caixa 'Títulos' fora da table.linha (tabela dentro de célula o Dompdf não quebra); linha do adiantamento/vale lançado junto identificada pelo tipo; Referente a 'Recebimento/Pagamento do(s) título(s) abaixo'; Pagamento em + Local/Data lado a lado; data sem dia da semana (MESES no service). #80016998 (360 títulos) = 15 páginas com cabeçalho repetido e assinatura no fim.

Modelo por modelo (pedido do Fábio, 11/10). Vale: só forma de pagamento (Pagamento em + Local/Data), autorização 'autorizo o desconto deste valor dos meus pagamentos futuros' dentro da Assinatura (nem todo colaborador é CLT), Referente a só com observação, sem rodapé (título, lançado por). Pendente para o modelo Recibo: letra da tabela de títulos maior; sem linha de total quando é um título só; tirar detalhes desnecessários.

Vale sem CNPJ da empresa na autorização (colaborador pode ser de outra filial). formataValorPorExtenso (MgHelpers, usado também nos recibos do RH): acentos (três, milhão/milhões...), dezessete, 'mil' em vez de 'um mil', 'mil e um reais' no plural, 'um milhão de reais' sem espaço duplo e 'de' minúsculo.

Correção do formataValorPorExtenso desfeita (veio de um 'tudo' do ditado por voz, não foi pedida). O extenso continua como antes.

Empresa do recibo = Pessoa da filial do pagamento (PDV: filial do PDV; fora do PDV: filial do portador, no cartão a da maquineta; PagamentoTituloService::pagamentoDaForma). Tirado o fallback para a filial do título: o único pagamento sem filial (#80109856, 2023, cancelado, R$ 0,00) não tem recibo.

Vale fechado com o Fábio (11/10): texto aprovado (confissão + desconto em empresas do mesmo grupo econômico + devolver até o vencimento do título + 'Em garantia, emito a nota promissória a seguir'); NP na página 2 (A5 paisagem, pedido do Fábio), sem cláusula extra, beneficiário e CPF/CNPJ em branco (Súmula 387 STF), vencimento = do título, pagável na cidade da filial, emitente = colaborador com CPF e endereço. Cupom sem NP (papel térmico apaga). Próximos modelos, um por um: Adiantamento de Fornecedor, Adiantamento de Cliente, Pagamento de Títulos, Recebimento de Títulos, Encontro de Contas.

Vale + NP numa folha só (pedido do Fábio): A5 retrato, vale na metade de cima (min-height 92mm), linha de corte tracejada, NP na metade de baixo; letra menor (classe .meia). Texto volta a 'Em garantia, emito a nota promissória abaixo.' Os outros modelos seguem em A5 paisagem.

Vale num layout próprio (pagamento/vale.blade.php; PagamentoReciboService::pdf escolhe pelo tipo): A5 retrato, metades de 94mm iguais com a mesma régua (título 13pt numa linha, Nº/Vencimento/Valor nas mesmas larguras, rótulo 6pt, valor 9,5pt, texto 8pt, mesma caixa de assinatura); vale ganhou a caixa Vencimento; observação >160 caracteres sai em 7pt para caber. recibo.blade.php ficou só com o RECIBO (saíram os ramos do vale).

Promissória também no cupom (pedido do Fábio, ciente de que papel térmico apaga): vale da térmica num layout próprio (pagamento/vale-termica.blade.php; pdfTermica escolhe pelo tipo), mesmos campos e ordem do A5, corte tracejado e NP embaixo. recibo-termica.blade.php ficou só com o RECIBO. 'comprometo-me' e 'devolvê-lo' sem quebra no hífen.

Referente a (11/10): coluna tblpagamento.referente varchar(255) (api/database/pagamento_referente.sql, rodado no dev; prod no go-live). Backend: fillable, validação nos StoreRequests (adiantamento, baixa) e UpdateRequest (lápis), PagamentoTituloService (pagamentoDaForma, PIX, pagamentoExistente preenche se vazio, atualizar), PixUsoService::pagar (linha avisada à sessão do TASK-219), PagamentoDetalheResource. Front: MgAdiantamentoDialog e MgBaixaTitulos ('Referente a (sai no recibo)' + 'Observação (interna)'), lápis do contas, linha no MgPagamentoDetalhe. Recibo lê só o referente (sai título.observacao e pagamento.observacoes). Testado com rollback: lápis em #80248397 e lançamento de vale novo.

Promissória: 'a ____' numa linha própria com o branco até a borda (95% no A5, 92% no cupom) e 'CPF/CNPJ ____' na linha de baixo (pedido do Fábio). Conferido com data e valor por extenso compridos.

Promissória: o branco do beneficiário volta para a linha do 'NOTA PROMISSÓRIA a' e vai até o fim dela (PagamentoReciboService::brancoDaLinha mede o texto com as métricas do Dompdf, Helvetica; A5 379pt/8pt, cupom 200pt/8,5pt; se sobrar pouco, o branco vai inteiro na linha de baixo); CPF/CNPJ na linha de baixo. Conferido com 4 datas. Vale A5 sem a borda grande de fora (pedido do Fábio).

Adiantamento a fornecedor (11/10): layouts do vale renomeados para pagamento/adiantamento e adiantamento-termica (servem vale e fornecedor; título, rótulo e vencimento vêm do service). Sem promissória (sugestão aceita pelo Fábio). Fornecedor com endereço na caixa. Conferido: #80248399 (PF, singular) e simulação PJ em memória (plural).

Adiantamento a fornecedor em A5 paisagem (pedido do Fábio: sem promissória não precisa de retrato): @page pelo $r['promissoria'], classe .inteira com os mesmos campos maiores (título 22pt, valor 12pt, texto 10pt); vale segue em retrato (.meia).

Adiantamento de cliente NÃO ganha recibo próprio (decisão do Fábio, 11/10): o documento dele é o vale de crédito do caixa (Vale Compras / Contra Vale, saldo do título e código de barras para bipar no PDV). Variante 'cliente' desfeita. Ficaram dois ajustes gerais: autorização do pagamento numa linha miúda embaixo da forma (o E2E do PIX é comprido) e CPF/CNPJ sem quebrar no hífen; e o layout de adiantamento só quando o pagamento inteiro é o lançamento (sem baixa junto e sem sobra livre), senão recibo com a tabela.

Vale de crédito desacoplado (11/10): TituloValeService (titulos/pdf/pdfTitulos/imprimir), blade titulo/vale.blade.php (era negocio/vale) recebendo só títulos, um vale por título (nunca soma saldo); o blade decide pelo título: saldo usado em parte = Contra Vale; ligado a venda de vale compras (Titulo::NegocioVale) = Vale Compras; senão Adiantamento; itens do kit só com saldo nunca usado. Rotas GET v1/titulo/{cod}/vale (titulo.vale, auth_or_signed) e POST v1/pdv/titulo/{cod}/vale/{impressora}. Pdv\ValeService::pdf delega (a venda passa os títulos dela). Recibo do adiantamento de cliente com saldo = o vale. Front: @components/abrirVale, botão Vale no detalhe do pagamento (títulos com saldo de crédito; Imprimir na térmica no PDV), na tela do título (contas) e nos cards do PDV (ListagemTitulos voltou a mostrar: testava o tipo 3, que não existe desde a renumeração; ListagemContraVale). Testado: 650457 adiantamento, 650176 vale compras com itens, 650177 contra vale sem itens, 650085/649974 crédito, venda 4541457, rota assinada 200 / sem login 401 / sem crédito 422.

Vale de crédito: crédito de devolução (tipo 212 Crédito Cliente) sai como 'Vale Devolução' quando nunca usado; usado em parte, Contra Vale (pedido do Fábio).
<!-- SECTION:NOTES:END -->
