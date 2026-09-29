---
id: TASK-171
title: 'Agro/Patio: da pra alterar sem querer um campo ja lancado da carga'
status: In Progress
assignee:
  - '@eduardo'
created_date: '2026-09-23 19:56'
updated_date: '2026-09-29 17:54'
labels:
  - agro
dependencies: []
priority: medium
type: feature
ordinal: 155000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Tela de carga do Patio (agro/src/components/carga/CargaForm.vue) hoje e um unico formulario sempre editavel, sem confirmacao — edicao acidental de campo ja lancado passa despercebida. Dividir em 4 blocos (Caminhao, Origem/Destino do grao, Pesagem, Classificacao), cada um com botao lapis que abre um q-dialog Cancelar/Salvar. Decisoes de arquitetura ja validadas: (1) replicar o padrao local ja maduro no repo (familia Card*.vue em pessoas/src/components/pessoa/, ex. CardEndereco.vue), sem criar componente compartilhado novo tipo MgEditableCard; (2) cada dialog de bloco salva IMEDIATAMENTE ao clicar Salvar, reaproveitando o fluxo ja existente CargaPage.vue::persistir() (store.salvar + troca de safra + tratamento de erro) via uma funcao persistirBloco() provida pelo CargaForm.vue — nenhum bloco importa a store diretamente nem duplica esse tratamento; (3) enquanto a carga e nova (novo===true) nenhum bloco persiste, so edita estado local — nada e criado no Dexie/servidor antes do clique em Registrar. Plano completo em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md. Ordem de execucao: Caminhao, Pesagem, Classificacao, Origem/Destino (mais complexo, por ultimo), depois limpeza do FAB 'salvar sem avancar'.

Os 4 blocos (Caminhao, Pesagem, Classificacao, Origem/Destino) estao implementados; falta so a limpeza do FAB cinza 'salvar sem avancar' e decidir onde fica a Observacao. Consolidada em 2026-09-23: era esta task mais 5 subtasks, que agora sao os criterios de aceite daqui. O texto integral delas esta nas notas; os arquivos originais (task-146.1 a 146.5) seguem recuperaveis em `git show c6ef66e97`.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Bloco Caminhao (placa, carreta, motorista, chegada) so edita por dialog com Cancelar/Salvar
- [x] #2 Bloco Pesagem (PBT/Tara com preview ao vivo) so edita por dialog
- [x] #3 Bloco Classificacao (leituras dos parametros da cultura) so edita por dialog
- [x] #4 Bloco Origem/Destino do grao: um dialog so, duas colunas lado a lado
- [ ] #5 FAB cinza 'salvar sem avancar' removido e a Observacao com lugar pra ser salva
- [ ] #6 Trocar o tipo de romaneio com peso ja lancado pede confirmacao nomeando a consequencia; sem peso troca direto; finalizada segue bloqueada
- [ ] #7 Blocos em leitura com icone e hierarquia: rotulo em legenda, valor em destaque, icone por tipo de ponto (talhao/armazem/contrato)
- [ ] #8 Chip colorido de etapa sai da area das abas; a etapa segue visivel na barra de progresso do drawer direito
- [ ] #9 Botão da etapa (Pesar bruto, Classificar, Pesar tara, Notas fiscais) abre o dialog da etapa pedindo o valor; sem valor não avança
- [ ] #10 Bloco sem nada informado mostra um + azul no lugar do lapis; com dado informado, segue o lapis de editar
- [ ] #11 Safra e tipo de operacao saem do drawer e do card e viram selects no modal de Operacao (6 campos em 2 linhas de 3); o card so exibe
- [ ] #12 Placa e carreta aceitam so formato brasileiro (ABC1234 ou ABC1D23); placa sem cadastro pode ser usada assim mesmo ou cadastrada
- [ ] #13 Motorista pode ser cadastrado de dentro do modal com CPF, nome, telefone e endereco
- [ ] #14 Motorista sem cadastro exige CPF, nome completo, telefone e endereco, gravados so na carga; CPF ja cadastrado seleciona a pessoa existente
- [ ] #15 Carga nova nasce com Recebimento e a chegada preenchidos, mas sem safra; registrar sem safra nao deixa
- [ ] #16 No modal de Operacao as setas percorrem as opcoes de placa e motorista (inclusive usar sem cadastro e cadastrar), e escolher a placa leva o foco pra carreta
- [ ] #17 Origem/Destino: a unica linha de origem ou destino nao tem X; a soma so aparece com mais de uma linha; o + fica na linha do titulo com tooltip
- [ ] #18 Os 4 modais da carga com 60% da altura da tela (tela cheia no celular); o modal de Operação cabe sem rolar, com o 'cadastrar no sistema' no rodapé à esquerda
- [ ] #19 Pesos (PBT/Tara) e leituras da classificacao em campos numericos grandes; na pesagem o Liquido em destaque
- [ ] #20 Carga nova abre direto no modal de Operacao com o cursor na Safra; setas para cima/baixo percorrem as safras
- [ ] #21 F3 e Enter confirmam o modal aberto e ja abrem o proximo que falta (Operacao > Origem/Destino > etapa da balanca); Esc interrompe
- [ ] #22 Registrar com o peso ja digitado nao pede o peso de novo: a carga ja nasce na etapa seguinte
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Detalhe tecnico das subtasks consolidadas (2026-09-23)

Eram os arquivos task-146.1 a 146.5, criados em outra maquina, renumerados para 171.x na correcao da colisao de ids e removidos na consolidacao.
Viraram criterios de aceite desta task; o texto original esta preservado aqui.

### Agro/Patio: bloco Caminhao em dialog editavel

Extrair Placa/Carreta/Motorista/Chegada de CargaForm.vue para novo agro/src/components/carga/CargaBlocoCaminhao.vue: exibicao read-only + botao lapis que abre q-dialog (Cancelar/Salvar), seguindo o padrao de pessoas/src/components/pessoa/CardEndereco.vue. Recebe a carga inteira por referencia (mesma instancia 'local' que CargaForm.vue ja mantem), le/escreve nela diretamente. Move tambem o autocomplete de placa e o CaminhaoDialog.vue (cadastro rapido de veiculo, ja existente, so muda de 'morada'). Salvar do dialog: aplica o patch e, quando !novo, chama a funcao persistirBloco() (provide/inject vindo de CargaForm.vue) — nao importa a store diretamente, nao duplica tratamento de erro/troca de safra (isso ja existe em CargaPage.vue::persistir). Primeiro bloco a ser feito — estabelece o gabarito (estrutura do dialog + uso de persistirBloco) para os demais. Ver TASK-171 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.

### Agro/Patio: bloco Pesagem em dialog editavel

Extrair PBT/Tara + preview ao vivo (bruto/desconto/liquido/sacas via calcularCarga, de agro/src/utils/desconto.js) de CargaForm.vue para novo agro/src/components/carga/CargaBlocoPesagem.vue: exibicao read-only + lapis + q-dialog Cancelar/Salvar, mesmo padrao do bloco Caminhao (criterio #1). Preview dentro do dialog usa os valores em edicao (pbt/tara) + a classificacao ja salva em props.carga. Salvar aplica o patch e chama persistirBloco() (provide/inject de CargaForm.vue) quando !novo. Segundo bloco a ser feito, apos Caminhao. Ver TASK-171 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.

### Agro/Patio: bloco Classificacao em dialog editavel

Extrair leituras de classificacao (itensCarga/parametrosDaCarga, hints de tolerancia/desconto) de CargaForm.vue para novo agro/src/components/carga/CargaBlocoClassificacao.vue: exibicao read-only + lapis + q-dialog Cancelar/Salvar, mesmo padrao do bloco Caminhao (criterio #1). Lista de itens vem dos parametros da cultura (nao e editavel em quantidade de linhas pelo usuario, so as leituras). Preview de desconto por parametro reaproveita props.carga.pbt/tara ja salvos. Salvar aplica o patch e chama persistirBloco() quando !novo. Terceiro bloco a ser feito, apos Pesagem (reaproveita o preview validado la). Ver TASK-171 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.

### Agro/Patio: bloco Origem/Destino do grao em dialog editavel

Extrair a lista dinamica de pontos (origem/destino, add/remove linha, rateio de %, troca de safra pelo talhao escolhido, campos condicionais de NF na saida) de CargaForm.vue para novo agro/src/components/carga/CargaBlocoPontos.vue. UM UNICO bloco/dialog com as duas colunas (Origem | Destino) lado a lado, como a tela atual — nao separar em dois blocos, ja que a % de cada lado e editada em conjunto. Dialog mais largo (2 colunas). Copia editavel e um clone do array 'pontos' (nao da carga inteira); Salvar substitui props.carga.pontos pelo array editado e chama persistirBloco() quando !novo. Validacao de soma=100% continua condicionada a 'finalizando' (so obrigatoria perto de FINALIZAR) e permanece no FAB principal de CargaForm.vue (validarFinalizacao) — o dialog do bloco so mostra a dica visual, nao bloqueia o Salvar fora dessa etapa. Peças puras compartilhadas com CargaForm.vue (soma de %, filtro por papel) migram para agro/src/utils/carga.js. Ultimo bloco a ser feito, de proposito — e o mais complexo (unica lista editavel pelo usuario) e o mais usado no patio real; fazer por ultimo minimiza o tempo de instabilidade na etapa mais critica. Ver TASK-171 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.

### Agro/Patio: remover FAB ''salvar sem avancar'' e decidir destino da Observacao

Com os 4 blocos (Caminhao, Pesagem, Classificacao, Origem/Destino) salvando individualmente via persistirBloco(), o FAB cinza 'salvar sem avancar' (CargaForm.vue, funcao salvarSemAvancar) fica redundante — exceto pelo campo Observacao (textarea livre, hoje fora de qualquer bloco, CargaForm.vue:996-1003), que perderia forma de salvar se o FAB sumir. Recomendacao do plano: mover Observacao para dentro do dialog do bloco Caminhao (campo mais 'de contexto geral' da viagem) e remover o FAB cinza do template principal (a funcao salvarSemAvancar pode virar a base de persistirBloco, entao nao desaparece do codigo, so deixa de ser exposta como FAB). Fazer so depois que os 4 blocos estiverem validados em uso real no patio — mais seguro avaliar com a tela nova rodando de verdade. Ver TASK-171 para contexto completo e o plano em /home/usuario/.claude/plans/quero-montar-um-novo-synthetic-karp.md.

## Estado da implementacao (era o Final Summary, de quando a task estava Done)

4 blocos implementados (criterios #1 a #4): CargaForm.vue reescrito para renderizar CargaBlocoCaminhao/Pontos/Pesagem/Classificacao no lugar dos campos inline, com provide('persistirBloco', ...) + provide de calc/itensCarga/sacasLiquido/avisoClassificacao/finalizando para os blocos injetarem. CargaPage.vue passa :persistir='persistir' pro CargaForm. FAB cinza 'salvar sem avancar' e observacao ainda NAO tocados (fica pro criterio #5, que so deve rodar apos uso real validado). Lint limpo em todos os arquivos tocados. Verificacao visual em navegador bloqueada nesta sessao por problema de infra nos dev servers (TASK-172, criada durante este trabalho) — trabalho pronto na arvore, sem commit, aguardando validacao do usuario na tela real.

## Modal de Operação: safra, operação, placa e motorista (28/09/2026)

**DDL obrigatório antes de publicar:** api/database/agro_carga_cpfmotorista.sql (tblcarga.cpfmotorista varchar(11)). Já aplicado no dev. Sem ele o sync da carga dá 500.

- Safra saiu do drawer (CargaLeftDrawer) e o toggle de operação saiu do card: os dois são select no modal de Operação (CargaBlocoOperacao), 6 campos em 2x3. O card só exibe.
- Pátio lista todas as safras (TASK-109): stores/carga.js carregarCargas sem filtro; codsafraAtiva virou a safra padrão da próxima carga nova (a última salva). Tela/ticket/totais leem safra/cultura/pesosaca da carga (safraDaCarga, culturaDaCarga, pesosacaDaCarga).
- trocarOperacao (CargaForm) devolve Promise<boolean> e não persiste mais; o Salvar do modal grava tudo numa vez.
- Placa/carreta: formato ABC1234 ou ABC1D23 (PLACA_RE em utils/carga.js). Placa sem cadastro: 'Usar sem cadastro' ou 'Cadastrar' (CaminhaoDialog, só online).
- Motorista: MgSelectPessoa ganhou slot no-option com { busca } (compatível com os outros apps). Sem cadastro: CPF + nome completo gravados em tblcarga (cpfmotorista/motorista). Cadastrar: passo 2 no mesmo card (CargaMotoristaCadastro), POST v1/carga/motorista -> PdvPessoaService::novaPessoa (pessoa + telefone + endereço numa transação). CPF já cadastrado seleciona a pessoa existente.
- Os 4 modais usam a classe .carga-dialog (560px x 70vh, css/app.scss) e abrem maximized no celular; Origem/Destino empilha, Classificação em 3 colunas.

## Rodada 2 do modal de Operação (28/09/2026)

**DDL renomeado:** api/database/agro_carga_motorista_sem_cadastro.sql (era agro_carga_cpfmotorista.sql, nunca commitado). Agora cria cpfmotorista, telefonemotorista, cepmotorista, enderecomotorista, numeromotorista, complementomotorista, bairromotorista, codcidademotorista. Já aplicado no dev; rodar em prod antes de publicar.

- Carga nova nasce sem safra (store.nova codsafra null); CargaForm.entradaValida barra o Registrar e abre o modal de Operação. Escolher talhão de origem também preenche a safra.
- Placa: as ações 'usar sem cadastro'/'cadastrar' viraram opções do q-select (setas + Enter chegam nelas); escolher a placa leva o foco pra Carreta (@popup-hide). Testado em Chrome headless com o QSelect real.
- MgSelectPessoa (compartilhado): prop acoesSemResultado + evento acao — as ações viram opções quando a busca não acha ninguém. O slot no-option da rodada anterior saiu.
- Motorista sem cadastro e cadastrar usam os mesmos campos (CargaMotoristaCampos.vue): CPF, nome, telefone, CEP (viacep), endereço, número, complemento, bairro, cidade + toggle 'Cadastrar no sistema'. Sem cadastro grava tudo na carga; cadastrar faz POST v1/carga/motorista antes de salvar. CargaService zera os campos sem cadastro quando há codpessoamotorista.
- Origem/Destino: cada lado abre com ao menos uma linha; X só nas linhas a mais; Soma só com mais de uma linha; + na linha do título com tooltip.
- Modais: .carga-dialog com height 50vh (largura 560px).

### Revisão independente da rodada 2 (4 revisores + verificação adversarial): 16 achados confirmados, corrigidos
- Placa: sem clearable do Quasar (X próprio fora do Tab); foco pra Carreta só se o foco ainda estiver na placa (Tab não pula campo; Enter rápido < debounce funciona); blur usa o texto do campo, não o último termo filtrado (Esc e re-escolher a mesma placa não trocam mais a placa). Placa/carreta antigas normalizadas ao abrir.
- Motorista offline: abre na busca (não obriga motorista), X de voltar também offline, nome do cadastrado aparece offline (cache semeado). Busca que falha por rede oferece 'sem cadastro' (MgSelectPessoa passa { erro }) e tira a exigência de cidade.
- CPF: Salvar espera a checagem do CPF (blur + Salvar reaproveitam a consulta); CPF de pessoa inativa avisa e segue sem cadastro (o backend recusa cadastrar CPF repetido mesmo de inativo).
- CEP: preenche só o que o viacep trouxe; sem rua, foco no Endereço.
- Home (IndexPage): 'No pátio' e 'Recebidas' filtram pela safra do select da home (a store agora carrega todas).
- F3 não chamava o onSubmit (submit() sem evento + @submit.prevent) — corrigido passando um Event; mensagem 'ou remova a linha' ajustada.
<!-- SECTION:NOTES:END -->
