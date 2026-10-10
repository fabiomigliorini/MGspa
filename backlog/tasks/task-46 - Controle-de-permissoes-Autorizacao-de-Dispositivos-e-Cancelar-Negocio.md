---
id: TASK-46
title: 'Controle de permissoes: Autorizacao de Dispositivos'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 18:53'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 112000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao SEGURANCA. No arquivo original constava "(Allan)".
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A configuração do PDV (local de estoque, natureza, impressora, maquineta, PIX) fica na tabela e volta na sincronização
- [x] #2 A 1ª sincronização migra o que estava no navegador
- [x] #3 Lista de Dispositivos com filtro na drawer (Admin todos, Gerente a filial)
- [x] #4 Página de cada dispositivo com os dados e os últimos negócios, pagamentos e ocorrências
- [x] #5 O PDV configura a si mesmo; nos outros só Admin/Gerente da filial; o cadastro é só Admin/Gerente
- [x] #6 Autorizar e inativar só Admin, também na tela
- [x] #7 Sincronizar no PDV sem janela dupla: sem login o botão fica desabilitado, sessão expirada só avisa, e o dispositivo novo entra pelo mesmo fluxo da página do dispositivo
- [x] #8 Ao abrir o negocios (PDV e quiosque), dispositivo sem cadastro ou sem autorização vai direto para o Meu Dispositivo, que tem o Cadastrar
- [x] #9 Um formulário só para editar o dispositivo, agrupado por contexto; o que o usuário não pode alterar fica desabilitado (e o servidor recusa)
- [x] #10 Autorizar e reativar recusam sem Apelido, Filial, Local de Estoque, Setor e Natureza de Operação
- [x] #11 A página do dispositivo mostra o histórico de IP e localização (uma linha por período no mesmo lugar, com quantas sincronizações) e a data da última sincronização completa
- [x] #12 Sincronizar aparece em todas as telas do negócios
- [x] #13 Ícone de sincronização não fica vermelho logo depois de sincronizar à tarde
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementação (10/10/2026): config do PDV em tblpdv (api/database/pdv_configuracao.sql, com índice idx_tblpagamento_codpdv para os últimos pagamentos). PdvAutorizador: Admin todos, Gerente a filial, o próprio PDV só a configuração. Cadastrar = POST pdv/dispositivo; sincronizar = PUT, recusa 404 sem cadastro e manda a config do navegador uma vez (legado). Página /dispositivo/:codpdv serve a lista e o Meu Dispositivo (/dispositivo/meu); o próprio se vê sem login. Guard no router redireciona sem cadastro/autorização. Sai o 'Confirme seus dados' do BtnSincronizacao.

Formulário (10/10): a filial saiu da tela e passa a ser a do local de estoque (PdvService::update e na migração do legado); o próprio PDV só escolhe local da mesma filial. PIX padrão virou select (MgSelectPortador pix, com ícone do banco) e o MgSelectMaquineta mostra o logo (Stone/SafraPay/parceiro) em todos os apps.

Autorizado = ativo (Fábio, 10/10): tblpdv.autorizado saiu. O dispositivo nasce inativo; ativar (DELETE dispositivo/{id}/inativo) é o que autoriza, só Admin e com os 5 campos. PdvService::podeAcessar olha inativo nulo (com uuid repetido, vale o ativo). Os pendentes viram inativos no pdv_configuracao.sql; o DROP da coluna está em pdv_autorizado_drop.sql, para depois do deploy. Nos critérios, 'autorizar' = ativar.

Lentidão dos últimos registros (3,6 s no PDV 254): com só o índice em codpdv, 'order by cod desc limit' descia a PK inteira filtrando o PDV. Índices (codpdv, cod) em tblnegocio, tblpagamento e tblocorrencia no pdv_configuracao.sql (CONCURRENTLY); < 1 ms. Página mostra os 10 últimos.

Revisão (Fábio): os últimos registros saem por data (lancamento/transacao/criacao desc, limit 20) e os índices compostos passaram a ser (codpdv, data): idx_tblnegocio_codpdv_lancamento, idx_tblpagamento_codpdv_transacao, idx_tblocorrencia_codpdv_criacao. Sem eles, PDV parado desde 2024 levava 2 s.

Histórico de IP e localização (Fábio, 10/10): tblpdvlocalizacao, uma linha por período no mesmo lugar (PdvService::registrarLocalizacao, na mesma transação): com o mesmo IP, latitude e longitude da última linha só soma em sincronizacoes e a alteracao vira a da última; mudou, linha nova. O atual é a última linha (Pdv::UltimaLocalizacao); o PdvResource manda o ip dela, para a lista e o SelectPdv. Filtro IP da lista procura no histórico (quem já usou o IP). Página do dispositivo: card Localizações (20 últimas, link do mapa, precisão) no lugar dos campos IP/Localização. Saiu o IP do PDV do detalhe do negócio (era o de hoje, não o da venda). DDL: tabela + carga inicial no pdv_configuracao.sql; ip, latitude, longitude e precisao saem do tblpdv no pdv_autorizado_drop.sql, depois do deploy. Desktop sem GPS vem pelo IP público (cidade, 1–4 km); precisão de 1 m é localização forçada no navegador. Sincronização completa: tblpdv.sincronizacaocompleta, a mesma data que o botão Sincronizar mostra (a mais antiga entre os cadastros baixados); o PDV manda no fim de cada sincronização (PUT pdv/dispositivo/sincronizacao-completa, sem login, pelo uuid). Página e lista de dispositivos mostram.

Sincronizar em todas as telas e hora em 24h (10/10, commits f796b2a16 e 9b5c52bbf): o BtnSincronizacao saiu do OfflineLayout e foi para o MainLayout, ao lado do usuário (o quiosque segue com o dele). O ícone ficava vermelho à tarde porque o carimbo sincronizado dos endpoints v1/pdv/* saía em 12 horas (date 'Y-m-d h:i:s' no PdvService e no PdvPranchetaService): às 13:47 gravava 01:47 e passava do limite de 4 h. Virou 'H'. O mesmo carimbo decide o que a base offline apaga (below sincronizado), então o apagado no servidor também ficava no PDV até o dia seguinte. Teste: sincronizar depois das 12h e o ícone fica na cor normal; Caixa, Pagamentos, Listagem, Vales, Comandas, Confissão, Configuração, Dispositivos e Woo têm o botão, e o PDV só um.

Testes (10/10, depois do c909caa71): pela API com tokens de Admin, Gerente da 103 e usuário comum (lista por papel, cadastrar/sincronizar/quiosque, ver sem login, editar por papel, ativar só Admin e com os 5 campos) e de tela no Chrome headless (navegador limpo vai para o Meu Dispositivo; Cadastrar sem login só avisa; cadastrar, editar e ativar; comum só com Negócios e Pagamento habilitados; filtros da lista). Corrigido: recusa das rotas do PDV diz 'Dispositivo não cadastrado ou inativo'; Gerente ao ativar recebe 'Só Administrador ativa ou inativa'; validação em português com nomes legíveis; salvar o próprio dispositivo pela tela encerra a migração do legado (a configuração vale na hora e a 1ª sincronização não preenche o que ficou vazio de propósito).

Cadastrar sem login (Fábio, 10/10): além do aviso, abre o dialog de login (useAuth().login); depois de entrar, o Cadastrar é outro clique. O aviso deixou de ser fixo (timeout 0), para não ficar na tela depois do login.

Testes do histórico (10/10, depois do 7628c55a9): API com token de Admin e sem login (cadastrar grava 1 linha e o clique duplo não duplica; mesmo lugar soma, IP ou posição nova abre linha, sem posição também; sincronizacao-completa 200/422/403 e recusa dispositivo inativo; lista com ip e filtro no histórico; página e registros do próprio sem login) e tela no Chrome headless (navegador limpo cadastra, sincroniza e manda a completa; mudar de lugar abre linha nova; cards dois por linha e um embaixo do outro no celular; lista mostra 'sincronizado há'). Corrigido: período no mesmo minuto mostrava '14:46 → 14:46'. Observação: o coletarDispositivo aceita posição de até 10 min (maximumAge), então sincronizar logo depois de mudar de lugar ainda grava a posição anterior.

Cancelar Negócio (Fábio, 10/10): saiu do título. Fica coberto pela TASK-205 — o cancelamento vira ocorrência para o gerente (quem, quando, justificativa e pagamentos) em vez de bloquear o caixa. Não há restrição por papel no cancelamento (PdvController::deleteNegocio só exige justificativa de 15+ caracteres).
<!-- SECTION:NOTES:END -->
