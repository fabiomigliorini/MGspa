---
id: TASK-137
title: 'Carga: endpoint de listagem com rotulo do ponto e totais do recorte'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-22 12:23'
updated_date: '2026-09-22 12:29'
labels:
  - agro
dependencies:
  - TASK-136
priority: medium
type: feature
ordinal: 147000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Endpoint novo GET v1/carga/listagem para a tela de romaneios. NAO reaproveitar GET v1/carga: ele e contrato do sync offline (stores/sincronizacao.js -> normalizarCargaDoServidor depende de classificacao e CargaPontoS completos); mexer no Resource dele quebra o Patio em silencio.

Escopo:
- CargaPontoService::rotulo(CargaPonto): monta 'Talhao 12 - TMG 7262' | 'Armazem Sede' | 'CT-0042 - Bunge', espelhando rotuloPonto/rotuloContrato/rotuloDoPlantio de agro/src/stores/carga.js. Exposto em CargaPontoResource como $ret['rotulo'].
  Motivo: hoje quem monta o rotulo e a store Dexie; a tela nova e online e nao tem esse cache.
- Acrescentar 'CargaPontoS.Plantio.Fazenda' a const WITH (o romaneio precisa do nome da fazenda; hoje so carrega Talhao e Variedade). Aditivo.
- CargaListagemResource: colunas cruas + Safra (com Cultura.pesosaca) + CargaPontoS com rotulo. SEM classificacao. Derruba de ~14 para ~9 queries por pagina de 50.
- CargaController@listagem: pesquisar(..., WITH_LISTAGEM)->paginate(per_page ?: 50), mais UMA query agregada sobre o recorte inteiro (count, sum bruto/desconto/liquido) devolvida em ->additional(['totais' => ...]). E o que alimenta a barra de totais da tela.
- Rota antes de carga/{codcarga} em routes/api.php (sem colisao real: o wildcard tem whereNumber, mas registrar antes segue a convencao do arquivo).

ATENCAO: CargaService faz if (!empty($filter['inativo'])), entao a AUSENCIA da chave traz canceladas junto. scopeAtivoInativo: 1=ativas, 2=canceladas, 9=todas. O front tem que mandar sempre.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado.

Arquivos:
- api/app/Mg/Grao/CargaPontoService.php (novo): rotulo(CargaPonto) nos 3 contatipos, com fallback pro codigo. Comentario avisa que o formato espelha rotuloPonto() da store do agro -- mudou aqui, muda la.
  Cuidado documentado: Plantio.talhao e COLUNA (nome do talhao na safra), nao a relacao Talhao; o Eloquent resolve o atributo antes da relacao.
- api/app/Mg/Grao/CargaPontoResource.php: + $ret['rotulo'].
- api/app/Mg/Grao/CargaListagemResource.php (novo): colunas + Safra (pesosaca) + CargaPontoS com rotulo. SEM classificacao.
- api/app/Mg/Grao/CargaService.php: + totais(filter) -- query agregada sobre o recorte inteiro, sem eager load e sem ordem. Sacas NAO entra no agregado de proposito: pesosaca varia por cultura e somar saca de soja com milho nao significa nada.
- api/app/Mg/Grao/CargaController.php: + listagem() e + relatorio() (esse consumido pela TASK-140).
- api/routes/api.php: GET carga/listagem e GET carga/relatorio, registradas ANTES do wildcard carga/{codcarga}.

Verificado no dev:
- route:list confirma listagem e relatorio resolvendo antes de carga/{codcarga}; nenhuma colisao.
- Eager load: WITH_LISTAGEM 10 queries x WITH (sync) 16 queries por pagina de 50.
- totais() bate EXATO com SQL manual (qtd=8 bruto=94000.000 desconto=9693.682 liquido=84306.318) e respeita filtro de ponto (unidade1/ORIGEM -> qtd=4).
- Payload da linha sem classificacao, com Safra e CargaPontoS.
- rotulo resolvido nos 3 tipos: PLANTIO '01 - Soja boa', UNIDADE 'SILO', CONTRATO 'MILHO-26-0001 - Amaggi - Sinop - Camping'.
<!-- SECTION:NOTES:END -->
