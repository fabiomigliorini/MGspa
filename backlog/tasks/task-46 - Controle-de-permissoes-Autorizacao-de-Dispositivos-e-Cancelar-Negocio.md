---
id: TASK-46
title: 'Controle de permissoes: Autorizacao de Dispositivos e Cancelar Negocio'
status: To Do
assignee: []
created_date: '2026-09-12 15:53'
updated_date: '2026-10-10 18:07'
labels:
  - negocios
dependencies: []
priority: high
type: feature
ordinal: 112000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Origem: negocios/todo — secao SEGURANCA. No arquivo original constava "(Allan)".
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Sincronizar aparece em todas as telas do negócios
- [x] #2 Ícone de sincronização não fica vermelho logo depois de sincronizar à tarde
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Sincronizar em todas as telas e hora em 24h (10/10, commits f796b2a16 e 9b5c52bbf): o BtnSincronizacao saiu do OfflineLayout e foi para o MainLayout, ao lado do usuário (o quiosque segue com o dele). O ícone ficava vermelho à tarde porque o carimbo sincronizado dos endpoints v1/pdv/* saía em 12 horas (date 'Y-m-d h:i:s' no PdvService e no PdvPranchetaService): às 13:47 gravava 01:47 e passava do limite de 4 h. Virou 'H'. O mesmo carimbo decide o que a base offline apaga (below sincronizado), então o apagado no servidor também ficava no PDV até o dia seguinte. Teste: sincronizar depois das 12h e o ícone fica na cor normal; Caixa, Pagamentos, Listagem, Vales, Comandas, Confissão, Configuração, Dispositivos e Woo têm o botão, e o PDV só um.
<!-- SECTION:NOTES:END -->
