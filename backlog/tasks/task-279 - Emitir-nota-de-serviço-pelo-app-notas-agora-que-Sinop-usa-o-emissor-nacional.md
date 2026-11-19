---
id: TASK-279
title: 'Emitir nota de serviço pelo app notas, agora que Sinop usa o emissor nacional'
status: In Progress
assignee: []
created_date: '2026-11-18 02:01'
updated_date: '2026-11-19 20:53'
labels:
  - notas
dependencies: []
priority: high
type: feature
ordinal: 288000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Sinop deixou o emissor municipal (Coplan): a NFS-e da FDF (filial 201, Simples Nacional, ~30 notas/mês) passa a ser pelo Sistema Nacional (nfse.gov.br). Hoje se emite à mão no site; a ideia é emitir pelo app notas.

Decisões (17/11/2026; detalhes nas notas da task):
- Estrutura PRÓPRIA: tabela de nota de serviço e telas à parte, fora da tblnotafiscal e das telas de NF-e (62 arquivos do api filtram a tblnotafiscal por modelo).
- Uma nota = um serviço: o XSD 1.01 tem um serv/cServ e um valores. Valores: serviço + desconto incondicionado (abate a base do ISS) + desconto condicionado (não abate). Sem frete/seguro/outras (não existem na NFS-e).
- Serviço escolhido na nota: código de tributação nacional e NBS das tabelas oficiais importadas (Anexo B v1.01, como a tblncm). FUTURO: o produto tipo Serviço guarda esses dados e a nota copia.
- Simples da FDF: regime 1 (federais e ISS no DAS), ME/EPP. Alíquota efetiva do ISS digitada em cada nota (muda todo mês). Total aproximado de tributos: não informar (indTotTrib=0). ISS retido: campo, padrão não retido. Sem retenção federal por ora.
- Tomador opcional (cópias/encadernação sem cliente identificado). Local da prestação selecionável, padrão a cidade da filial.
- Status com os códigos da NF-e: DIG, ERR, AUT, CAN. Sem inutilização nem denegação.
- Só digitada no notas por enquanto; gerar a partir do negócios fica para depois. Telas com a mesma usabilidade das de nota fiscal (listagem, view com os botões de ação, form criar/editar).
- tblfilial ganha emitenfse e nfseserie, editáveis no formulário da filial do pessoas. DPS série 1, a partir do 1. Ambiente = o da NF-e (no .env desde a TASK-278).
- Comunicação = serviço próprio: certificado, assinatura e XSD pelo sped-common; DANFSe pelo sped-da 1.1.7 (atualizar de 1.1.6; conferir DANFE/DANFCE). A API de DANFSe do ADN foi suspensa em 01/07/2026 (NT 008). NFePHP não tem a parte de API.
- API: Sefin (POST /nfse síncrono, GET /dps/{id}, POST /nfse/{chave}/eventos) e ADN (distribuição /contribuintes/DFe/{NSU}, eventos por chave). mTLS com o A1 da filial; DPS assinada, gzip+base64 em JSON. Consulta pela chave não mostra cancelamento.
- Não gera título a receber; fora da exportação do Domínio por enquanto.
- Distribuição do ADN em estrutura à parte (tabela e pasta próprias), não na tbldistribuicaodfe.

Prazo: emitir em 18/11/2026 (testes de manhã, deploy ao meio-dia). Mínimo para amanhã = critérios 1 a 4, 7 e 8. Substituição e caixa do ADN depois.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Falar com o Sistema Nacional: certificado da filial, DPS assinada e validada no XSD, envio e consulta da DPS, testado na produção restrita
- [x] #2 Tabela da nota de serviço e emitenfse/nfseserie na tblfilial, com o regime do Simples Nacional da FDF
- [x] #3 Tela no notas: listar, criar/editar, enviar e baixar o DANFSe (sped-da 1.1.7), com a usabilidade das telas de nota fiscal
- [x] #4 Cancelar a NFS-e (evento) pela tela
- [ ] #5 Substituir a NFS-e (DPS nova apontando a anterior)
- [x] #6 Ler a caixa do ADN em estrutura própria: nossas notas e eventos (cancelamento de fora leva a CAN) e as notas emitidas contra nós
- [ ] #7 Enviar o DANFSe e o XML por e-mail ao tomador, por botão no detalhe da nota
- [x] #8 Tabelas oficiais de código de tributação nacional e NBS importadas (Anexo B v1.01) e emitenfse/nfseserie no formulário da filial do pessoas
- [x] #9 Menu do notas separa Notas de Produtos de Notas de Serviço
- [x] #10 Menu do notas separa DFe NFe de DFe NFS-e
- [x] #11 DFe NFS-e: a lista mostra só a outra parte (o tomador quando a filial emitiu, o emitente quando ela é a tomadora)
- [x] #12 DFe NFS-e: filtro para ver só as notas em que a filial é a emitente, ou só as em que ela é a tomadora
- [x] #13 DFe NFS-e: botão para abrir o DANFSe de qualquer NFS-e da caixa, inclusive as emitidas por outros contra nós
- [ ] #14 Menu do notas agrupado em Produtos, Serviços, Transporte e Configurações, com o mesmo ícone de nota (azul produtos, verde serviços)
- [x] #15 As duas telas de DFe (NF-e e NFS-e) com o layout de listagem das Notas de Serviço
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decisões de 17/11/2026 (noite):
- Madrugada liberada no dev: SQL no banco de dev, arquivos sem commit, DPS de teste na homologação com o certificado da FDF (201.pfx copiado de produção; abre com a senha da tabela, válido até 06/03/2027), scripts/tinker, lint.
- DANFSe: atualizar o sped-da 1.1.6 → 1.1.7 (NFePHP\DA\NFSe\Danfse); conferir DANFE e DANFCE no teste da manhã.
- Códigos de tributação nacional e NBS: importar as tabelas oficiais (Anexo B v1.01, anexo_b-nbs2-lista_servico_nacional-snnfse-v1-01-20260122.xlsx), como a tblncm.
- Agora o serviço (código de tributação, NBS, alíquota) é escolhido na própria nota. FUTURO: o produto tipo Serviço guarda esses dados e a nota copia do produto.

- Série da DPS = 1, começando no número 1 (a FDF ainda não emitiu pelo site).

- Simples da FDF: regime 1 (federais e ISS no DAS, regApTribSN=1, opSimpNac=3 ME/EPP). A alíquota efetiva do ISS (muda todo mês, como no Coplan) é DIGITADA em cada nota, campo vazio (pAliq).

- Total aproximado de tributos: não informar (indTotTrib=0, Decreto 8.264/2014).

- emitenfse e nfseserie configuráveis no formulário da filial do pessoas (além do SQL que liga a 201 com série 1).

- Retenção federal (INSS/IR/PIS/COFINS/CSLL): sem campos agora (Fábio não sabe se ocorre); entra depois se aparecer caso. ISS retido continua como campo (padrão não retido).
- Não gera título a receber por enquanto (a NFS-e 1.01 não tem forma de pagamento nem duplicatas; o gPgtoVinc da NT 009 ainda não está em produção).
- Cancelamento (evento) entra JÁ no mínimo de amanhã, com justificativa, no detalhe da nota.
- Botão Enviar e-mail (DANFSe + XML ao tomador, e-mail de NF-e da pessoa) entra JÁ no mínimo de amanhã.

- Valores como no XML: valor do serviço + desconto incondicionado + desconto condicionado (sem dedução/redução; NFS-e não tem frete/seguro/outras).

- Tomador OPCIONAL (toma é minOccurs=0 na DPS): nota sem cliente identificado, pensando nos serviços de cópia/encadernação em que o cliente quase nunca se identifica.

- Local da prestação: selecionável (pode ser outra cidade), padrão = cidade da filial.

- Exportação para o Domínio: notas de serviço ficam fora por enquanto.

Implementação (madrugada 17→18/11/2026), sem commit:

BLOQUEIO: a homologação recusa com E0116 — Sinop cadastrou a FDF no CNC com inscrição municipal, e a DPS tem que levar o número que está lá (testei um fictício: mesma recusa). O número não existe em nenhum lugar do nosso banco nem na consulta do CNC. Criei tblfilial.inscricaomunicipal (formulário da filial no pessoas). Preencher a da FDF e reenviar.

Até onde foi testado em homologação (nota de teste 1 da FDF, série 1 número 1): certificado + mTLS ok; DPS assinada passa no schema do servidor; consulta da DPS ok (E2404, não autorizada). Validei no XSD local: DPS com tomador PJ e PF, descontos, ISS retido, texto com quebra de linha/aspas tipográficas/emoji, e o pedido de cancelamento. DANFSe do sped-da 1.1.7 renderiza (exemplo do próprio sped-da, inclusive com marca de cancelada). Endpoints testados por script em transação desfeita: CRUD, validações, selects, filiais, duplicar, excluir e as recusas por status. NÃO testados contra o Sistema Nacional (dependem de nota autorizada): autorização, cancelamento, consulta de cancelamento, e-mail.

Achados no caminho:
- Dois leiautes: a homologação já roda o da reforma tributária (finNFSe obrigatório, xDescServ no fim do cServ; schemas esquemas_prod_restrita_20261020) e a produção o 'atual' do portal (schemas 20260209). NfseXmlService monta conforme o ambiente; em produção, NFSE_LEIAUTE_RTC=true no .env troca para o novo quando o portal anunciar (se a produção recusar com E1235 pedindo finNFSe, é isso).
- O XSD oficial 1.01 tem um erro no padrão da série (^...$ literal em XSD); corrigido na cópia local e documentado em api/resources/nfse/schemes/README.md.
- Campos de texto da DPS não aceitam quebra de linha nem caracteres fora de U+0020–U+00FF: a quebra vira ' | ' (hint no formulário); aspas/travessões tipográficos viram ASCII; emoji sai.
- Sem a declaração <?xml encoding=UTF-8?> o servidor recusa (E1229).
- Pela API: Sinop é aderente ao Emissor Nacional (parametrizacao/5107909/convenio).

Arquivos: api/database/nfse.sql (estrutura + liga a 201 série 1) e nfse_tabelas.sql (338 códigos, 918 NBS, 989 correlações; gerado das planilhas oficiais); api/app/Mg/NotaFiscalServico/ (model, controller, request, resource, NotaFiscalServicoService = cadastro, NfseXmlService = XML, NfseSefinService = HTTP/assinatura/XSD, NfseService = enviar/consultar/cancelar/DANFSe/e-mail, mail + view nfse-mail); Select{TributacaoNacional,Nbs}Controller; rotas; NFePHPPathService (arvore nfse/ e conversas); config/mg.php + .env.example (NFSE_LEIAUTE_RTC); composer.lock (sped-da 1.1.7); api/resources/nfse/schemes (1.01 e restrita). notas: rotas /nota-servico, menu 'Notas de Serviço', páginas lista/detalhe/form, store, service, drawer de filtros, form; @components MgNotaFiscalServicoAcoes, MgSelectTributacaoNacional, MgSelectNbs. pessoas: emitenfse, série DPS e inscrição municipal no form/detalhe da filial.

Go-live: deploy do api (composer install traz o sped-da 1.1.7), notas e pessoas; nfse.sql e depois nfse_tabelas.sql; inscrição municipal da FDF pelo pessoas.

Testes de 18/11/2026 (manhã), autorizados pelo Fábio:

Inscrição municipal da FDF = 14644: lida das 78 NFS-e que ela emitiu, no ADN de PRODUÇÃO (só leitura, com o certificado dela). Já entra pelo nfse.sql. A distribuição do ADN só responde com ?cnpjConsulta=<CNPJ> (sem ele: REJEICAO sem erro) — anotar para o critério 6.

Regras de Sinop/Sistema Nacional achadas na homologação (todas tratadas):
- E0625: ME/EPP com ISS no DAS só pode informar alíquota com ISS RETIDO. A alíquota agora só vai (e só é obrigatória) com retenção; sem retenção não é gravada.
- E0712: ME/EPP não pode 'não informar' o total de tributos: exige o % aproximado do Simples (pTotTribSN). Nova coluna tributospercentual, digitada em cada nota (decisão do Fábio), obrigatória para filial do Simples.
- E0824: em Sinop, NFS-e SEM tomador não pode ser cancelada (parâmetro do município). Não mexi em nada — impacta a decisão de tomador opcional (cópias/encadernação); a recusa do Sistema Nacional aparece na tela.
- O vLiq do Sistema Nacional NÃO desconta o ISS retido (1000 − 100 − 50 = 850 com ISS retido de 36,63; o Coplan descontava). A conta do valor líquido foi alinhada com a dele.

Bugs corrigidos: consulta não detectava cancelamento feito fora (o evento vem em eventos[].arquivoXml com base64 duplo); consultar nota cancelada dizia 'autorizada'; nota ficava ERR sem mensagem quando falhava algo depois da numeração; falha ao gravar o XML depois da autorização voltaria a nota para ERR (agora fica AUT e o Consultar busca o XML de novo); consulta repete uma vez se a conexão cair (envio nunca repete).

Homologação: NFS-e 1 a 4 autorizadas (1 e 3 sem/ com tomador), 2 e 4 canceladas pelo sistema, cancelamento de fora simulado e detectado, reenvio de nota autorizada sem resposta recuperado pela consulta da DPS, DANFSe conferido visualmente (sem validade jurídica, IM, % Simples). Telas testadas no Chrome sem tela contra o quasar dev do notas: listagem, criar com tomador/ISS retido, enviar, DANFSe, XML, janela de e-mail, consultar, cancelar, duplicar, editar e excluir — sem erro de API. Endpoints e validações por script em transação desfeita; API da filial grava emitenfse/nfseserie/inscricaomunicipal.

Não testado: envio real de e-mail (no dev os workers da fila, api-worker-*, estão parados desde 17/11; o e-mail foi testado com Mail::fake e a view renderizada). Produção usa o leiaute 'atual' (sem finNFSe), que só foi validado no XSD.

Segunda rodada de testes (18/11/2026):

NFePHP inteiro atualizado (decisão do Fábio): o sped-da 1.1.7 chama Keys::extractAccessKey(), que só existe no sped-common >= 5.1.17 — com só o sped-da atualizado, DANFE, DANFCE e DAMDFE quebravam. Agora sped-common 5.1.18, sped-nfe 5.2.11, sped-da 1.1.7 (sped-mdfe, sped-ibpt e sped-gtin já estavam na última). Conferido: XML de 11 notas reais (NF-e normal, com ST, devolução, NFC-e) montado antes e depois — 9 idênticos byte a byte; nos 2 da filial 201 (sem IE) o sped-nfe novo omite a tag <IE/> vazia (inválida nos dois casos). Na SEFAZ-MT de homologação: NFC-e e NF-e emitidas, DANFCE/DANFE gerados (conferidos na imagem) e canceladas; DAMDFE gerado com MDF-e de exemplo do sped-da. Notas de teste no banco de dev: 3519338 (NFC-e) e 3519339 (NF-e), canceladas.

NFS-e em homologação: tomador PJ sem CEP, prestação em Cuiabá, competência do mês passado, descrição de 2000 caracteres, só desconto condicionado — autorizadas. Recusas tratadas na validação (backend e formulário): ISS retido sem tomador (E0204) e competência depois de hoje (E0015). Envio simultâneo da mesma nota: a trava deixa passar um só. Nota recusada corrigida e reenviada reaproveita o número da DPS. Filtros da lista conferidos pela API. Telas: validações novas e limpeza do NBS ao trocar o código conferidas no Chrome sem tela.

Em aberto para o Fábio: E0240 — o Sistema Nacional confere o CEP do tomador na base dos Correios; CEP genérico de cidade no cadastro (ex.: 78200-000 de Cáceres) é recusado (a NF-e aceita).

Fora do escopo (só relato): o NFePHPMakeService::montarXml grava '-assinado.xml' sem a chave em nota nova (usa o $nf em memória antes de recarregar); há arquivos assim no dev desde agosto. No dev os workers da fila (api-worker-*) estão parados desde 17/11.

- E0240 (decisão do Fábio, 18/11/2026): mantém o endereço do tomador na DPS; quando o CEP do cadastro for recusado, corrige-se o cadastro do cliente.

Caixa de DF-e da NFS-e (critério 6), pedida pelo Fábio antes do deploy (18/11/2026), no mesmo estilo da tela de DF-e das NF-e:
- tbldistribuicaonfse (no nfse.sql) + XML em nfse-dfe/{filial}/{ambiente}/AAAA/MM/DD/{nsu}-{chave}.xml.gz.
- NfseDistribuicaoService: lê o ADN por NSU com ?cnpjConsulta (o ADN devolve os NSU maiores que o informado, em lotes, sem dizer o último: lê até vir vazio; E2220 = nada novo); grava a linha, o XML, os dados da NFS-e (número, emitente, tomador, valor), liga à nossa nota pela chave; evento CANCELAMENTO/SUBSTITUICAO de nota nossa AUT leva a CAN. DataHoraGeracao vem em horário de Brasília sem fuso: convertida para Cuiabá.
- Comando nfse:dist-dfe agendado a cada 30 min (como o nfe-php:dist-dfe), só filiais com emitenfse.
- Endpoints /v1/nfse-dfe (lista com filtros, filiais-habilitadas, consultar/{filial}/{nsu?} um lote por chamada, xml, processar).
- notas: menu 'DFe NFS-e' (/nfse-dfe), lista com rolagem infinita, filtros (chave, filial, tipo, período, NSU), janela 'Consultar ADN' por filial, botões abrir a nota / reprocessar / XML.
Testado: comando leu 8 documentos da homologação (6 NFS-e + 2 cancelamentos) ligados às nossas notas; cancelamento que chega pela caixa levou a nota a CAN; endpoints; tela no Chrome sem tela (lista, consulta ao ADN, filtro de tipo, XML, reprocessar, abrir a nota).

Serviços de DF-e do ADN, todos chamáveis pela tela (pedido do Fábio, 18/11/2026):
- GET /contribuintes/DFe/{NSU}?cnpjConsulta&lote=true (lote seguinte ao NSU): janela 'Consultar ADN' e comando agendado.
- GET /contribuintes/DFe/{NSU}?cnpjConsulta&lote=false (devolve o documento SEGUINTE ao NSU; pede-se NSU-1): botão 'Buscar este NSU no ADN' em cada linha (POST /v1/nfse-dfe/distribuicao/{id}/consultar-adn).
- GET /contribuintes/NFSe/{chave}/Eventos (eventos da nota, no mesmo formato do lote, com NSU; sem evento devolve a própria NFS-e): botão 'Eventos desta NFS-e no ADN' em cada linha e campo de chave na janela 'Consultar ADN' (POST /v1/nfse-dfe/consultar-eventos/{filial}/{chave}); grava o que faltar sem duplicar (testado recuperando um evento apagado; chave inexistente = E2240).

Terceira rodada de testes (18/11/2026):
- Agendador (mgspa-api-scheduler) roda como www-data: as pastas nfse/ e nfse-dfe/ nascem com o dono certo; o nfse:dist-dfe já rodou sozinho no dev.
- Produção da Sefin conferida só com leitura (GET /dps de DPS inexistente): URL e mTLS ok com o certificado da FDF, as duas grafias do caminho funcionam, e a DPS série 1 número 1 está livre em produção. O erro de produção vem como 'erro': {codigo, descricao} (singular, minúsculas) — o tratamento de mensagens já cobre.
- Bug corrigido: duas leituras do ADN ao mesmo tempo (robô x botão) gravavam o mesmo NSU duas vezes (risco de chave duplicada). Agora há trava por filial (quem chega depois espera); testado com dois comandos simultâneos.
- Leitor da caixa conferido com os 124 documentos REAIS da FDF no ADN de produção (só leitura, transação desfeita, sem gravar arquivo): 0 erros; 123 NFS-e + 1 cancelamento; 45 NFS-e emitidas por terceiros contra a FDF, todas com emitente, tomador e valor.
- Pessoas: tela da filial no Chrome sem tela — detalhe mostra Série DPS, Emite NFS-e e Inscrição Municipal; o formulário carrega e salva.
- Varredura final: php -l em todo PHP alterado, eslint no notas e no pessoas, prettier nos componentes compartilhados — ok.

Ajustes pedidos no teste do Fábio (18/11/2026):
- DANFSe não quebrava a linha da descrição e das observações (a NFS-e guarda ' | ' no lugar da quebra, porque o XSD não aceita quebra de linha). Agora a cópia do XML que vai para o DANFSe volta o ' | ' para quebra de linha (o XML fiscal fica igual): nas observações sempre; na descrição só se couber — a caixa 'Descrição do Serviço' do sped-da tem altura fixa (~3 linhas inteiras a 7pt) e o resto invadia o bloco de Tributação Municipal (os métodos de desenho do sped-da são private, não dá para mudar a altura). Sem caber em linhas, fica corrido; acima de 500 caracteres, corta com reticências só no PDF. Hint do formulário: 'No DANFSe cabem cerca de 3 linhas: o detalhe vai nas Observações'.
- Duplicar agora pede confirmação ('Tem certeza que quer duplicar…'); Cancelar não cria nada (testado no Chrome sem tela).
- Alterar a nota recusada continuava mostrando a recusa antiga (trocou o tomador, a faixa vermelha seguia com o E0240 do tomador anterior). Agora salvar a alteração apaga a mensagem da recusa; o status fica 'Não Autorizada' (DPS numerada pode ter sido autorizada sem resposta, e o Enviar consulta antes de reenviar). Testado pela API em transação desfeita (nota 16, ERR E0204): trocar o tomador limpa a mensagem e mantém ERR e o número; alteração recusada (validação, troca de filial da DPS numerada) não apaga a recusa; nota autorizada segue sem alterar; nota em digitação segue DIG.

Formulário da nota de serviço refeito para celular (pedido do Fábio, 18/11/2026): cinco cards com o cabeçalho azul da tela de detalhe — Nota (filial e competência na mesma linha), Tomador (tomador; Retenção do ISS como select e a alíquota ao lado, desabilitada sem retenção em vez de sumir), Serviço (código, NBS, descrição, local da prestação), Valores (valor, % tributos, descontos, 2 por linha no celular e 4 no desktop, com o resumo base/ISS retido/líquido no rodapé do card) e Observações. Conferido no Chrome sem tela a 412 px: sem rolagem lateral; retenção habilita a alíquota e deixa o tomador obrigatório.

- Formulário (pedido do Fábio): alíquota do ISS saiu do card do Tomador (lá fica só a Retenção do ISS, linha inteira) e foi para Valores, que ficou: Valor do Serviço / Desc. Incond. + Desc. Cond. / Tributos + Alíquota ISS (desabilitada sem retenção).

- Correção (18/11/2026, conferido no Anexo I do leiaute): valor líquido = serviço − descontos − retenções (vTotalRet), e o ISS entra quando retido. A homologação devolveu vLiq sem descontar o ISS retido, contra a própria regra (e o Coplan descontava): a conta da tela e do backend voltou a seguir a regra. Alíquota do ISS limitada a 5% (E0595/E1300); antes o limite era só o formato do campo (9,99).

- Alíquota do ISS não pode passar do % de Tributos do Simples (decisão do Fábio, 18/11/2026: no Simples o ISS é uma fatia do DAS; a regra nacional só limita a 5%). Validado no formulário ('Não pode passar dos Tributos') e no servidor.

Fechada em 18/11/2026 a pedido do Fábio com dois critérios em aberto: #5 (substituir a NFS-e) não foi feito; #7 (e-mail) tem o botão e a janela, mas o envio real não foi testado (workers da fila parados no dev).

DFe NFS-e: outra parte, filtro Emissão e DANFSe (critérios #11 a #13, pedidos pelo Fábio em 18/11/2026; plano aprovado):
- tbldistribuicaonfse.emitida boolean (padrão da tblnotafiscal.emitida): true quando o CNPJ que a chave traz (dígitos 10 a 23: município 7 + ambiente 1 + tipo de inscrição 1 + inscrição 14 + ...) é o da filial. Vale para os eventos, que só têm a chave. Preenchida no NfseDistribuicaoService::processar; as linhas antigas, pelo api/database/nfse_emitida.sql.
- Lista: a linha 1 mostra só a outra parte (tomador quando emitida, com 'Tomador não identificado' na nota sem tomador; emitente quando não). Ícone call_made (emitida) / call_received (tomada); eventos como antes.
- Filtro 'Emissão' no drawer com o EMITIDA_OPTIONS das notas de produto ('Nossa Emissão' / 'Emitida por Terceiro'); o false é tratado no drawer (?? null), na store e no backend (filter_var só com o parâmetro presente).
- DANFSe: GET /v1/nfse-dfe/distribuicao/{id}/danfse, botão em toda linha com chave (NFS-e e eventos). Pega o XML da NFS-e da chave na caixa e marca CANCELADA se há evento CANCELAMENTO ou SUBSTITUICAO da mesma chave. Render separado em NfseService::renderDanfse (o DANFSe das nossas notas usa o mesmo). Falha do sped-da (XML de outro município/leiaute) vira 422 com o motivo.
- Filial só intermediária entra como emitida = false (mostra o emitente); aceito pelo Fábio, não ocorreu nos 124 documentos reais.
Go-live: rodar api/database/nfse_emitida.sql ANTES do deploy do api (sem a coluna, o processar falha e o nfse:dist-dfe para de gravar a caixa).

Layout das duas telas de DFe (critério #15, pedido do Fábio em 19/11/2026: 'o padrão das telas de listagem é o das Notas de Serviço'):
- DfeDistribuicaoPage (NF-e) e NfseDfeDistribuicaoPage (NFS-e) no molde da NotaFiscalServicoListPage: q-page com max-width 1086px, q-card flat bordered + q-list separator, q-icon pequeno à esquerda, quatro colunas (documento + filial | outra parte + CNPJ | data + NSU | valor), MgEmptyState, respiro para o FAB.
- Diferenças do molde: a chave ganhou uma linha inteira embaixo (na coluna do meio só cabiam ~40 dos 62 caracteres e sumia o número da nota); as ações ficam à direita com largura fixa (210px NFS-e, 110px NF-e, para as colunas alinharem) e, como DFe não tem tela de detalhe, descem para baixo da linha no celular e no tablet (lt-md) em vez de sumir. Botões flat round sm grey-7; na NF-e o 'Consultar SEFAZ' da linha virou cloud_sync (o mesmo do 'Buscar este NSU no ADN'). Avisos da NF-e em red-5/green-5.
- NF-e: título = nome do evento ou tipo do DF-e (NFe Completa/Resumida); meio = emitente (fantasia da pessoa ou nome do XML) com o CNPJ, ou o autor do evento (CNPJ - órgão); valor com a natureza embaixo.

Testes de 19/11/2026 (autorizados pelo Fábio):
- nfse_emitida.sql no banco de dev: 22 linhas marcadas (19 NFS-e + 3 eventos, todas da FDF); rodado de novo, não mexe em nada.
- API por script em transação desfeita: filtro emitida (sem parâmetro, true, false, vazio, inválido); NFS-e de terceiro (chave com outro CNPJ) fica emitida = false; DANFSe da NFS-e, pelo evento de cancelamento (marca CANCELADA conferida na imagem do PDF) e pela linha da NFS-e cancelada (mesmo PDF); 404 sem a NFS-e na caixa; 422 com XML que o sped-da não desenha e sem chave; DANFSe da nossa nota (render separado) igual.
- Bug corrigido no teste: filter_var(null/'') devolve false — a lista sem filtro mostraria só as de terceiro. O filtro só se aplica com o parâmetro presente e não vazio.
- Telas no Chrome sem tela contra o quasar dev do notas, API simulada (com linhas de terceiro, que o dev não tem): outra parte, setas, 'Tomador não identificado', evento, DANFSe (modal e erro 422), filtro Emissão (pedido emitida=false/true, sobrevive ao recarregar, limpar tira o parâmetro), celular 412px e tablet 800px sem rolagem lateral e com as 6 ações, desktop sem duplicar ações; DFe NF-e com NF-e, evento e documento não processado.
- Não testado: DANFSe de NFS-e de terceiro de verdade (o dev só tem as nossas de homologação) — fica com o Fábio, com as notas da Bee, do Rogério e do Wellington.

Achado fora do escopo (relatado, sem mexer): a DFe NF-e ordena por data desc e o Postgres põe as linhas sem data (documentos não processados, 309 no dev) no topo da lista.
<!-- SECTION:NOTES:END -->
