---
id: TASK-197
title: Sincronização do PDV é manual e baixa a base inteira toda vez
status: In Progress
assignee:
  - '@fabio'
created_date: '2026-10-05 15:40'
updated_date: '2026-10-19 02:29'
labels:
  - negocios
dependencies: []
priority: medium
type: feature
ordinal: 210000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Hoje o PDV só sincroniza quando alguém clica no botão, e cada sincronização baixa de novo todos os produtos, pessoas e configurações. Cadastro recém-feito (cliente, produto, preço) não chega ao PDV até a próxima sincronização manual.

Origem: hotfix do quiosque (TASK-154, 05/10/2026). Na migração para a api/, o grupo v1/pdv/* ficou dentro do auth:api e o quiosque sem usuário logado parou de consultar preço. O fix simples tirou as rotas de leitura do auth:api; o controller autoriza só pelo uuid que vem na query string (PdvService::autoriza). Funciona, mas o uuid não é credencial: qualquer um que conheça o uuid de um PDV autorizado lê o catálogo e o cadastro de clientes.

Ideia do Fábio para a sincronização:
- Autenticação própria do PDV, para os casos sem usuário logado (quiosque, sincronização): o dispositivo autorizado recebe um token/credencial em vez de mandar só o uuid. Consolidar as rotas do PDV que hoje usam withoutMiddleware('auth:api').
- Sincronizar sozinho ao iniciar o sistema, sem depender do botão.
- Incremental: trazer só o que mudou desde a última sincronização. Pensado: arquivo JSON cacheado no backend e só o diff de cada alteração para o front.
- Full semanal como rede de segurança (pega exclusões e qualquer divergência do incremental).

Consolida TASK-36 (sincronizar pessoas novas) e TASK-54 (forçar sincronização ao entrar se a última for antiga).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Quem só conhece o uuid de um PDV não se passa por ele: o PDV manda o uuid escondido no cabeçalho de cada chamada (nunca no endereço, que fica no log) e o uuid não aparece em tela, na lista de Dispositivos nem na cobrança PIX; quiosque e sincronização seguem sem usuário logado
- [x] #2 Sincronização roda sozinha ao iniciar o sistema, sem clicar no botão (ex-TASK-54)
- [x] #3 Sincronização incremental traz só o que mudou desde a última, inclusive cadastro recém-feito de pessoa (ex-TASK-36), produto e preço
- [x] #4 Sincronização incremental só fica disponível quando compensa: o servidor ainda tem a versão do PDV, os diffs somados são menores que a completa e a última completa tem até 7 dias; senão só a Sincronização completa fica disponível, já com o foco (a que abre sozinha ao iniciar segue a mesma regra)
- [x] #5 Sincronização não fica esperando a localização: hoje o Chrome no Linux às vezes não devolve a posição (mesmo com permissão) e a sincronização recusa depois de 10 s; localização continua obrigatória
- [ ] #6 Quiosque sem resposta do backend (offline, rede caída, PDV não autorizado) não mostra o saldo do cache como estoque atual: avisa que é estoque não confirmado e de quando é; sem cache, mostra estoque indisponível (não zero) (ex-TASK-154)
- [x] #7 Sincronizar funciona sem usuário logado: o botão não fica desabilitado nem avisa de login ou de sessão expirada
- [x] #8 Ao abrir o sistema sem ter sincronizado hoje (última sincronização de outro dia, ou nunca feita), a janela Sincronizar abre e já sincroniza sozinha
- [x] #9 Duas janelas do mesmo navegador nunca sincronizam ao mesmo tempo: a segunda recusa avisando que outra já está sincronizando
- [x] #10 Ao abrir sem usuário logado (ou com a sessão vencida), o PDV não abre negócio sozinho e o login não aparece; F2/Novo continuam abrindo, inclusive sem login e sem rede
- [x] #11 Fechar a janela Sincronizar com ESC ou clicando fora para a sincronização, igual ao Cancelar: ela não continua rodando escondida e travando o PDV
- [x] #12 A janela Sincronizar mostra a data e a hora (com segundos) da última sincronização
- [x] #13 Botões Completa e Incremental na janela Sincronizar; com a incremental disponível o foco vai nela, e a completa baixa a base inteira de pessoas e produtos mesmo assim
- [x] #14 Durante a sincronização cada linha mostra só a contagem (ex.: 28.195 / 28.195) numa linha e a sua barra de progresso, sempre visível; a completa avança de 2 em 2 mil e a janela não muda de tamanho enquanto roda
- [x] #15 Ao terminar sem erro a janela fecha e avisa 'Sincronização finalizada'; cancelada não avisa
- [x] #16 Com outra janela do navegador sincronizando, a janela Sincronizar nem abre (botão do PDV, menu do quiosque e a sincronização ao abrir o sistema): recusa com o aviso de que já existe uma sincronização em andamento
- [x] #17 Sincronização completa interrompida (Cancelar, F5, computador travado) deixa o PDV como nunca sincronizado: a data da última sincronização some já no começo da completa e a próxima só pode ser completa
- [x] #18 Janela Sincronizar cabe no celular: sem o círculo grande, uma barra de progresso por linha e botões Cancelar, Completa e Incremental
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Pendência do hotfix da TASK-154 (05/10/2026): dispositivo() pede navigator.geolocation com timeout 10 s e maximumAge 10 min; sem posição, recusa a sincronização. No Chrome de dev no Linux a posição às vezes não vem e a sincronização recusa. Avaliar maximumAge Infinity (última posição conhecida) ou registrar a localização separado da sincronização.

Revisão do backlog com o Fábio (10/10/2026, 2ª varredura): convivência com a TASK-46 #7 (sem login o botão Sincronizar fica desabilitado; sessão expirada só avisa): o botão manual continua pedindo usuário logado; a sincronização automática (#1/#2) usa a credencial própria do PDV, sem usuário.

Revisão com o Fábio (14/10/2026): a decisão de 10/10 (o botão manual continua pedindo usuário logado; TASK-46 #7) deixou de valer — sincronizar não pede login. A janela que abre ao iniciar já sincroniza (não espera o clique). Trava entre janelas pelo Web Locks do navegador (navigator.locks), que solta sozinha se a janela fechar. Sem negócio aberto, a tela do PDV mostra 'Aperte F2 ou clique em Novo para começar uma venda'.

Fábio (16/10/2026): o Cancelar abortava (abortarSincronizacao zera importacao.rodando, que os laços checam a cada página), mas ESC e clique fora só punham importacao.dialog = false e a sincronização seguia em segundo plano; o usuário achava que tinha parado, dava F5 e começava de novo. Fix: @hide da q-dialog no DialogSincronizacao chama abortarSincronizacao (vale para os três caminhos). O aborto para na próxima checagem, depois da requisição em andamento responder.

Fábio (16/10/2026): a janela de 2 horas era pequena; a sincronização ao abrir passa a valer se a última não foi hoje (critério 8 reescrito).

Fábio (17/10/2026): o incremental vai por JSON cacheado no backend com diff por versão. Incremental por alteracao foi descartado: não pega exclusão física; lista de chaves e tombstone com trigger foram descartados por abrirem brecha (tabela auxiliar, trigger). LGPD (cifrar o IndexedDB) saiu para task própria, prioridade low.

Implementação (17/10/2026), core da sincronização incremental:
- Backend: job pdv:catalogo (api/app/Console/Commands/PdvCatalogoCommand.php, agendado a cada 15 min em routes/console.php, withoutOverlapping) gera em storage/app/pdv-catalogo os arquivos produtos-AAAAMMDD-HHMMSS.json.gz e .diff.gz (idem pessoas-), um registro por linha em ordem de chave, sempre, mesmo sem mudança. O diff é um merge em PHP do json novo contra o anterior ('-' saiu, '+' entrou; alterado vem nas duas), numa transação REPEATABLE READ; manifesto catalogo.json gravado por último, renames atômicos. Retenção: diffs 30 dias, jsons só os 2 últimos. Mede 7 s na primeira rodada e 3,5 s nas seguintes no dev (191 mil barras + 27,6 mil pessoas). Consultas de produto/pessoa e as de bipe/CNPJ foram para PdvCatalogoService/PdvCatalogoController; rotas paginadas produto, produto-count, pessoa, pessoa-count removidas. Registro de produto ganhou 'nome' (base, sem variação/embalagem) para a TASK-173.
- Rotas (sem login, uuid do dispositivo como hoje): GET v1/pdv/catalogo/{tabela}/versoes?desde=V (atual com tamanho e registros, diffs com tamanho, desde.conhecida) e GET v1/pdv/catalogo/{tabela}/{versao}.json|.diff (serve o .gz com Content-Encoding gzip).
- Front: Dexie v9 apaga produto/pessoa e v10 recria só com os índices consultados (codproduto, barras, versao, buscaArr); store sincronizacao com versao.produto/pessoa persistida, sincronizarCatalogo (full em fluxo com fetch/ReadableStream ou diffs em paralelo de 5 aplicados em ordem; full só se a versão local não é conhecida pelo servidor ou se os diffs somados passam do tamanho da full), etapas em paralelo (Promise.allSettled) com uma linha por etapa no dialog e AbortController no Cancelar/ESC; bipe, CNPJ e cadastro de pessoa carimbam a versão local no registro.
- Teste de ponta a ponta em Chrome headless contra o quasar dev e a api-dev (17/10): full num perfil limpo em 10 s (produtos) com pessoas em paralelo, total 14 s; alteração de preço + barra nova -> diff aplicado em 1,2 s ('5 alterados, 0 excluídos'); exclusão da barra -> '4 alterados, 1 excluídos' e a barra some do IndexedDB; rodada sem mudança -> 'Nada mudou desde a última'. Sem erro de console. Critérios #3 e #4 ficam para o Fábio validar e marcar.

Fábio (17/10/2026, primeira sincronização em produção): a full parecia travada (progresso de 20 em 20 mil, sem dizer o que fazia) e não havia como forçar a completa. Mudou:
- Botões Sincronização completa e Sincronização incremental. A janela, ao abrir, verifica a incremental (verificarIncremental): disponível só se, em pessoas e produtos, o servidor conhece a versão local, os diffs somados são menores que a completa e a última completa tem até 7 dias. A data da última completa vem da menor versao da tabela no IndexedDB (a completa carimba todos os registros, o diff só os que mudaram). Foco na incremental quando disponível; senão só a completa habilitada, com o foco. A sincronização ao abrir segue a mesma regra.
- A incremental não cai mais sozinha na completa: se a versão local saiu do servidor depois da verificação, a etapa recusa pedindo a Sincronização completa.
- Full grava e mostra o progresso a cada 2 mil; as linhas dizem 'Consultando o servidor...', 'Baixando tudo: x / y registros', 'Baixando alterações: ...'.
- Fim sem erro: Notify 'Sincronização finalizada'; cancelada não avisa.
- Teste em Chrome headless contra o quasar dev (17/10): completa ao abrir num perfil limpo em 23 s com o aviso no fim; reabrir -> incremental com foco, 'Nada mudou' em 2 s; registro com versão de 01/10 no IndexedDB -> só a completa, com foco, e depois dela a incremental volta; versão de pessoas desconhecida -> só a completa; ESC no meio da completa -> sem aviso.

Fábio (17/10/2026): a trava entre janelas só valia no executar; a janela Sincronizar abria na segunda aba, verificava a incremental e só recusava no clique. Agora abrirJanela() consulta a mesma trava do Web Locks (navigator.locks.query) antes de abrir, e é o único caminho de abertura: botão do cabeçalho (BtnSincronizacao), menu do quiosque e sincronizarAoAbrir. Na corrida entre a consulta e o clique, o sincronizar() continua recusando. Teste com duas abas no Chrome headless: com A fazendo a completa, B ao abrir o sistema e B no botão do cabeçalho recusam com o aviso, sem abrir a janela; A terminada, B abre normalmente.

Fábio (18/10/2026), critério #5: localização continua obrigatória ('não quero gente sincronizando sem eu saber onde está'); baixar em paralelo à posição foi descartado. A posição vale até 1 min (era 10 min de maximumAge). No sincronizacao.js, localizar() pede a posição sem timeout ao navegador e desiste em 10 s com a mesma recusa de hoje; a resposta que chega depois fica só em memória (some no F5) e serve à próxima tentativa enquanto tiver até 1 min (pelo timestamp da própria posição). Abrir a janela Sincronizar (abrirJanela) já faz o pedido, para a posição chegar antes do clique. Cadastrar do Meu Dispositivo usa o mesmo caminho.

Fábio (18/10/2026): a janela Sincronizar mostra a busca da localização numa linha própria, acima das etapas e no mesmo formato: girando com 'Buscando a localização do dispositivo...', check com a precisão (± m) ou erro 'Sem localização'; toggle sempre ligado e desabilitado (obrigatória).

Teste em Chrome headless contra o quasar dev e a api-dev (18/10/2026, dispositivo 511 Teste; Chrome com --disable-web-security porque a api-dev só libera CORS para http://localhost e o dev roda em https://localhost:9900). Geolocation real do Chrome com posição emulada e geolocation falsa para controlar atraso e erro, 34/34 ok, sem erro de console:
- posição emulada: linha Localização é a primeira, toggle ligado e travado, '± 25 m', PUT com a posição e 'Sincronização finalizada';
- navegador demora 3 s: aos 1 s a linha gira com 'Buscando a localização do dispositivo...' e nenhuma etapa começou; getCurrentPosition com maximumAge 60000 e sem timeout;
- navegador nunca responde: recusa aos 10,0 s com 'Sem a localização do dispositivo não é possível sincronizar: o navegador não respondeu em 10 segundos', linha vermelha 'Sem localização', sem PUT, janela aberta;
- posição indisponível: recusa na hora com o erro do navegador;
- resposta chega aos 12 s: a 1ª tentativa recusa aos 10 s; a 2ª, logo depois, usa a resposta atrasada sem pedir de novo e sincroniza; dentro de 1 min a posição é reaproveitada, depois de 1 min pede outra (PUT com a cidade nova);
- abrir a janela já pede a posição: com ela chegada, o clique não gira e há um pedido só;
- Cadastrar: coleta com posição e recusa com 'não é possível cadastrar' sem ela.
O banco registrou no histórico do 511 cada posição enviada.

Bug achado no teste e corrigido: Cancelar (ou ESC, clique fora) durante a busca da localização fechava a janela, mas a sincronização seguia esperando escondida, mandava o PUT do dispositivo quando a posição chegava e segurava a trava; reabrir a janela recusava com 'Já existe uma sincronização em andamento'. Agora o AbortController nasce antes do dispositivo(): o Cancelar interrompe a espera da localização sem aviso, nada é enviado e a janela reabre na hora. Se cancelar enquanto o PUT do dispositivo responde, as etapas não começam.

Fábio (18/10/2026), critério #1: a credencial própria (segredo gerado pelo servidor) foi descartada. O uuid já é aleatório; o furo era ele aparecer (Meu Dispositivo, Quiosque inativo, lista de Dispositivos, mensagem da cobrança PIX no app do banco do cliente) e ir na query string, que fica no log do servidor. Esconder o uuid faz dele a senha do dispositivo, sem mexer no cadastro nem na ativação. Base64 ou criptografia no front foram descartados: não escondem nada.
- Backend: middleware `pdv` (App\Http\Middleware\AutorizaPdv, alias no bootstrap/app.php) lê o cabeçalho X-Pdv (PdvService::uuid), exige o dispositivo ativo e entrega o Pdv em $request->attributes->get('pdv'). Vale só nas rotas que chamavam PdvService::autoriza (45 métodos, conferidos no route:list); as rotas sem login foram para um bloco só no routes/api.php. Fora do middleware, pela mesma função: anexos do negócio (PDV pelo cabeçalho; sem ele, usuário Financeiro/Gerente no contas), página e registros do dispositivo (o próprio ou Administrador/Gerente) e o PUT dispositivo, que aceita inativo. No cadastro e na sincronização o uuid continua no corpo. PdvRequest removido; a regra `pdv` saiu do PdvUpdateRequest e do TituloAdiantamentoStoreRequest. O podeAcessar recusa valor que não é uuid (antes ia ao Postgres).
- uuid escondido: o PdvResource não devolve o uuid; o filtro UUID da lista de Dispositivos virou Código; a página do dispositivo e o Quiosque inativo pedem a ativação pelo código; a cobrança PIX mostra PDV #código.
- Front: o boot/axios.js põe o X-Pdv em toda chamada e o fetch do catálogo põe à mão; o `pdv` saiu dos parâmetros e corpos (negocios e @components: cobrança, estorno, listagem de pagamentos, confissão). Listagem e Woo filtram pelo codpdv da sincronização, em vez de baixar a lista de Dispositivos (só de Administrador/Gerente) para achar o uuid: para o usuário comum a listagem passa a vir filtrada no PDV dele, como já vinha para o gerente. stores/pdv.js removido.
- Fica: os uuids já vistos (lista, logs antigos, cobranças PIX antigas) continuam valendo. Se algum preocupar, um cadastro novo gera outro uuid.
- Teste (18/10): curl na api-dev (sem cabeçalho, com o ?pdv= antigo, com dispositivo inativo e com valor inválido: 403; com o cabeçalho: 200; o preflight de CORS aceita x-pdv) e Chrome headless contra o quasar dev (negocios-dev:9900): completa ao abrir sem login, com 24 pedidos, todos com X-Pdv e nenhum com o uuid no endereço ou no corpo; quiosque consulta o produto; Meu Dispositivo sem o UUID e com o código; dispositivo inativo pede a ativação pelo código. Não testado: os fluxos com login (venda, pagamento, anexos, lista de Dispositivos) e a mensagem do PIX.

Fábio (18/10/2026): finalizar a task. #1, #14, #17 e #18 conferidos e marcados (#14: contagem x / y e barra por linha, completa de 2 em 2 mil; #17: aplicarFull apaga versão e data da completa antes de baixar; #18: sem o círculo, botões Cancelar, Completa e Incremental). #6 não foi feito: sem resposta do backend o Quiosque ainda mostra o saldo do cache (ou zero sem cache); a task fica em andamento até a decisão sobre ele.
<!-- SECTION:NOTES:END -->
