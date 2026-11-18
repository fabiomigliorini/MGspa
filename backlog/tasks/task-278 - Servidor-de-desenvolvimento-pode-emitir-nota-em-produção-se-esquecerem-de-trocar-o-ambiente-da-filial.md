---
id: TASK-278
title: >-
  Servidor de desenvolvimento pode emitir nota em produção se esquecerem de
  trocar o ambiente da filial
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-18 01:51'
updated_date: '2026-11-18 02:24'
labels:
  - notas
dependencies: []
priority: low
type: bug
ordinal: 287000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O ambiente da SEFAZ (1 = produção, 2 = homologação) fica em tblfilial.nfeambiente, por filial — legado de antes do NFePHP. Como é dado do banco, toda cópia da base de produção para desenvolvimento precisa trocar as filiais para 2 à mão (no banco de dev hoje as 21 estão em 2); se esquecerem, o dev emite nota de verdade. O ambiente é do servidor, não da filial: passa a ser UMA variável no .env do api (dev = 2, produção = 1).

Origem: levantamento da NFS-e Nacional (17/11/2026). A NFS-e vai usar o mesmo ambiente da NF-e, então esta task vem antes e a NFS-e já nasce lendo o .env.

Onde o api lê o campo hoje:
- api/app/Mg/NFePHP/NFePHPConfigService.php:42 (tpAmb)
- api/app/Mg/NFePHP/NFePHPMakeService.php:149 (tpAmb do XML)
- api/app/Mg/NFePHP/NFePHPService.php:860 (inutilização) e :1091 (consulta chave)
- api/app/Mg/NFePHP/NFePHPPathService.php:94 e :105 (pasta producao/homologacao)
- api/app/Mg/NFePHP/SefazLogService.php:46 e :102
- api/app/Mg/Inutilizacao/InutilizacaoService.php:41
- api/app/Mg/Mdfe/MdfeNfePhpConfigService.php:28 e MdfeNfePhpService.php:54
- api/app/Mg/Filial/Filial.php:64 e :91 (fillable/cast)
- comentados: NFePHPManifestacaoService.php:26, NFePHPDistDfeService.php:43
No pessoas: components/empresa/FormFilial.vue, pages/empresa/FilialEditar.vue, NovaFilial.vue, FilialView.vue.

Atenção: o MGsis (vivo só para NFe de Terceiros) ainda lê tblfilial.nfeambiente — protected/models/NfeTerceiro.php:516 monta a pasta do XML com ele, e NFePHPNovoController.php em vários pontos. Por isso a coluna não pode simplesmente sumir do banco.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Ambiente da NF-e, NFC-e e MDF-e lido de uma variável única no .env do api, documentada no .env.example
- [x] #2 Nenhum uso de $filial->nfeambiente sobra no api (envio, XML, inutilização, consulta, pastas, log, MDF-e)
- [x] #3 Campo do ambiente sai do cadastro e da tela da filial no pessoas
- [x] #4 Destino da coluna tblfilial.nfeambiente decidido com o Fábio, considerando que o MGsis (NFe de Terceiros) ainda a lê
- [x] #5 Coluna tblfilial.validadecertificado eliminada: sai do model Filial (api/app/Mg/Filial/Filial.php:72 e :94) e da tela da filial no pessoas (pages/empresa/FilialView.vue:44 e :319); a validade real já é lida do .pfx em CertificadoService::validarValidade; ninguém mais lê a coluna (MGsis e MGLara não usam)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decisões (17/11/2026, com o Fábio):
- AC #4: opção C — a coluna tblfilial.nfeambiente sai do banco; o MGsis passa a ler o ambiente do protected/.env.php.
- Sem a variável, ou com valor diferente de 1, é homologação (2). Só emite de verdade o servidor que diz 1.

Implementação:
- api: config('mg.nfe_ambiente') em config/mg.php, lido de NFE_AMBIENTE (documentado no .env.example). Substitui o $filial->nfeambiente em NFePHPConfigService, NFePHPMakeService, NFePHPService (inutilização e consulta chave), NFePHPPathService (pasta producao/homologacao), SefazLogService, InutilizacaoService, MdfeNfePhpConfigService e MdfeNfePhpService. Linhas comentadas da Manifestação/DistDFe removidas (esses dois serviços seguem fixos em produção, setEnvironment(1)). Filial: nfeambiente e validadecertificado saem do fillable/casts; constantes NFEAMBIENTE_* removidas (sem uso).
- pessoas: campo Ambiente NFe sai do FormFilial, NovaFilial e FilialEditar; Ambiente NFe e Validade Certificado saem da FilialView.
- MGsis (repo próprio): Filial::nfeAmbiente() lê define('NFE_AMBIENTE', ...) do .env.php; substitui todos os $...->Filial->nfeambiente do NFePHPNovoController e do NfeTerceiro. Coluna sai do model (rules/labels) e das telas filial/_form e filial/view.
- MGLara (repo próprio): nfeambiente sai do fillable, do docblock e das constantes do model Filial.
- MGdb (repo próprio): ajusta-cadastros.sql para de fazer nfeambiente = 2 (a coluna deixa de existir).
- api/database/inutilizacao.sql (backfill de agosto/2026, já aplicado) continua citando f.nfeambiente: é histórico, não roda de novo.

Go-live (api/database/filial_nfeambiente_validadecertificado_drop.sql):
1. ANTES do deploy, em produção: select codfilial, filial, nfeambiente from tblfilial where nfeambiente <> 1; — filial em 2 de propósito passa a emitir em produção.
2. NFE_AMBIENTE=1 no .env do api (config:cache se usado).
3. define('NFE_AMBIENTE', 1); no protected/.env.php do MGsis.
4. Deploy do api, pessoas, MGsis e MGLara.
5. Rodar filial_nfeambiente_validadecertificado_drop.sql.
Sem 2 e 3, produção emite em homologação (nota sem valor fiscal).

Testes no dev (17/11/2026), com as duas colunas renomeadas durante o teste para simular o drop:
- config('mg.nfe_ambiente'): sem variável = 2; NFE_AMBIENTE=1 → 1; =2 → 2; =producao → 2.
- Config NF-e e MDF-e com tpAmb 2; pastas em homologacao/.
- Status SEFAZ (filial 101): tpAmb 2, cStat 107, log ambiente=2.
- Consulta chave da NFC-e 3519334: cStat 100, log ambiente=2.
- NF-e 3519337: XML com tpAmb 2 e destinatário 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO'; envio processado em homologação (rejeição 306, IE do destinatário inativa — cadastro da nota de teste).
- Inutilização filial 101, mod 55, série 99, nº 1: cStat 102, tblinutilizacao e log com ambiente=2.
- FilialController update/store com o payload do pessoas: 201, sem nfeambiente/validadecertificado no resource (em transação, rollback).
- Componentes do pessoas compilados (vue compiler-sfc), eslint e prettier ok.
- MGsis (container avulso, PHP 5.6): nfeAmbiente() 2/1/2/2 para vazio/1/2/producao; Filial valida sem a coluna; caminho do XML de terceiro e tpAmb do montarConfiguracao seguem a variável.
- MGLara: Filial carrega sem a coluna.
- MGdb: primeiro update do ajusta-cadastros.sql roda sem a coluna (rollback).
- Drop script: roda e derruba as duas colunas (testado com ROLLBACK). Colunas restauradas no dev.
<!-- SECTION:NOTES:END -->
