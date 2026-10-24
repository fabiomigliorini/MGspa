---
id: doc-6
title: Plano da requisição entre lojas, entregas do negócio e impressoras
type: other
created_date: '2026-10-21 20:16'
---

# Requisição entre lojas + entregas do negócio + impressoras em tabela

## Context

Hoje, quando a loja A vende e não tem estoque, o caixa entrega ao cliente um "vale" de papel para
retirar a mercadoria na loja B. Nada disso está no sistema: B não fica sabendo antes, ninguém acompanha o
que foi separado, a transferência é digitada à mão depois, e não há prova de que o cliente certo retirou.
A reposição entre lojas também é manual (PDF "Sugestão de Transferências" + negócio digitado), e não
existe controle de entrega de transferências nem de vendas (TASK-55).

Resultado esperado:
- uma **requisição** autônoma e desacoplada (quem pede, quem atende, itens, código de retirada, painel por
  loja, impressão automática), atendida por **negócios de transferência do PDV** (a separação é o próprio
  negócio, offline), com um processo idempotente que calcula o atendido e o status;
- um **controle de entregas do negócio** que vale para qualquer transferência e, depois, venda com entrega;
- indicadores de qualidade do estoque no detalhe do **Local de Estoque**;
- de quebra: impressoras saem do `printers.json` para tabela, e `codestoquelocaldestino` passa a valer.

Princípios (do Fábio): cada tela cuida da sua coisa, autônoma; processos desacoplados e idempotentes que
reprocessam cada evento sempre que o usuário quiser; o sistema não bloqueia corrigir a realidade, mostra a
incoerência e o usuário conserta; o usuário tem autonomia — o sistema não tenta fazer o serviço dele.
Execução **do mais fácil para o mais difícil**: limpezas e cadastros simples primeiro, separação no PDV e
o que depende dela por último.

## O que existe hoje (levantamento — repo /opt/www/MGspa, banco `mgsis`)

- **Loja** = `tblestoquelocal` (1 por filial, `codfilial*1000+1`; 101001 DEP é depósito). Usuário não tem
  `codestoquelocal`; o dispositivo (`tblpdv`) tem `codestoquelocal` e `impressora` (texto). A API de local
  de estoque só tem `index`/`show`.
- **Transferência** = negócio natureza 15 (Transferencia Saida; preço = venda, NF aplica ×0,7), pessoa =
  pessoa da filial destino. `tblnegocio.codestoquelocaldestino` existe e está **nulo nas ~95 mil
  transferências**. Estoque movido pelo MGLara (`/opt/www/MGLara/app/Jobs/EstoqueGeraMovimentoNegocioProdutoBarra.php:164`),
  que acha o destino por pessoa → filial → primeiro local. Fechamento: `PdvNegocioService::fechar`
  (`api/app/Mg/Pdv/PdvNegocioService.php`); cancelar (`:631`); reabrir fechado **ou cancelado**
  (`PdvNegocioReaberturaService::reabrir`). No PDV, fechar exige estar sincronizado
  (`negocios/src/stores/negocio.js:2164`) e, em natureza de transferência, já emite a NF-e 55
  (`romaneioOuNota`); Duplicar é cópia JSON do negócio (`negocio.js:757`).
- **NF-e** do negócio: `NotaFiscalNegocioService::gerarNotaFiscalDoNegocio`; entrada no destino nasce pelo
  comando horário `nota-fiscal:transferencia-entrada`.
- **Impressão térmica**: o servidor publica no Ably (canal `printing`, evento = nome da fila do CUPS) uma URL
  assinada de PDF 80 mm; o agente `/opt/MGprint/bin/index.js` roda `lp -d <nome>`. Modelo:
  `api/app/Mg/Pdv/RomaneioService.php::imprimir` (QR via chillerlan). Catálogo: `api/printers.json` (30),
  lido por `Mg\Select\SelectImpressoraController` e `PdvService::impressora`.
- **Leitor do PDA**: `negocios/src/composables/useCursorNoLeitor.js` (TASK-244); QR lido no PDV já existe
  para a confissão (`MGCONF|…`, `components/MgConfissaoScanner.vue`). **Sem tempo real**: polling
  (`components/cobranca/useConsultaAutomatica.js`).
- **Sugestão abaixo do mínimo**: `api/app/Mg/Estoque/EstoqueSaldoRelatorioService.php::transferencias`.
- **`tblpedido` / `tblpedidoitem` / `tblnegocioprodutobarrapedidoitem`**: 0 linhas, esqueleto em
  `api/app/Mg/Pedido/`, sem tela. "Vale" do sistema (`Mg\Vale`) é vale-compras, não vale de retirada.
- **Entrega no negócio**: `tblnegocio.entrega` (bool) / `acertoentrega` / `codusuarioacertoentrega` parados
  desde 2014 (4.302 `entrega=true`, nenhum acerto).
- **Regras da casa** em `/opt/www/MGspa/CLAUDE.md` (MgInput/MgSelect, axios em `src/services/api.js`, datas
  em `@components/utils/datas.js`, uuid do PDV só no `X-Pdv`, um botão = uma coisa, task/commit só com
  autorização).

## Achados (relatar, não viram task)

- `NFePHPService::imprimir` devolve no JSON o `curl` com a **chave do Ably** (`NFePHPService.php:1404`).
  Tirar da resposta já na etapa 0; as 10 publicações `exec(curl …)` passam a `ImpressoraService::publicar`
  na etapa 8.
- `NegocioService::movimentaEstoque` tem um `dd($ret)` (`NegocioService.php:164`). Fora do caminho da requisição.
- `PdvNegocioService::fechar:496-508`: o laço do `$mesmaEmpresa` sobrescreve (só a última filial conta).
  Corrigido na etapa 6.
- O job do MGLara também gera entrada no destino em **venda/uso para filial de outra empresa do grupo**
  (25 negócios natureza 1, 2016–2024, e natureza 19). Tratado na etapa 6.
- `tblnegocio` tem 5,4 mi e `tblnegocioprodutobarra` 18,9 mi de linhas: coluna nova sem default, FK
  `NOT VALID` + `VALIDATE`, índice `CONCURRENTLY`.

## Decisões finais

**Requisição**
1. Requisição ≠ Pedido (Pedido é do departamento de compras). Tabelas de pedido removidas (etapa 0).
2. Destino da mercadoria: **R** cliente retira na loja requisitada / **E** entregar no endereço do cliente /
   **L** vai para a loja requisitante (reposição). `atendimento` **L** loja / **C** compras (só estrutura).
   Fiscal fora de discussão: todos geram transferência B→A; na entrega vai a NF-e de transferência + a nota de venda.
3. Item pedido = `codprodutobarra` + quantidade; **a mesma variação pode repetir** em embalagens diferentes
   (ex.: 1 CX c/10 resmas + 5 pacotes avulsos); não repete a mesma barra.
4. Criar: no painel (do zero, `MgInputProdutoBarras`) ou a partir de negócio **fechado** (itens e cliente
   preenchidos e editáveis; guarda o negócio de origem). Requisitante edita itens e cliente **só enquanto
   aguardando**. Cancelar: PDV da filial **requisitante** ou **Gerente**; recusa se houver negócio ativo
   ligado (mostra o link do negócio, onde se cancela).
5. Código de retirada de 6 dígitos, só no lado A (comprovante e tela da A; nunca na tela nem na via da B).
6. O que faltou: nova requisição à mão. Requisição parada: sem prazo; o painel ordena das mais antigas.

**Separação = negócio do PDV**
7. A loja B separa num **negócio de transferência no PDV**, offline. Fechar o negócio = uma parte separada
   (emite a NF-e como hoje); próxima parte = outro negócio. Reabrir e Duplicar funcionam.
8. Ligar negócio ↔ requisição a qualquer momento (antes, no meio ou depois de bipar): botão **Separar** na
   requisição; **bipar o QR** da via de separação (URL com nº + loja destino); **digitar o nº**. Ligar põe
   natureza de transferência; loja destino vem do QR ou o usuário preenche (obrigatório).
9. **Um negócio pode atender várias requisições** com o mesmo destino da mercadoria; retirada/entrega só
   junta com mesmo `codpessoa`, mesma loja de origem e de destino.
10. **No Fechar** (online), o servidor confere a coerência de cada requisição ligada — existe, não cancelada
    nem finalizada, requisitada = loja do negócio, requisitante = loja destino, natureza de transferência,
    mesmo destino da mercadoria (e mesmo cliente em R/E) — e, se divergir, **recusa mostrando a divergência**.
11. O PDV não mostra os itens da requisição; só o link do negócio para ela.

**Processo idempotente e status**
12. O negócio **não faz nada** de requisição além de guardar a lista de requisições ligadas e **disparar** o
    processamento delas nos seus eventos. O processo (domínio da requisição) lê as linhas ativas dos
    negócios fechados ligados, distribui por item e grava **na requisição** o atendido de cada item.
13. Distribuição: cada linha vai para o item da mesma variação (entre todas as requisições do negócio) com a
    **menor diferença entre o total da linha e o que ainda falta** no item (quantidade × embalagem);
    empate → requisição mais antiga (`criacao`). Item já atendido só recebe mais se não houver outro da
    mesma variação faltando (vira excesso). Sem item da variação = **fora do pedido**. Atendido × pedido é
    mostrado na **menor embalagem** envolvida ("faltam 3 pacotes").
14. Status é **coluna** (nunca calculado na consulta), gravada só pelo processo:
    **A** aguardando · **S** em separação (há negócio ligado aberto) · **P** separado parcial / pendente ·
    **T** separado total (tudo bateu: atendido = pedido em todos os itens e nada fora do pedido — o processo
    finaliza sozinho) · **D** separado com divergências (finalizado à mão) · **E** entregue (finalizada e
    todas as partes entregues) · **C** cancelada. "Separado" = pronto para retirar.
15. Pendência: a loja **requisitada** ou **Gerente** abre a requisição, lança por item **motivo**
    (estoque do sistema errado, avariado/vencido, não localizado, enviado a mais, sem saldo) + observação +
    **substituído por** (campo à parte, complementar ao motivo), e **Finaliza** (→ D). Produto fora do pedido
    aparece listado; quem justifica marca como substituto de um item ou inclui como item com pedido 0 e
    motivo "enviado a mais".
16. Reprocessamento: a cada evento o processo roda de novo. **Reabriu ou cancelou** um negócio ligado → a
    finalização manual cai e volta a pendente (as justificativas ficam); **fechou e atendeu tudo** → T
    sozinho. Botão **Reprocessar** na requisição roda o mesmo processo.

**Entregas (do negócio)**
17. `tblnegocio.entrega` = "tem entrega a controlar" (padrão na transferência; à mão na venda).
    `tblnegocioentrega` registra cada tentativa: **entregue / entregue com problema / não entregue**, motivo
    (com problema: mercadoria errada, avariada, faltando item; não entregue: cliente ausente, recusou,
    endereço não encontrado), recebedor, observação. `tblnegocio.entregue` (data) gravado pelo serviço.
18. Duas listas por loja: **Entregas a fazer** (origem: retirada e entrega ao cliente) e **A receber**
    (destino: reposição e transferência).
19. Negócio de requisição R/E se entrega **pela requisição**, com o código de **todas** as requisições do
    negócio; sem código, com justificativa obrigatória (marca "entregue sem código"). Reposição (L): a loja A
    registra o recebido, sem código.

**Impressão e impressoras**
20. Um blade, duas vias × térmica/A5: via **requisitante** (autorização de retirada com o código) na térmica
    do **PDV que registrou**; via **requisitada** (lista de separação com QR, sem código) na impressora do
    **local de estoque**. Sai sozinha ao criar e tem botão Imprimir em cada lado.
21. `tblimpressora` (chave = fila do CUPS = evento do Ably, tipo térmica/A5/etiqueta); `codimpressora` em
    PDV, local de estoque, todas as rotas e no front; cadastro no **PDV** (app negocios); limpeza das
    impressoras do usuário.

**Local de estoque e indicadores**
22. CRUD de Locais de Estoque no app estoque; o **detalhe do local** é o painel de indicadores de qualidade do
    estoque dele como requisitado.
23. `codestoquelocaldestino` em toda natureza que gera movimento no destino; backfill do histórico; select
    "Loja destino" no PDV; MGLara usa só o campo.

## Plano — do mais fácil para o mais difícil

Padrão de DDL: SQL idempotente em `api/database/*.sql` (modelo `ocorrencia.sql`, `pdv_configuracao.sql`);
DROP em `*_drop.sql` rodado depois do deploy; SQL de dados de go-live rodado pelo Fábio. Cada etapa
termina numa tela onde o Fábio confere (**Confere**).

### Etapa 0 — Limpezas (só apagar)
- `pedido_drop.sql` (aborta se houver linha); apagar `api/app/Mg/Pedido/`,
  `Mg\Negocio\NegocioProdutoBarraPedidoItem`, relações em `GrupoEconomico`, `ProdutoVariacao`,
  `NegocioProdutoBarra`, `EstoqueLocal`; rotas `api.php:616-623`; entradas em `app/Mg/Gerador/IndiceModels.json`;
  views/rotas de pedido no `quasar/` legado.
- `usuario_impressora_drop.sql`; tirar dos models (api e MGLara), da validação de `UsuarioController::store`,
  do fallback de `NFePHPService::imprimir`; apagar `MGLara/app/Library/EscPrint/`; telas de impressora do
  usuário no `quasar/`.
- `negocio_acertoentrega_drop.sql`: zera os 4.302 `entrega=true` de 2012–2014; dropa
  `acertoentrega`/`codusuarioacertoentrega` (models api/MGLara, `negocios/src/stores/negocio.js:475`).
- Tirar o `"commando"` (com a chave do Ably) da resposta de `NFePHPService::imprimir`.
- **Confere:** criar/editar usuário sem impressora; impressão do PDV e DANFE seguem saindo.

### Etapa 1 — Cadastro de Impressoras (app negocios, o PDV)
- `impressora.sql`: `tblimpressora` (`impressora` varchar único = fila do CUPS, CHECK de caracteres;
  `descricao`; `tipo` char(1) T/A/E; `inativo`; auditoria); carga dos 30 do json (argox = E, resto = T).
- `Mg\Impressora` (CRUD, inativo; escrita Administrador) + `ImpressoraService::publicar()` (HTTP para o Ably,
  recusa inativa) — usado pelo código novo a partir daqui.
- `SelectImpressoraController` e `PdvService::impressora` passam a ler a tabela, devolvendo o mesmo de
  hoje (a chave); apaga `api/printers.json`. A troca para `codimpressora` nas rotas e no front fica para a
  etapa 8.
- negocios: `pages/ImpressoraPage.vue` (`/impressora`) + `stores/impressora.js` + item no menu do `MainLayout.vue`.
- **Confere:** Negócios → Impressoras (30, com tipo, editar/inativar); Meu Dispositivo e etiquetas seguem
  listando e imprimindo.

### Etapa 2 — Cadastro de Locais de Estoque (app estoque)
- `estoquelocal_impressora.sql`: `tblestoquelocal.codimpressora` (FK).
- API: `POST|PUT v1/estoque-local`, `POST|DELETE v1/estoque-local/{id}/inativo` (Administrador), incluindo
  a impressora; `index`/`show` devolvem a impressora.
- estoque: `pages/estoque-local/Index.vue` (lista) + `Detalhe.vue` (dados do local, editar em diálogo,
  inativar) + store + menu. Os indicadores entram neste detalhe na etapa 11.
- **Confere:** Estoque → Locais de Estoque; edita o Centro e escolhe a impressora.

### Etapa 3 — Requisição: cadastro, detalhe e painel
- `requisicao.sql`:
  - `tblrequisicao`: `codestoquelocalrequisitante`, `codestoquelocalrequisitada`, `atendimento` L/C,
    `destino` R/E/L, `codpessoa`, `nome`, `telefone`, `endereco`, `codigoretirada` char(6), `codnegocio`
    (origem), `codpdv` (que registrou), `observacoes`, **`status` char(1)** A/S/P/T/D/E/C, `finalizacao` +
    `codusuariofinalizacao` (manual), `cancelamento` + usuário, auditoria; CHECKs de coerência; índices
    `(codestoquelocalrequisitada, status)`, `(codestoquelocalrequisitante, status)`, `(codnegocio)` parcial.
  - `tblrequisicaoitem`: `codprodutobarra`, `quantidade` ≥ 0 (0 = incluído como "enviado a mais"),
    `quantidadeatendida` (gravada pelo processo, unidade base), `motivo` char(1), `codprodutobarrasubstituto`,
    `observacao`, auditoria; único `(codrequisicao, codprodutobarra)`.
- Domínio `Mg\Requisicao` (rotas com middleware `pdv`): `GET v1/requisicao/painel` (as abertas da loja, dos
  dois lados), `GET v1/requisicao` (listagem paginada, todos os status, filtros opcionais), `POST v1/requisicao`,
  `GET|PUT v1/requisicao/{id}` (PUT só aguardando), `POST v1/requisicao/{id}/cancelar`.
  `codigoretirada` em `$hidden`; só volta quando o PDV é da filial requisitante.
- `RequisicaoProcessamentoService::processar(codrequisicao)` (esqueleto: A e C; a distribuição entra na
  etapa 9) grava a coluna `status`.
- negocios: `RequisicaoPainelPage` (`/requisicao`; uma aba por loja, abrindo na do PDV; na aba, o card
  "Para Centro atender" e um card "Centro pediu para Deposito" por loja atendente, cada um agrupado por
  status; só as abertas, mais antigas primeiro, polling 30 s com `useConsultaAutomatica`),
  `RequisicaoListagemPage` (`/requisicao/listagem`, botão Todas do painel; entregues e canceladas ficam aqui:
  como a listagem de negócios, filtros no painel lateral — nº, status, destino, negócio, criação, lojas,
  pessoa, quem retira, telefone, código de retirada inteiro com o limite de tentativas da entrega —,
  rolagem infinita, mais nova primeiro, sem filtro padrão), `RequisicaoFormPage`
  (`MgInputProdutoBarras` com F1), `RequisicaoDetalhePage`, `stores/requisicao.js`, menu.
- **Confere:** cria no Botânico (vê o código); aparece no painel do Centro como Aguardando, sem código;
  edita enquanto aguardando; cancela.

### Etapa 4 — Impressões da requisição
- `api/resources/views/requisicao/requisicao.blade.php` com `$via` × `$formato`; QR (chillerlan) = URL com
  nº e destino; via B sem o código; localização (corredor/prateleira).
- `RequisicaoImpressaoService` + `ImprimirRequisicaoJob` (após o commit do criar, via
  `ImpressoraService::publicar`): via A → impressora do PDV que registrou; via B → impressora do local
  requisitado; formato pelo `tipo`. `GET v1/requisicao/{id}/pdf` (assinado),
  `POST v1/requisicao/{id}/imprimir`; botões no detalhe.
- **Confere:** requisição nova imprime a via do cliente no PDV e a de separação no Centro; QR abre o
  detalhe pela câmera; PDF A5 pelo botão.

### Etapa 5 — Facilitadores
- Botão "Requisitar" no negócio fechado → `/requisicao/nova?codnegocio=` pré-preenchido; negócio mostra as
  requisições de origem.
- `GET v1/requisicao/sugestao` com o SQL de `EstoqueSaldoRelatorioService::transferencias` (extraído para
  método de linhas); barra da `codprodutoembalagemtransferencia`; botão "Sugerir abaixo do mínimo" só
  preenche a lista.
- **Confere:** venda → Requisitar preenchido; sugestão bate com o PDF de Sugestão de Transferências.

### Etapa 6 — `codestoquelocaldestino`
- `negocio_estoquelocaldestino.sql` (go-live, **antes** do deploy do MGLara): 1) copia o local do movimento
  de destino já existente (naturezas 15, 1, 19); 2) transferências restantes: pessoa → filial única → local
  único; 3) conferência (252 transferências com pessoa que não é filial ficam nulas).
- MGLara `EstoqueGeraMovimentoNegocioProdutoBarra.php:164`: destino = `Negocio->EstoqueLocalDestino`.
- `PdvNegocioService`: em natureza cujo tipo de movimento gera destino, grava o destino (pessoa = filial do
  destino); `fechar` valida destino informado, ativo, diferente da origem e, na transferência, mesma empresa
  (sai o laço bugado).
- negocios `components/offline/DetalheNegocio.vue`: select "Loja destino" no diálogo de natureza (offline,
  do Dexie); `stores/negocio.js::informarNatureza(..., codestoquelocaldestino)`.
- **Confere:** transferência nova com Loja destino → entrada aparece no destino; sem destino é recusada;
  transferência antiga mostra o destino.

### Etapa 7 — Entregas do negócio (TASK-55)
- `negocio_entrega.sql`: `tblnegocioentrega` (`codnegocio`, `data`, `resultado` E/P/N, `motivo` com CHECK
  por resultado, `recebedor` obrigatório em E/P, `observacao`, `codigoconferido`, `justificativa`,
  `inativo`, auditoria); `tblnegocio.entregue timestamp(0)`; índices parciais `(codestoquelocal)` e
  `(codestoquelocaldestino) WHERE entrega AND entregue IS NULL` (concurrently).
- `Mg\Negocio\NegocioEntrega*`: `GET v1/negocio/entrega/a-fazer`, `GET v1/negocio/entrega/a-receber`,
  `POST v1/negocio/{codnegocio}/entrega`, `POST v1/negocio-entrega/{id}/inativo`. O serviço grava/limpa
  `tblnegocio.entregue`.
- negocios: checkbox "Controlar entrega" (padrão na transferência); "Registrar entrega" no negócio fechado
  (`DialogEntrega.vue`, cards `ListagemEntregas.vue`); páginas **Entregas a fazer** e **A receber** por loja.
- **Confere:** transferência fechada aparece em A receber no destino; "não entregue — ausente" fica com a
  tentativa; "entregue" sai da lista e aparece no negócio.

### Etapa 8 — `codimpressora` em todo lugar
- `tblpdv.codimpressora` (FK) + conversão `tblpdv.impressora` → código; `impressora_drop.sql` depois.
- Os 10 `exec(curl)` passam a `ImpressoraService::publicar`; `resolver()` aceita código e, na transição, a
  chave (PWA antigo).
- Rotas de impressão passam a `{codimpressora}` (romaneio, vale, comanda, vale do título, recibo, borderô,
  fechar/devolução, nota-fiscal imprimir, comanda do vendedor, QR do PIX, etiqueta); select devolve o código.
- Front: `@components/MgSelectImpressora.vue` (prop `tipo`, valor numérico); props `codimpressora` em
  `MgNotaFiscalAcoes`, `PixCobDialog`, `pixStore`, `MgBaixaTitulos`, `periodoStore`; negocios
  (`stores/negocio.js`, `sincronizacao.js` sem sincronizar impressora, `pagamento.js`, `dispositivo.js`,
  `boot/db.js` v11 remove a tabela, páginas de pagamento/caixa/comanda/dispositivo); apagar
  `negocios/src/components/selects/SelectImpressora.vue`; estoque etiqueta com `tipo="E"`; notas
  `notaFiscalService.js`, `notaFiscalStore.js`; README do MGprint.
- **Confere:** Meu Dispositivo escolhe a térmica; romaneio, comanda, recibo, vale, borderô, DANFE, PIX e
  etiqueta saem; PDV com versão antiga continua imprimindo até atualizar.

### Etapa 9 — Separação no negócio do PDV + processo (a mais difícil)
- `requisicao_negocio.sql`: `tblnegociorequisicao` (`codnegocio`, `codrequisicao`, `uuid`, `inativo`,
  auditoria; único ativo por par) — sincronizada junto com o negócio, como os vales.
- PDV (negocios, offline): lista de requisições do negócio; ligar por **QR** (o `InputBarras` reconhece a
  URL `…/requisicao/{nº}?destino={local}`), por **nº digitado** (diálogo) ou chegando pelo **Separar** da
  requisição (abre negócio novo já ligado). Ligar põe natureza de transferência de saída e loja destino
  (do QR ou preenchida pelo usuário, obrigatória). Link do negócio para cada requisição. Duplicar copia a lista.
- `PdvNegocioService::fechar`: conferência de coerência de todas as requisições ligadas (decisão 10); recusa
  com a divergência. Jobs e MGLara em `afterCommit`.
- Disparo: `PdvNegocioService` (sincronização que muda a lista, fechar, cancelar, reabrir) só despacha
  `ProcessarRequisicaoJob(codnegocio)` depois do commit; o job acha as requisições ligadas.
- `RequisicaoProcessamentoService::processar`: pega o grupo (requisições que compartilham negócios), lê as
  linhas ativas dos negócios fechados ligados (ordem determinística: lançamento do negócio, ordem da linha),
  distribui pela decisão 13, grava `quantidadeatendida`, decide o status (decisões 14 e 16) e grava. Botão
  **Reprocessar** (`POST v1/requisicao/{id}/reprocessar`).
- Justificar e finalizar (requisitada ou Gerente): `PUT v1/requisicao-item/{id}` (motivo, observação,
  substituto), incluir item pedido 0, `POST v1/requisicao/{id}/finalizar` (exige motivo em cada item
  divergente e produto fora do pedido resolvido).
- negocios `RequisicaoDetalhePage`: itens com pedido × atendido (menor embalagem), lista "fora do pedido",
  partes (negócios) com link, justificativas e Finalizar.
- **Confere:** no PDA do Centro, bipa offline, liga pelo QR, fecha → NF-e sai → requisição "separado
  parcial" com o atendido; bipa o resto noutro negócio → "separado total" sozinho; cancela um negócio →
  volta a pendente; reabre/duplica; finaliza com motivo → "com divergências"; negócio ligado a requisição
  de outra loja é recusado no Fechar.

### Etapa 10 — Entrega pela requisição
- `tblnegocioentrega.codrequisicao` (FK) + CHECKs de código/justificativa só com requisição.
- `POST v1/requisicao/{id}/entregar` (throttle): partes fechadas sem entrega; R/E exigem os códigos de
  todas as requisições do negócio (`hash_equals`) ou justificativa; L registrado pela loja A sem código.
  Grava `tblnegocioentrega` por negócio e dispara o processamento (status E).
- **Confere:** código certo → Entregue; sem código → justificativa + selo; nova parte entrega só a nova;
  dois pedidos do mesmo cliente no mesmo negócio pedem os dois códigos.

### Etapa 11 — Indicadores no detalhe do Local de Estoque
- Detalhe `/estoque-local/:codestoquelocal` (etapa 2) ganha: filtro de período; resumo do local como
  requisitado (itens requisitados, atendidos 100%, %, contagem por motivo e substituídos — cada número filtra
  a lista); lista dos itens divergentes (data, requisição, produto, pedido × atendido, motivo, substituído
  por, observação, quem justificou) com link para o produto. Conta por item; só colunas gravadas, índice
  por loja requisitada e data. Ranking, evolução e gráfico só a pedido.
- **Confere:** depois de requisições finalizadas com divergência, o detalhe do Centro mostra o resumo e a
  lista batendo com as requisições.

## Backlog — primeiro passo da execução

- TASK-55: título → "Loja vende sem estoque e o cliente retira em outra loja com vale de papel; entregas
  e transferências sem controle"; descrição com contexto + decisões; prioridade **High**; um AC por etapa
  (0-Pedido, 2, 3, 4, 5, 6, 7, 9, 10, 11); status In Progress.
- Task nova (autorizada): "Impressora nova só se cadastra editando arquivo no servidor", type feature,
  label `api`, prioridade **High**; ACs: etapa 0 (impressoras do usuário, chave do Ably fora da resposta),
  etapa 1, etapa 8, README do MGprint.

## Verificação

Cada etapa termina na tela indicada em **Confere**; o Fábio valida antes da próxima e antes de qualquer
commit (um commit por task quando autorizado, `[ADD]/[FIX] TASK-xx`). SQL de estrutura roda no dev
(`docker exec mgdb-mgdb-1 psql -U mgsis -d mgsis -f …`); SQL de dados e DROP ficam para o go-live do
Fábio. Lint/prettier nos arquivos tocados; testes/headless só se pedidos.
