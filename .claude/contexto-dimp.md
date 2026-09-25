# Contexto: DIMP no MGspa

Documento de passagem de bastão. Reúne tudo que existe hoje sobre DIMP neste repositório, como
chegou a este estado, o que foi medido em dados reais e o que ficou em aberto. Escrito em
25/09/2026.

---

## 1. O que é DIMP aqui, e por que o projeto se importa

DIMP = Declaração de Informações de Meios de Pagamento. As adquirentes de cartão e os PSPs do PIX
informam à SEFAZ **todas** as transações do CNPJ. O fisco cruza isso com os documentos fiscais
emitidos, e a diferença é tipificada como **presunção de omissão de receita** — presunção *juris
tantum*, que admite prova em contrário.

O projeto se importa porque a **venda de vale compras** abre essa diferença por construção: o
dinheiro entra hoje, com o `cAut` real na mão da adquirente, e o documento fiscal sai meses depois,
na retirada do material, com `tPag=12`. Não é evasão — é adiantamento de cliente, fora do campo de
incidência do ICMS no momento do ingresso.

A tese fiscal completa está em `backlog/docs/doc-1 - Tese-de-regularidade-fiscal-da-venda-de-vale-compras.md`.
Os pontos que interessam ao DIMP:

- **§ 3.4** — a divergência é presunção relativa e se desconstitui com prova.
- **§ 4.2** — venda mista (mercadoria + vale numa passada de cartão só): a NFC-e leva apenas a fração
  da mercadoria no `detPag`, **mantendo o `cAut` da transação real** como elo de auditoria. É
  **vedado** fechar com `vTroco` inflado ou item fictício (risco penal-tributário, Lei 8.137/1990).
- **§ 4.4** — prescreve um **relatório de conciliação mensal** com diferença zero, arquivado. É esse
  relatório que converte uma notificação de malha em resposta documental imediata.
- **§ 5** — a trilha de auditoria exigida do ERP (passivo escriturado, vínculo individualizado com o
  `cAut`, baixa casada com a NFC-e de troca, e o relatório mensal).

**O modelo que a tese desenha no § 4.4:**

| Linha | Valor (ilustrativo) |
| --- | --- |
| Recebimentos cartão/PIX do mês (DIMP) | 700.000,00 |
| (−) NFC-e/NF-e do mês pagas com cartão/PIX | (520.000,00) |
| (−) Recebimentos de títulos — crediário (notas de meses anteriores) | (150.000,00) |
| (−) Vendas de vale compras do mês (→ passivo) | (30.000,00) |
| Diferença não explicada | 0,00 |

Guarde essa tabela: o que está implementado **não** tem a linha do crediário. Ver § 6 abaixo.

---

## 2. O que existe hoje no repositório

Implementado na noite de 24→25/09/2026, como milestone 7 do plano `.claude/plano-vale-compras.md`
(TASK-38). **Está na árvore de trabalho, não commitado.**

| Arquivo | Papel |
| --- | --- |
| `api/app/Mg/Dimp/DimpConciliacaoService.php` (462 linhas) | Toda a apuração |
| `api/app/Mg/Dimp/DimpConciliacaoController.php` | Um método, `relatorio()` |
| `api/resources/views/dimp/conciliacao.blade.php` (170 linhas) | Layout do PDF |
| `api/routes/api.php:516` | `Route::get('dimp/conciliacao', …)` dentro de `auth:api` + `v1` |

Rota: `GET /v1/dimp/conciliacao?ano=2026&mes=7&codfilial=103`
`codfilial` é opcional (sem ele, todas as filiais). `?html=1` devolve o HTML cru, para ajustar
layout sem re-renderizar PDF a cada tentativa — mesmo contrato do relatório de romaneios
(`Mg\Grao\CargaRelatorioService`) e do modelo de vale (`Mg\Vale\ValeModeloRelatorioService`).
Permissão: `Autorizador::autoriza(['Administrador', 'Gerente'])`.

PDF por **mPDF**, A4 retrato (é um demonstrativo de uma coluna de valores). Não usa Dompdf — a
convenção do projeto é mPDF para relatório.

**Não existe nenhum gerador do arquivo DIMP em si.** O que existe é só o relatório de conciliação.
Se o outro trabalho for gerar o arquivo/obrigação acessória, é do zero.

### Como rodar sem subir tela

Não há ponto de entrada no app ainda. Para exercitar:

```bash
# PDF / HTML pela rota, autenticado
curl -H "Authorization: Bearer $TOKEN" \
  'https://…/v1/dimp/conciliacao?ano=2026&mes=7&html=1'

# direto no container, sem HTTP
docker exec mgspa-api php -r '…'   # ou um script em storage/app/
```

A bancada que usei (fora do repositório, ainda no disco):
`api/storage/app/vale-teste/m7.php` (apura 3 meses e imprime tudo),
`api/storage/app/vale-teste/m7pdf.php` (gera o PDF),
`api/storage/app/vale-teste/m7set.php` (mês com vale).
Todos fazem `require __DIR__ . '/boot.php'`, que sobe o Laravel fora do artisan —
`php artisan tinker arquivo.php` **não executa** o arquivo nesta versão, só ecoa o fonte.

---

## 3. Estrutura do relatório implementado

Duas partes. A primeira é **narrativa**, a segunda são **conferências que têm que dar zero**.

**Seção 1 — Como o cliente pagou os negócios do mês.** `tblnegocioformapagamento` dos negócios
fechados no mês, agrupado por `tipo` (tPag), líquido de troco. Destaque na soma de cartão + PIX,
que é o que a adquirente informa.

**Seção 2 — O que as notas emitidas no mês declararam.** `tblnotafiscalpagamento` das notas ativas
emitidas no mês, mesmo agrupamento. Destaque no cartão + PIX.

**Seção 3 — A divergência que o fisco vai ver.** Seção 1 menos seção 2, e quanto dela é vale compras
vendido.

**Seção 4 — Vale compras vendido no mês.** Dentro do negócio (`tblnegociovale`) e no sistema antigo
(`tblvalecompra`), mais os negócios do mês sem nota nenhuma.

**Seção 5 — Conferências.** Três, e **nenhuma é arredondamento**:

| # | Método | O que pega |
| --- | --- | --- |
| 1 | `confNotaPagamento()` | Notas do mês em que `Σ vPag − vTroco ≠ vNF`. É a conta que a própria SEFAZ faz — nota que apareça aqui seria **rejeitada na transmissão**. |
| 2 | `confValeSemCredito()` | Vale vendido em negócio fechado sem título de crédito emitido. Dinheiro recebido sem vale do outro lado. |
| 3 | `confNegocioPagamento()` | Venda do PDV em que os pagamentos não cobrem o total cobrado. É a conferência que o próprio fechamento faz; se aparecer, o caixa do dia não fecha. |

A conferência 1 é a que amarra o DIMP ao rateio fiscal do vale: é exatamente o invariante que o
rateio do `detPag` (milestone 6) precisa manter de pé.

---

## 4. A decisão de projeto mais importante, e a evidência dela

**A ideia óbvia não funciona:** "soma dos negócios fechados no mês menos a soma das notas do mês tem
que dar zero" é incomputável neste banco.

Motivo: **o mesmo item de negócio pode estar em duas notas ativas ao mesmo tempo** — o cupom NFC-e
do balcão e a NFe 55 do faturamento do crediário (`Mg\Titulo\TituloAgrupamentoService:349` gera a 55
a partir de negócios que já têm cupom, com `$ignorarJaNotados`). Somar as notas conta o mesmo item
duas vezes.

Medido em julho/2026:

```
itens de negócios do mês em mais de uma nota ativa: 7.622
combinações de modelo encontradas: "55" | "55,65" | "65"
soma das notas (nota inteira):        R$ 5.214.511,39
soma dos negócios do mês:             R$ 4.897.139,02
```

A soma das notas ficava R$ 300 mil **acima** da soma dos negócios. Somar item a item em vez de nota
inteira não resolve: dá R$ 5.178.073,85 com 81.454 dos 93.641 itens, porque a duplicidade é por item.

Por isso a conferência do documento fiscal é feita **nota a nota** (`Σ vPag − vTroco = vNF`), que é
verdade individualmente e não depende de deduplicar nada.

**Outra decisão:** só operação de **saída** entra (`codoperacao = 2`, em todas as consultas de
negócio, e `nf.codoperacao = 2` nas de nota). Compra é dinheiro saindo, não é DIMP. Sem esse filtro
a conferência 3 acusava 262 negócios de "Compra" com R$ 1,69 milhão de diferença — compras criadas
fora do PDV simplesmente não têm linha em `tblnegocioformapagamento`. A conferência 3 ainda restringe
a `codpdv is not null`, porque a validação de fechamento que ela espelha só vale para venda do PDV.

---

## 5. Fatos do modelo de dados que você vai precisar

**Códigos tPag** (coluna `tipo` em `tblnegocioformapagamento` e `tblnotafiscalpagamento`):
`01` dinheiro · `02` cheque · `03` crédito · `04` débito · `05` crédito loja (crediário) ·
`12` vale presente (é como o vale compras entra na nota) · `15` boleto · `16` depósito ·
`17` PIX · `90` sem pagamento · `99` outros.
Eletrônicos para DIMP = `[3, 4, 17]` (`DimpConciliacaoService::TPAG_ELETRONICOS`).

**Duas estruturas de vale coexistem**, e as consultas leem as duas em `union`:
- `tblnegociovale` — o vale dentro do negócio, novo (TASK-38). Tem `codpessoafavorecido`, `aluno`,
  `turma`, `valorvale` (a **face**, que é o crédito), `valortotal` (a fatia paga após rateio) e
  `codtitulo`.
- `tblvalecompra` — o vale do MGLara, que vendia fora do negócio. Some quando o milestone 9 converter
  o legado. O código já trata a ausência (`tabelaExiste()`), então o relatório continua rodando
  depois da conversão sem mudança.

**O saldo do vale mora em `tbltitulo`**, tipo 3 (`TituloService::TIPO_VALE`), conta contábil 83. O
crédito é **saldo negativo**; as consultas fazem `-t.saldo` para trabalhar com positivo.

**`tblnotafiscal.valortotal` é mantido por trigger do banco**, não pelo PHP:
`fntblnotafiscalprodutobarraaiauad` → `fnTblNotaFiscal_Atualiza_ValorProdutos` →
`fnTblNotaFiscal_Atualiza_ValorTotal`. A fórmula é
`valorprodutos + icmsst + ipi + ipidevolucao + frete + seguro − desconto + outras`, onde
`valorprodutos = Σ item.valortotal`. Consequência prática: depois de gravar os itens, o objeto
`$nota` em memória está desatualizado — precisa de `refresh()`.

**As triggers equivalentes do negócio** (`fnTblNegocio_Atualiza_ValorTotal` /
`_ValorProdutos`) só rodam **quando `codpdv is null`**. Negócio de PDV mantém os valores que o
frontend mandou. E a fórmula delas **não conhece `valorvales`** — isso é uma pendência do milestone 9
do vale, não do DIMP, mas morde qualquer coisa que mexa em negócio sem `codpdv`.

**Nota fiscal ativa** = `nfecancelamento is null and nfeinutilizacao is null`. O helper canônico é
`Mg\NotaFiscal\NotaFiscalStatusService::isAtiva()`, mas nas consultas SQL isso está inline.

**Banco de dev:** `docker exec mgdb-mgdb-1 psql -U mgsis -d mgsis -c "…"`.

---

## 6. O que foi medido em dados reais (dev)

**Julho/2026** (`ano=2026&mes=7`, todas as filiais):

```
negócios de saída fechados:            28.524   R$ 3.133.134,21
cartão + PIX recebido nos negócios:              R$   872.837,45
cartão + PIX declarado nas notas:                R$   871.105,19
divergência:                                     R$     1.732,26
  explicada por vale (3 vales do MGLara):        R$       473,23
negócios do mês sem nota nenhuma:       4.213   R$   329.166,34

conferência 1 (Σ vPag ≠ vNF):           0 notas    R$      0,00   OK
conferência 2 (vale sem crédito):       0 vales    R$      0,00   OK
conferência 3 (pagamentos < total):     2 negócios R$    233,06   FALHOU
tempo: ~46s
```

**Junho/2026:** divergência −1.560,54; conferência 1 = 1 nota / R$ 0,01; conferência 3 = 2 negócios /
R$ 61,43. ~29s.

**Setembro/2026** (o mês dos negócios de teste com vale dentro do negócio): **as três conferências
deram zero**, e a seção do vale se preencheu — 8 vales, cobrado R$ 996,68, face R$ 986,68,
cartão/PIX R$ 326,68. (Cobrado > face porque um dos casos tinha juros: a fatia de juros do vale entra
no "cobrado" e não na "face".)

### Achados reais em dados de produção

Não são defeitos deste trabalho — são dados anteriores, encontrados pela conferência 3. Relatados,
**sem abrir task** (regra do CLAUDE.md):

| Negócio | Total cobrado | Pagamentos lançados | Observação |
| --- | --- | --- | --- |
| 4485692 | 61,42 | 122,84 | exatamente o dobro |
| 4513488 | 85,00 | 149,00 | — |
| 4531948 | 169,06 | 338,12 | exatamente o dobro |
| 4501184 | 50,01 | 50,00 | 1 centavo |

E uma nota: `codnotafiscal 3485335`, modelo 65, número 1262041, autorizada em 22/06/2026, com
`vNF 50,01` contra `Σ vPag 50,00` — o mesmo negócio 4501184.

---

## 7. O que ficou em aberto

1. **Não existe ponto de entrada no app.** Hoje é só a rota da API. Falta decidir em qual app mora a
   tela (negócios? contas?), qual menu, e se o filtro por filial aparece. O padrão do projeto para
   abrir PDF é o helper `@components/abrirPdf` — modal `MgRelatorioPdfDialog` no desktop, nova aba no
   mobile.

2. **Falta a linha do crediário que a tese pede.** O § 4.4 desconta "recebimentos de títulos —
   crediário (notas de meses anteriores)" do total recebido. O relatório implementado **não** tem essa
   linha: ele mostra a divergência e explica a fatia do vale, mas joga o resto numa linha "venda a
   prazo, crediário e nota emitida em mês diferente do negócio" sem abrir. Para o relatório fechar
   exatamente como a tese desenha, falta apurar as liquidações de título do mês por meio de pagamento
   (`tblliquidacaotitulo` / `tblmovimentotitulo`) e separar o que veio de cartão/PIX.

3. **Desempenho: 30 a 50 segundos por mês.** As consultas pesadas são `negociosSemNota()` e as
   conferências, que varrem `tblnegocioprodutobarra` × `tblnotafiscalprodutobarra` do mês. Aceitável
   para um relatório mensal rodado uma vez, ruim para uma tela interativa. Se virar tela, vale medir
   de novo e talvez materializar.

4. **Nada foi validado na tela nem com o contador.** O PDF foi gerado e conferido página a página em
   imagem, e os números batem com consultas diretas ao banco. Mas ninguém do fiscal olhou ainda.

5. **A tese pede o relatório "arquivado" mensalmente** (§ 5). Não há rotina de arquivamento — hoje o
   PDF é gerado sob demanda e não fica guardado em lugar nenhum.

6. **`tblvalecompra` ainda é lida.** Quando o milestone 9 do vale converter o legado e dropar a
   tabela, a seção 4 do relatório perde a linha do sistema antigo sozinha (`tabelaExiste()` já trata),
   mas vale conferir o primeiro mês depois da conversão: os vales convertidos passam a aparecer como
   `tblnegociovale` em negócios retroativos, e isso muda a contagem dos meses históricos.

---

## 8. Ligação com o resto do trabalho do vale compras

O DIMP não é um assunto isolado — ele existe porque o **milestone 6** (rateio do `detPag`) cria a
divergência de propósito. Quem for mexer em DIMP precisa saber:

- Em venda mista, a NFC-e leva só a fração da mercadoria. O `cAut` do cartão é preservado inteiro no
  `detPag`, **mas o `vPag` é menor que o valor que a adquirente informou**. É exatamente esse par
  (`cAut` real, `vPag` reduzido) que o cruzamento DIMP enxerga como divergência.
- O vale consome primeiro o **dinheiro**, depois o **PIX**, depois o resto — justamente para deixar
  cartão e PIX intactos sempre que dá, e só encostar neles quando o dinheiro não cobre o vale. Isso
  reduz a divergência DIMP na origem.
- O consumo de vários vales (milestone 8) vira **um único `detPag tPag=12`** na nota, embora sejam N
  pagamentos no banco.
- O relatório de conciliação é a peça que explica tudo isso. A tese trata ele como elemento de prova,
  não como conveniência.

O plano completo, com as 22 decisões tomadas e o relato dos milestones 4 a 8, está em
`.claude/plano-vale-compras.md`. A task é a **TASK-38** (`./backlog.sh task view TASK-38 --plain`).

---

## 9. Convenções do projeto que valem para qualquer mexida aqui

- **Nunca commitar sem OK explícito.** Deixar na árvore, dizer como testar, esperar validação.
- **Nunca criar task por iniciativa própria.** Achou bug? Conta no chat e espera.
- Regra de negócio em Service, controller só valida e chama. Services estáticos, sem DI.
- SQL cru (`DB::select`) é preferido ao Query Builder na maioria das leituras.
- Relatório = mPDF + rota com `?html=1` + blade em `resources/views/<dominio>/`.
- DDL em produção é do Fábio e roda no go-live — **não relatar como pendência**.
