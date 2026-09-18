---
id: TASK-111
title: 'HMR morre silenciosamente: certificado do dev server nao cobre o hostname'
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-17 20:27'
updated_date: '2026-09-18 19:40'
labels:
  - negocios
  - pessoas
  - notas
  - contas
  - estoque
  - agro
dependencies: []
priority: medium
type: bug
ordinal: 103000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Sintoma: no console do Chrome, 'WebSocket connection to wss://negocios-dev.mgpapelaria.com.br:9900/?token=... failed' e '[vite] failed to connect to websocket (Error: WebSocket closed without opened.)'. O HMR para de atualizar a tela.

Causa raiz: com devServer.https=true o Quasar usa @quasar/ssl-certificate, que gera um certificado self-signed SO PARA LOCALHOST (CN=localhost; SANs: localhost, localhost.localdomain, lvh.me, *.lvh.me, [::1], 127.0.0.1, fe80::1). Ele NAO cobre negocios-dev.mgpapelaria.com.br, que e justamente o host configurado em devServer.hmr.host. Toda conexao TLS para esse nome e nao confiavel. Para a pagina o Chrome mostra a interstitial e da pra clicar em Prosseguir; para um WebSocket nao existe interstitial, entao o handshake falha calado - exatamente o 'WebSocket closed without opened.'.

O certificado fica cacheado em node_modules/@quasar/ssl-certificate/ssl-server.pem e e regerado a cada 30 dias (e a cada node_modules novo). A excecao que o Chrome guarda e amarrada a impressao digital do certificado e tem validade, entao a cada troca o HMR volta a morrer sozinho.

Verificado:
- servidor OK: handshake WS responde 101 Switching Protocols via curl/TLS com ALPN http/1.1 e o token correto
- Vite 8 sobe HTTP/2 (createSecureServer, allowHTTP1:true); o Chrome abre conexao http/1.1 separada pro WS, entao HTTP/2 nao e o problema
- Chrome headless (mesmo binario 153) com o certificado aceito: '[vite] connected.' e '[vite] hot updated'
- nao existe excecao salva pra negocios-dev.mgpapelaria.com.br:9900 em nenhum profile do Chrome

Vale para os 6 apps: pessoas/notas/contas/estoque/agro apontam hmr.host para sistema-dev.mgpapelaria.com.br, que o certificado tambem nao cobre.

Opcoes de correcao:
1. mkcert: gerar certificado para os hostnames de dev e apontar devServer.https={key,cert}; a CA local fica confiavel no Chrome e o problema some de vez
2. tirar https do dev server e usar hmr.protocol ws (checar antes se alguma tela depende de secure context)
3. paliativo: reabrir a pagina e aceitar o certificado toda vez que ele trocar
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Corrigido em 18/09, sem certificado novo. Primeiro no negocios, validado pelo Fabio no mesmo dia; depois padronizado nos outros 5 apps (pessoas, notas, contas, estoque, agro) a pedido dele.

Causa real: o devServer.hmr.host estava fixo (negocios-dev no negocios, sistema-dev nos outros), mas a aba do negocios costuma ser aberta por sistema-dev.mgpapelaria.com.br:9900. A excecao de certificado do navegador vale por host, entao aceitar o certificado no sistema-dev para abrir a pagina nao cobria o WebSocket, que ia para o negocios-dev. Nos outros 5 apps o problema so aparecia se alguem abrisse por um host diferente de sistema-dev.

Correcao, identica nos 6 quasar.config.js:
- removido o bloco hmr (protocol/host/port). Sem ele o cliente do Vite conecta no mesmo host e porta da pagina, e o certificado aceito para abrir a pagina cobre o WebSocket.
- adicionado allowedHosts: ['.mgpapelaria.com.br']. O Vite pula a checagem de host nas requisicoes HTTP quando o servidor e HTTPS, mas no WebSocket ele checa sempre. O host so era aceito porque vinha do hmr.host. Sem essa linha o handshake volta 400 (foi o que aconteceu na primeira tentativa, que so removia o bloco hmr).

Verificado: no negocios, handshake 101 no sistema-dev e no negocios-dev, e Brave headless com perfil limpo mostrando '[vite] connected.' nos dois hosts, alem do teste do Fabio. Nos outros 5: diff identico ao do negocios, prettier ok, mesma familia de versao (vite 8.0.13 a 8.0.16, @quasar/app-vite 2.6.x) com a mesma checagem de host no codigo do Vite, e 'quasar inspect -c dev' no pessoas mostrando allowedHosts no servidor do Vite e nenhum hmr. Eles passam a valer no proximo quasar dev de cada app. Touch em .vue nao serve de teste de hot update, porque o plugin do Vue ignora arquivo sem mudanca de conteudo.

Historico: a correcao com mkcert (certificado curinga compartilhado pelos 6 apps) foi implementada em 17/09 e desfeita em 18/09 a pedido do Fabio, por incomodar demais. A task nasceu como TASK-104 e foi renumerada para TASK-111 porque um merge trouxe outra TASK-104 ja commitada.
<!-- SECTION:NOTES:END -->
