---
id: TASK-283
title: Adaptar sistema para CNPJ alfanumérico
status: Done
assignee:
  - '@fabio'
created_date: '2026-11-19 16:31'
updated_date: '2026-11-21 17:58'
labels:
  - pessoas
dependencies: []
priority: low
type: bug
ordinal: 294000
---

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Banco e cadastro de pessoa aceitam CNPJ com letras (colunas em texto, validação, formatação, telas do pessoas)
- [x] #2 PDV pesquisa e cadastra cliente com CNPJ com letras
- [x] #3 Notas (NF-e, NFC-e, NFS-e, MDF-e) e chave de acesso aceitam CNPJ com letras
- [x] #4 Cheque, PIX, boleto BB e demais integrações mandam o CNPJ como texto
- [x] #5 NFe de Terceiros do MGsis e Mercos do MGLara funcionam com a coluna em texto
- [x] #6 Remover a importação de pessoa pela SEFAZ, que só o MGsis chamava numa tela fora de uso e estava quebrada
- [x] #7 PIX do BB manda a mensagem para quem paga, como o Sicredi
- [x] #8 Cancelar e encerrar MDF-e de filial com CNPJ manda o CNPJ (o ajuste de CPF fica só para filial pessoa física)
- [x] #9 Montar o XML da NF-e não grava mais o arquivo '-assinado.xml' sem chave no nome
- [x] #10 Nova pessoa: Continuar no passo do CPF/CNPJ só avança com documento válido
- [x] #11 PDV: wizard de cliente não quebra quando a consulta da IE falha (segue com Sem IE / Outra IE)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Go-live: api/database/cnpj_alfanumerico.sql junto com o deploy do MGspa (api e apps), do MGsis e do MGLara; o código antigo usa to_char(cnpj), que não existe para texto. Colunas de CNPJ viram varchar(14), maiúsculo, zeros à esquerda, CPF com 11 e CNPJ com 14; zero (sem documento) vira NULL. Já rodado no dev em 19/11. Bibliotecas (sped-mdfe, sped-nfe, openboleto) e APIs (BB, SEFAZ) não mudaram: considera-se que já aceitam. Exemplo de CNPJ válido para teste: 12.ABC.345/01DE-35.

Testes em 19/11: consulta de cadastro da SEFAZ recusava o CNPJ do BB 00.000.000/E08G-12 pelo schema PL_009_V4 do sped-nfe. Consulta de cadastro, manifestação e consulta de situação pela chave passaram a usar o PL_010_V1.30, que já vem na versão instalada (mesmo leiaute, só o tipo do CNPJ/chave aceita letras). O download pela chave (distDFe) continua só com dígitos em todos os schemas do sped-nfe e fica como está.
<!-- SECTION:NOTES:END -->
