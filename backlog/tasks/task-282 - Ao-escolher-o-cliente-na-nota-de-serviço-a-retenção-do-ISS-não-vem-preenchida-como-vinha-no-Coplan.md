---
id: TASK-282
title: >-
  Ao escolher o cliente na nota de serviço, a retenção do ISS não vem preenchida
  como vinha no Coplan
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-18 15:12'
updated_date: '2026-11-18 16:57'
labels:
  - notas
dependencies: []
priority: low
type: feature
ordinal: 293000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No Coplan (sistema da prefeitura de Sinop), ao escolher o cliente a nota já dizia se havia retenção do ISS: a prefeitura tem o cadastro de quem é substituto tributário. No emissor nacional (TASK-279) a retenção é escolhida à mão em cada nota.

O Sistema Nacional não substitui isso (verificado em 18/11/2026, consultas só de leitura ao ADN): a parametrização de retenções do município (GET /parametrizacao/5107909/{competencia}/retencoes) responde 'Parâmetros de retenções do Artigo Sexto não encontrados' para Sinop, em produção e homologação; e, onde existe, ela é por serviço, não por cliente. O ADN informa a alíquota municipal por serviço (GET /parametrizacao/5107909/17.03.01.000/{competencia}/aliquota = 4,00% desde 30/10/2024; o código vai com pontos, 9 dígitos).

Pedido do Fábio: marcar no cadastro do cliente se ele retém o ISS, e a nota pré-selecionar a retenção ao escolher o tomador (MgNotaFiscalServicoForm, campo 'Retenção do ISS'; com retenção o tomador já é obrigatório e a alíquota fica habilitada).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Cadastro do cliente (pessoas) com a marcação de que ele retém o ISS (substituto tributário)
- [x] #2 Nota de serviço pré-seleciona a retenção do ISS ao escolher o tomador
- [x] #3 Definido com o Fábio se a retenção pré-selecionada pode ser trocada na nota
- [x] #4 Inscrição municipal no cadastro da pessoa, ao lado da inscrição estadual, com o Retém ISS do lado (Documentos)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Implementado (18/11/2026):

- Banco: tblpessoa.retemiss boolean not null default false (api/database/pessoa_retemiss.sql). Go-live: rodar o SQL ANTES do deploy — o select de pessoa (v1/select/pessoa) passa a ler a coluna e quebra sem ela. Os clientes são marcados à mão no cadastro.
- Inscrição municipal: a TASK-279 tinha criado tblfilial.inscricaomunicipal — erro, documento da empresa mora na pessoa, como a IE. Movida para tblpessoa.inscricaomunicipal: o nfse.sql (go-live da TASK-279 ainda pendente) cria a coluna na tblpessoa e grava o 14644 na pessoa da filial 201; a DPS lê a IM do prestador em Filial->Pessoa; o campo saiu do model e das telas de filial (formulário, editar, nova, detalhe). No banco de dev a coluna foi movida e a tblfilial.inscricaomunicipal dropada.
- Pessoas (pedido do Fábio no teste): Retém ISS saiu do dialog 'Editar cliente e crédito'. No 'Editar detalhes' a linha dos documentos ficou CNPJ/CPF, (RG), Inscrição Estadual, Inscrição Municipal e o toggle Retém ISS do lado. Em Documentos aparece '/ IM 14644' depois da IE e a linha 'Retém ISS' quando marcado. Como a IE, qualquer um que edita os detalhes altera (saiu do bloco do Financeiro).
- Notas: escolher o tomador pré-seleciona a Retenção do ISS pelo cadastro (MgSelectPessoa traz retemiss no @select). Abrir uma nota salva não mexe no que foi gravado; limpar o tomador não mexe na retenção.
- AC #3, decisão do Fábio (18/11/2026): pode trocar na nota, mas avisa — quando a retenção da nota diverge do cadastro do tomador, o campo mostra 'No cadastro do cliente: retém / não retém o ISS'. Vale também ao editar nota salva (NotaFiscalServicoResource devolve pessoa.retemiss). O backend grava o que vier do formulário.
- A IM do tomador NÃO vai na DPS: só a do prestador, como antes.

Consulta ao ADN refeita em 18/11/2026 (só leitura, certificado da FDF, produção e homologação):
- Parametrização: o swagger real só tem aliquota, historicoaliquotas, beneficio (por número), convenio, regimes_especiais (por serviço) e retencoes (por município + competência). Não existe rota por CPF/CNPJ (a do manual dá 404). Retenções de Sinop: 'não encontrados'; regimes especiais do 17.03.01: 'não encontrados'.
- CNC (cadastro de IM): a API só tem POST /CNC (a prefeitura envia). Não há consulta.
- As NFS-e que a FDF emitiu pelo Coplan estão no ADN de produção (124 NSUs, desde jan/2025) e trazem a IM do tomador (toma/IM) e a retenção (tpRetISSQN), preenchidas pelo Coplan a partir do cadastro da prefeitura. Retidos: Migliorini & Migliorini (04576775000160, IM 9552, 24 notas) e Imobiliária Irmãos Nogueira (00360408000174, IM 5735, 9 notas); os outros 15 tomadores, não retido.
<!-- SECTION:NOTES:END -->
