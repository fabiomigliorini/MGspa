---
id: TASK-278
title: >-
  Servidor de desenvolvimento pode emitir nota em produção se esquecerem de
  trocar o ambiente da filial
status: To Do
assignee: []
created_date: '2026-11-18 01:51'
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
- [ ] #1 Ambiente da NF-e, NFC-e e MDF-e lido de uma variável única no .env do api, documentada no .env.example
- [ ] #2 Nenhum uso de $filial->nfeambiente sobra no api (envio, XML, inutilização, consulta, pastas, log, MDF-e)
- [ ] #3 Campo do ambiente sai do cadastro e da tela da filial no pessoas
- [ ] #4 Destino da coluna tblfilial.nfeambiente decidido com o Fábio, considerando que o MGsis (NFe de Terceiros) ainda a lê
<!-- AC:END -->
