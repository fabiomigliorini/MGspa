---
id: TASK-235
title: >-
  Cadastro de clientes e vendas ficam legíveis no navegador do PDV e podem vazar
  (LGPD)
status: To Do
assignee: []
created_date: '2026-10-17 17:38'
labels:
  - negocios
dependencies: []
priority: low
type: feature
ordinal: 246000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
O PDV guarda no IndexedDB do navegador a tabela pessoa inteira (27 mil cadastros com nome, CPF/CNPJ, IE e endereço completo) e a tabela negocio com todas as vendas já feitas naquele PDV, cada uma com o cadastro inteiro da pessoa embutido e sem expurgo. Qualquer um com acesso ao PC lê isso no DevTools ou copiando o perfil do Chrome. Produto não é dado pessoal. Origem: levantamento na TASK-197 (16-17/10/2026), separado a pedido do Fábio.

Medições (17/10/2026, Chrome headless no dev): cifrar 81 MB com AES-GCM do WebCrypto leva 0,4 s; pesquisar pessoa em memória foi mais rápido que o índice multiEntry do Dexie (32 ms contra 350 ms), então dado cifrado (que não indexa) não piora a busca.

Decisão pendente, custódia da chave da cifra: (a) servidor entrega ao abrir o app, autenticado pela credencial do PDV, e fica só em memória: disco ilegível sem o servidor e inativar revoga, mas PDV que abre sem rede não acha cliente até conectar uma vez (produto e Consumidor funcionam); (b) CryptoKey não extraível guardada no navegador: funciona offline ao abrir, protege de DevTools e cópia de arquivo, não de quem copia o perfil inteiro do Chrome.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Pessoa offline só com os campos que o PDV usa: saem IE, endereço, número, bairro e complemento; a Devolução busca o endereço online
- [ ] #2 Negócio local guarda só código e fantasia da pessoa, não o cadastro inteiro
- [ ] #3 Negócio local fechado e sincronizado há mais de 30 dias é apagado do navegador; o histórico fica no servidor
- [ ] #4 Dispositivo inativado apaga o IndexedDB ao descobrir que está inativo
- [ ] #5 Tabelas pessoa e negócio gravadas cifradas (AES-GCM, WebCrypto), com a pessoa pesquisada em memória
- [ ] #6 Custódia da chave decidida com o Fábio (servidor em memória ou navegador) e implementada
- [ ] #7 Padrão de máquina dos PCs do PDV: disco cifrado (BitLocker/LUKS) e usuário do sistema com senha (infra, não código)
<!-- AC:END -->
