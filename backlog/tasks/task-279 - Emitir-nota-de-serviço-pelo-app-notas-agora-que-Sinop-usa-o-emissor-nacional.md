---
id: TASK-279
title: 'Emitir nota de serviço pelo app notas, agora que Sinop usa o emissor nacional'
status: In Progress
assignee: []
created_date: '2026-11-18 02:01'
updated_date: '2026-11-18 02:01'
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

Decisões (levantamento de 17/11/2026):
- Estrutura PRÓPRIA: tabela de nota de serviço e telas à parte, fora da tblnotafiscal e das telas de NF-e (62 arquivos do api filtram a tblnotafiscal por modelo).
- Uma nota = um serviço: o XSD 1.01 da DPS tem um serv/cServ e um valores (um código de tributação nacional, uma descrição de até 2000, um valor).
- Status com os mesmos códigos da NF-e: DIG, ERR, AUT, CAN (substituída = CAN, ligada pela nota nova). Sem inutilização nem denegação.
- Só digitada no notas por enquanto; gerar a partir do negócios fica para depois. Telas com a mesma usabilidade das da nota fiscal: listagem, view com os botões de ação no cabeçalho (enviar, cancelar, DANFSe), form de criar/editar.
- tblfilial ganha emitenfse e nfseserie; o ambiente é o mesmo da NF-e (TASK-278 leva para o .env).
- Comunicação = serviço próprio: certificado, assinatura e XSD pelo sped-common; DANFSe pelo sped-da 1.1.7 (NFePHP\DA\NFSe\Danfse — a API de DANFSe do ADN foi suspensa em 01/07/2026, NT 008). NFePHP não tem a parte de API (mantenedor disse que não fará).
- API: Sefin (POST /nfse síncrono, GET /dps/{id}, POST /nfse/{chave}/eventos) e ADN (distribuição /contribuintes/DFe/{NSU}, eventos por chave). mTLS com o A1 da filial; DPS assinada, gzip+base64 em JSON. Consulta pela chave não mostra cancelamento: status vem dos eventos.
- Distribuição do ADN em estrutura à parte (tabela e pasta próprias), não na tbldistribuicaodfe.

Prazo: emitir já em 18/11/2026 (testes de manhã, deploy ao meio-dia). Por isso o mínimo para emitir vem primeiro; cancelamento e ADN logo depois — até lá cancela-se pelo site.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Falar com o Sistema Nacional: certificado da filial, DPS assinada e validada no XSD, envio e consulta da DPS, testado na produção restrita
- [ ] #2 Tabela da nota de serviço e emitenfse/nfseserie na tblfilial, com o regime do Simples Nacional da FDF
- [ ] #3 Tela no notas: listar, criar/editar, enviar e baixar o DANFSe (sped-da 1.1.7), com a usabilidade das telas de nota fiscal
- [ ] #4 Cancelar a NFS-e (evento) pela tela
- [ ] #5 Substituir a NFS-e (DPS nova apontando a anterior)
- [ ] #6 Ler a caixa do ADN em estrutura própria: nossas notas e eventos (cancelamento de fora leva a CAN) e as notas emitidas contra nós
<!-- AC:END -->
