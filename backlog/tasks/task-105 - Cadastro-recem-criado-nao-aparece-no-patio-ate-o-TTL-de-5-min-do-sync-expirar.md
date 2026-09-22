---
id: TASK-105
title: Cadastro recem-criado nao aparece no patio ate o TTL de 5 min do sync expirar
status: Done
assignee:
  - '@fabio'
created_date: '2026-09-16 21:52'
updated_date: '2026-09-22 13:54'
labels:
  - agro
dependencies: []
priority: high
type: bug
ordinal: 104000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: TASK-101, 16/09/2026. A unidade armazenadora SILO foi criada as 18:43 na UnidadesArmazenadorasPage e nao aparecia no select de destino do patio. Servidor OK (v1/unidade-armazenadora devolve o SILO). Causa: sincronizacao.sincronizar() so refaz o pull de cadastros quando o cache passou de 5 min (TTL_SINCRONIZACAO); o sync do onMounted do CargaPage pula o pull dentro da janela, e NENHUM cadastro do agro (unidade, safra, fazenda, talhao, plantio, contrato, veiculo, cultura, variedade, parametro) invalida esse TTL ao salvar. Resultado: o operador cadastra e o patio nao enxerga — obriga a esperar ou a achar o botao de nuvem. Correcao: invalidar o TTL de forma centralizada no adapter do api.js quando uma requisicao mutante bem-sucedida atinge um recurso de referencia; o sincronizar passa a ler o timestamp do localStorage na hora da decisao.
<!-- SECTION:DESCRIPTION:END -->
