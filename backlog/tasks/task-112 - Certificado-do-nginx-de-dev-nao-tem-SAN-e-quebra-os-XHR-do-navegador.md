---
id: TASK-112
title: Certificado do nginx de dev nao tem SAN e quebra os XHR do navegador
status: Done
assignee: []
created_date: '2026-09-17 21:01'
updated_date: '2026-09-19 19:47'
labels:
  - api
dependencies: []
priority: low
type: bug
ordinal: 105000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Descoberto durante a TASK-111.

O nginx de dev (repo MGweb) serve sistema-dev, api-dev e api-mkt-dev na porta 443 com /opt/www/MGweb/data/cert-dev/cert.crt. Esse certificado e um self-signed de 'openssl req' SEM NENHUMA extensao: nao tem subjectAltName e o subject nem tem CN (so C=BR, ST=MT, L=Sinop, O=MG). Navegador nenhum aceita isso sem excecao manual. Vence em 28/04/2027.

Como o API_URL dos apps e https://api-dev.mgpapelaria.com.br/api/, todo XHR do ambiente de dev depende de uma excecao aceita a mao nesse certificado. Maquina nova = ninguem entende por que as chamadas falham.

Correcao possivel: gerar um certificado com SAN para os tres hostnames (ex.: mkcert com '*.mgpapelaria.com.br') e trocar as duas linhas ssl_certificate/ssl_certificate_key em cada um dos 3 confs. ATENCAO: na TASK-111 uma correcao parecida com mkcert foi desfeita em 18/09 por incomodar demais, entao alinhar com o Fabio antes de mexer. Os confs:
  /opt/www/MGweb/data/nginx-dev/default.conf
  /opt/www/MGweb/data/nginx-dev/api.conf
  /opt/www/MGweb/data/nginx-dev/api-mkt.conf

Atencao: MGweb e outro repositorio git, entao o commit e separado.

Limpeza junto: /opt/www/MGweb/criar-certificado-dev.sh (openssl sem SAN, gera esse cert) e /opt/www/MGweb/habilitar-https-dev.sh (tentativa anterior com mkcert que grava em ~/.certs-mg-dev/, diretorio que nao e montado nos containers e por isso nunca foi usado) ficam obsoletos. Decidir se apaga.
<!-- SECTION:DESCRIPTION:END -->
