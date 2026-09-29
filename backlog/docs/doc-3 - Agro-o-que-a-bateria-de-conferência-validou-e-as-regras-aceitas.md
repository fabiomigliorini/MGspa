---
id: doc-3
title: 'Agro: o que a bateria de conferência validou e as regras aceitas'
type: specification
created_date: '2026-09-29 13:37'
---

# Agro — o que a bateria de conferência validou e as regras aceitas

**Origem:** bateria `api/tests/agro/` (TASK-184), com linha de base em 28/09/2026. Ela
repete no dev o que o pátio faz e confere os números contra as regras decididas.

**Classificação:** confirmada com quem prioriza em 29/09/2026. Este é o registro de
referência para separar **defeito** de **forma de trabalhar aceita**. Antes de tratar um
comportamento do agro como bug, conferir aqui.

**Como conferir de novo:** ver `api/tests/agro/README.md`.
- `run.php todos --relatorio` roda a bateria completa.
- `run.php conferir` roda só leitura, no banco inteiro.
- `node api/tests/agro/desconto-front.mjs` confere a paridade entre pátio e servidor.

Os ids entre parênteses (A1, C5, T2…) são os cenários da bateria.

## 1. Regras aceitas — não são defeito

| Regra | Como o sistema deve se comportar | Cenários |
|---|---|---|
| **Contrato pode ser carregado além do saldo** (29/09) | O caminhão completa a carga para aproveitar o frete, e o comprador aceita. O **servidor não bloqueia**; o pátio avisa quanto passa do contratado; a tela do contrato mostra "Entregue a mais" (saldo negativo, não zero). Cancelar nunca é barrado. | C1, C4, R5, E3 · C8 · I7 (INFO) |
| **Silo pode ficar negativo** (D1, 28/09) | O pátio mostra o saldo, avisa e pede confirmação ao finalizar; o servidor não bloqueia; o Estoque destaca o silo negativo. | T4 · I11 (INFO) |

Decisões de 28/09 que definem o comportamento certo (detalhe em `specs/PLANO-AGRO-ARMAZENAMENTO.md`):

| # | Decisão |
|---|---|
| D2 | Origem e destino por tipo de romaneio. Entrada: talhão ou contrato de compra → silo ou contrato de venda. Saída: silo → contrato de venda. Transferência: silo → **outro** silo. |
| D3 | Carga finalizada pode ser editada. |
| D6 | Saída de silo com desconto: o **silo de origem baixa o bruto**, o destino recebe o líquido e a diferença é quebra. |
| D7 | **Kg inteiro em tudo**: descontos, líquido, rateio, extrato e ticket. |
| D8 | Tabela de classificação **congelada no romaneio no 1º FINALIZADO**; editar a tabela não muda romaneio fechado. |
| D9 | Retirada manual de silo sem desconto: líquido = −quantidade. |
| D10 | Acesso ao agro: Administrador e Gerente. |
| D11 | Pico real do pátio: até 3 aparelhos ao mesmo tempo. |
| — | Totais da listagem e do relatório por tipo (recebido, expedido, transferido), com as canceladas fora da soma. |

## 2. Confirmado que NÃO é prática aceita — é defeito

- Transferência para o mesmo silo (T3).
- Tabela de classificação nova recalculando romaneio já fechado (A7).
- Silo inativo recebendo carga nova (T7).
- "Entregue" da safra somando contrato de compra (C9).
- As cargas 1, 4, 6 e 8 do dev, fora da matriz D2, são **dados de teste**; a matriz vale.

## 3. Validado e funcionando

- **Romaneio:** expedição e transferência sem desconto fecham certo (A2, A3). Cancelar e reativar devolvem o extrato igual, pelo botão e pelo pátio (A5).
- **Silos:**
  - 50 transferências aleatórias conservam o grão (T1);
  - transferências cruzadas simultâneas não travam nem perdem grão (T6);
  - a API separa o saldo do silo por safra (T5).
- **Descontos:**
  - a fórmula é a da norma (IN MAPA 11/2007 soja, 60/2011 milho) em 10.000 de 10.000 casos sorteados, em gramas;
  - FATOR, deságio e tolerância 99,9 conferem;
  - leituras na tolerância, zeradas ou ausentes dão desconto zero;
  - umidade 100 % leva o líquido a zero;
  - o cálculo pela API é igual ao do servidor.
- **Contrato:** ajuste manual entra no saldo a entregar da tela e o estornado sai (C2, C3); reativar acima do contratado é aceito (C4); volume em aberto aceita qualquer quantidade (C7); caminhões fechando juntos são aceitos sem grão perdido nem duplicado (R5).
- **Valores:**
  - líquido da fixação em R$ pela tabela de tributos (V1: 500 sc a R$ 120,00 = R$ 58.335,05, R$ 116,6701/sc);
  - isenção de FETHAB declarada no modal, com a linha do grupo zerada (V2);
  - fixação em US$ com câmbio travado em parte (V4).
- **Listagem e relatório:** os 13 agrupamentos fecham (subtotais = total, carga num grupo só); a tela bate com o PDF; o relatório sem período é recusado com mensagem (L3–L5).
- **Desempenho:** 3 aparelhos e 150 caminhões rodam com ~860 pedidos em 30 s, p95 do sincronizar de ~100–120 ms e zero erro (E1). Nesse pico real o dev folga com 5 workers PHP.
- **Dados do dev:** a tabela de classificação é igual à norma; talhão e contrato estão sempre na safra e cultura certas; o colhido é lançado na safra do talhão.

## 4. Defeitos confirmados, por task

As corridas variam entre rodadas; quando é o caso, os números abaixo são a faixa observada.

- **TASK-180 — sincronização entre aparelhos**
  - A cópia velha de outro aparelho desfaz fechamento (volta para TARA e zera o extrato) e desfaz cancelamento (R3, R4).
  - Dois aparelhos gravando o mesmo caminhão: as duas gravações passam, e 7 a 12 de 20 cargas ficam **FINALIZADAS sem extrato** (E4).
  - Extrato duplicado em 5 a 6 de 10 rodadas ao reativar durante o envio (R6).
  - A mesma carga enviada várias vezes dá 500, pontos duplicados ou 422 falso (R1, R2, R7).
  - Entrada inválida dá 500 em vez de mensagem: uuid malformado, PBT gigante, leitura 1000 %, parâmetro repetido (A8a, A8d, A8e, A8f).
- **TASK-182 — descontos**
  - Cálculo em gramas: 9.706 de 10.000 casos fogem do kg inteiro, em até 2 kg.
  - Romaneio fechado muda quando a tabela muda (A7).
  - Kg do ponto diferente do extrato (A4; cargas reais 2, 3 e 9 do dev).
  - Carga fica presa em 422 com o cache de parâmetros velho (R8).
  - 14 tickets não fecham a conta (ex.: 9.999 − 51 ≠ 9.949).
  - O pátio calcula 1 g diferente do servidor em 12 casos.
  - As sacas diferem entre pátio e ficha em 470 casos (ex.: 10,1 × 10,0).
  - O cadastro aceita ordem repetida e tolerância 100.
  - O desconto pode passar do bruto (D7).
- **TASK-170 — números**
  - O servidor **bloqueia** a carga que passa do saldo do contrato, contra a regra aceita (C1, E3).
  - Cancelar pelo pátio é barrado pela trava (C5).
  - O saldo esconde o excesso, mostrando 0 em vez de −4.000 (C8).
  - O entregue da safra soma compra (C9).
  - Retirada manual soma no silo, e ajuste só com líquido grava zero (A6).
  - O extrato da tela carrega só 50 linhas (A9, conferir na tela).
- **TASK-130 — silos**
  - O silo de origem baixa o líquido em vez do bruto, e o desconto vira grão fantasma no balanço da safra (T2, I1, I3, I8).
  - Silo inativo recebe carga (T7).
  - O pátio pede o saldo sem safra e soma milho com soja (T5).
- **TASK-181 — origem e destino**
  - Transferência para o mesmo silo é aceita (T3).
  - Tara maior que o PBT, PBT com fração e PBT acima de 150 t são aceitos (A8b, A8c, A8g).
- **TASK-183 — valores do contrato**
  - A fixação salva sem tributos não guarda os da época e segue a tabela ao vivo (V2b).
  - Editar abaixo do que já foi travado passa (V5).
- **TASK-133:** o plano de emissão da NF do contrato dá 500 (V7).
- **TASK-138/140:** o total da listagem é um número só, somando os tipos e as canceladas (L1, L2).
- **TASK-129:** um usuário só de Caixa entra em 91 de 91 rotas do agro (P1).

## 5. A conferir fora da bateria

- **UPF do FETHAB:** janeiro ou julho do ano anterior, pela Lei 13.002/2025 (`ContratoCalculoService::competenciaFethab`). A planilha antiga usava janeiro do mesmo ano. Confirmar com a contabilidade (V8).
- **Card da fixação e conferência do recebimento:** as contas são do navegador; conferir na tela do contrato (V3, V6).
- **Aviso de excesso do contrato no pátio:** usa o saldo a entregar da tela; conferir na tela depois da TASK-170 (C6).
- **Rampa até 24 aparelhos (E2):** ainda não rodada; deixa a API dev lenta para todos e pede horário combinado.
