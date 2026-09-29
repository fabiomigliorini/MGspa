# Bateria de conferência do agro

Simula o pátio de verdade contra a API do dev e confere os números contra as regras
decididas. Usa o mesmo payload do app, o mesmo endpoint (`POST v1/carga/sincronizar`) e
o nginx e o php-fpm reais, com disparos simultâneos quando o cenário é de corrida.

Não é PHPUnit: não existe banco de teste, e `RefreshDatabase` apagaria o dev. A bateria
cria uma massa própria (`ZZTESTE`) e apaga essa massa no fim.

Task: TASK-184. O plano completo das fases está em `specs/PLANO-AGRO-ARMAZENAMENTO.md`.

## Como rodar

```bash
# camadas (dentro do container da API, no repo montado)
docker exec -u www-data -w /opt/www/MGspa/api mgspa-api php tests/agro/run.php todos --relatorio
docker exec -u www-data -w /opt/www/MGspa/api mgspa-api php tests/agro/run.php cenarios
docker exec -u www-data -w /opt/www/MGspa/api mgspa-api php tests/agro/run.php estresse --aparelhos=3 --caminhoes=150
docker exec -u www-data -w /opt/www/MGspa/api mgspa-api php tests/agro/run.php estresse --rampa=3,6,12,24 --minutos=3
docker exec -u www-data -w /opt/www/MGspa/api mgspa-api php tests/agro/run.php limpar

# conferência SÓ LEITURA do banco inteiro (dev agora, PROD na virada)
docker exec -u www-data -w /opt/www/MGspa/api mgspa-api php tests/agro/run.php conferir
docker exec -i -e PGOPTIONS='-c default_transaction_read_only=on' mgdb-mgdb-1 psql -U mgsis -d mgsis < api/tests/agro/conferencia.sql

# paridade pátio × servidor (no host; lê o vetores.json gravado pela camada desconto)
node api/tests/agro/desconto-front.mjs
```

Opções: `--usuario=login|cod` (o padrão é o primeiro Administrador ativo), `--manter` (não apaga a
massa), `--relatorio` (grava markdown em `storage/app/agro-bateria/`), `--vetores=10000`,
`--semente=2809`, `--aparelhos`, `--caminhoes`, `--rampa`, `--minutos`.

A saída termina com código ≠ 0 quando há FALHA ou ERRO.

## Camadas

| Camada | O que confere | Tempo |
|---|---|---|
| `cenarios` | A: o romaneio de ponta a ponta · C: teto e saldo do contrato · T: silos e transferências | ~30 s |
| `corrida` | R: pedidos simultâneos (mesma carga 5×, cópia velha, 3 caminhões no mesmo teto, reenvio) | ~5 s |
| `desconto` | D: 13 vetores nomeados + 10.000 sorteados no `CargaService::calcular` real, numa transação desfeita; grava `vetores.json` | ~80 s |
| `valores` | V: fixação, tributos (FETHAB/IAGRO/SENAR), câmbio travado e plano de NF | ~1 s |
| `listagem` | L: totais da listagem e do relatório PDF, por tipo e sem canceladas | ~3 s |
| `permissoes` | P: usuário só de Caixa leva 403 em toda rota do agro (a lista vem das rotas registradas) | ~4 s |
| `estresse` | E1 dia de colheita (3 aparelhos), E2 rampa, E3 ponto quente, E4 dois aparelhos no mesmo caminhão | ~45 s + rampa |
| `conferir` | as invariantes do `conferencia.sql` no banco inteiro, só leitura | segundos |
| `todos` | todas as camadas acima, menos a rampa | ~3 min |

Depois de cada camada, a bateria roda as mesmas invariantes do `conferencia.sql` sobre a
massa: I1 a I14, com extrato × carga, balanço de massa da safra, teto do contrato, matriz de
origem e destino e tabela de classificação.

## Como ler

- `[OK]` a regra vale.
- `[FALHA]` a regra quebrou; no detalhe, "regra:" é o número que a regra decidida manda.
- `[INFO]` é uma medida que não reprova (ex.: silo negativo é permitido pela decisão D1).
- `[ERRO]` o próprio cenário não rodou; conta como falha.

## As regras (é contra isto que se compara)

Estão em `lib/Referencia.php`, escritas a partir das decisões e da norma, e não copiadas do
`CargaService`:

- **Desconto:** cascata da IN MAPA 11/2007 (soja) e 60/2011 (milho): NORMALIZADO, FATOR,
  deságio e reduzbase.
- **Kg inteiro (28/09):** cada item é arredondado meio-para-cima sobre a fração exata
  (bcmath), e bruto, desconto, líquido, rateio e extrato ficam em kg inteiro.
- **Silo de origem (28/09):** baixa o **bruto**; o destino recebe o **líquido**; a diferença
  é quebra.
- **Tabela de classificação:** congelada no 1º FINALIZADO (recomendação do plano).
- **D1:** silo sem saldo avisa e não bloqueia.
- **D2:** matriz de origem e destino por tipo de romaneio.

## Massa de teste

- Culturas, safras (2099), fazenda, talhões, silos e contratos com prefixo `ZZTESTE`.
- As cargas nascem com data fixa no passado (03/08/2026), para não aparecerem no pátio
  de ninguém.
- As camadas funcionais apagam o que criaram. O `estresse` (e o `todos`) **mantém** a massa
  para conferir a listagem e o relatório com volume.
- A massa mantida é **recolhida** do pátio no fim da rodada:
  - carga que ficou aberta vira cancelada;
  - safra, silo, contrato e talhão ficam inativos.

  O pátio só oferece o que está ativo e, desde a TASK-109, puxa as cargas abertas de
  qualquer dia e de todas as safras. Na listagem `/cargas`, limpe o período e filtre o
  motorista "ZZTESTE". `run.php recolher` faz o mesmo à mão.
- A próxima rodada, ou o `limpar`, apaga essa massa.
- A limpeza vai pelas chaves ZZ e aborta sem apagar nada se alguma carga de fora apontar para
  silo, contrato ou talhão da massa.

## Segurança

- A bateria recusa rodar fora do dev: exige `APP_ENV` diferente de production, `APP_URL` com
  `-dev` e o host do banco em `AGRO_BATERIA_DB_HOSTS`.
- O `conferir` roda numa transação só leitura.
- Os tokens (Passport) são criados para a rodada e apagados no fim.

## Quando uma fase mexer no que a bateria enxerga

- **Fase 2 (TASK-180):** o `Patio::payload` passa a mandar `versao`, e o aparelho ganha o
  modo "app antigo" (sem versão, em gramas) para a janela de atualização do PWA.
- **Fase 3 (TASK-182):** o `desconto-front.mjs` passa a testar o `ticketDoPatio`.
- **Fase 8 (TASK-138/140):** `Listagem::relatorio()` acompanha o layout novo da blade.

## Linha de base — 28/09/2026

Medida com o código da árvore, incluindo o trabalho em andamento da TASK-109/171.

- `run.php todos` terminou com **OK 38 · FALHA 59 · INFO 12 · ERRO 0** em 161 s.
- Nas corridas o resultado varia de uma rodada para outra. Por isso a tabela traz a faixa
  observada.

| Camada | Resultado |
|---|---|
| conferir (dev inteiro) | I4: ponto ≠ extrato nas cargas 2, 3, 9 · I14: cargas 1, 4, 6, 8 fora da matriz / mesmo silo · 3 cargas com kg fracionado |
| cenarios | A1/A4 kg em gramas · A6 retirada manual soma · A7 trocar a placa muda o líquido · A8 entrada inválida dá 500 ou passa · C2/C4/C5/C6/C8/C9 · T2 silo baixa o líquido · T3/T7 |
| corrida | R1 500 no uuid · R2 500 e pontos duplicados · R3/R4 cópia velha desfaz fechamento e cancelamento · R5 75 t num teto de 60 t · R6 extrato duplicado em 5–6 de 10 · R7 422 falso · R8 carga presa |
| desconto | fórmula = norma em 10.000/10.000 (gramas) · 9.706/10.000 fora do kg inteiro (máx 2,0 kg) · ordem repetida e tolerância 100 aceitas |
| paridade (node) | pátio ≠ servidor em 12/10.013 (1 g) · 14 tickets não fecham a conta · 470 sacas diferentes entre pátio e ficha |
| valores | V1/V2/V4 OK (a isenção de FETHAB declarada funciona) · V2b fixação sem tributos não guarda os da época · V5 edita abaixo do travado · V7 plano de NF dá 500 |
| listagem | L1 total único somando recebido e expedido · L2 inclui canceladas · L3/L4/L5 OK |
| permissoes | P1: 91 de 91 rotas abertas para um usuário só de Caixa |
| estresse | E1 OK (3 aparelhos, 150 caminhões, 857 pedidos em 28 s, p95 do sincronizar ~100 ms, zero erro) · E3 contrato com 100.000 kg num teto de 60.000 · E4 dois aparelhos no mesmo caminhão: as duas gravações passam, e 7–12 de 20 cargas ficam FINALIZADAS sem extrato · E2 (rampa) ainda não rodada: pede horário combinado |
