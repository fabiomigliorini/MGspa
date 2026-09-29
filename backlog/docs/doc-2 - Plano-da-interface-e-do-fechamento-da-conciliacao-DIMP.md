---
id: doc-2
title: Plano da interface e do fechamento da conciliacao DIMP
type: specification
created_date: '2026-09-28 23:36'
---

# Plano da interface e do fechamento da conciliação DIMP

**Tarefa:** TASK-185 — Não há como abrir a conciliação DIMP do mês
**Escrito em:** 28/09/2026 · **Leia antes de começar a executar a task.**

Documentos irmãos:

| Onde | O que tem |
| --- | --- |
| `backlog/docs/doc-1` | A tese fiscal. A § 4.4 é o modelo de relatório que este plano tenta alcançar; a § 5 é a trilha de auditoria exigida. |
| `.claude/contexto-dimp.md` | Como o backend chegou ao estado atual: estrutura das 5 seções do PDF, as 3 conferências, decisões de projeto com a evidência medida, fatos do modelo de dados (tPag, saldo de vale, triggers). |
| `.claude/plano-vale-compras.md` | O plano da TASK-38 inteira, onde o DIMP era o milestone 7. |

---

## 1 · Por que esta task existe, e por que ela não é só "fazer a tela"

O relatório de conciliação DIMP **existe e funciona**. Saiu como milestone 7 da TASK-38
(commit `d1c3cf251`) e lá o critério de aceite foi marcado como concluído.

O que ficou de fora:

1. **Não tem ponto de entrada em nenhum app.** É só a rota
   `GET /api/v1/dimp/conciliacao?ano=&mes=&codfilial=`, dentro de `auth:api`. Colada na barra
   de endereços do navegador dá **401** — não é `auth_or_signed` nem `auth_or_cookie` como os
   PDFs que abrem em aba. Na prática, hoje **ninguém consegue rodar o relatório.**
2. **A conta não fecha em zero** como a tese § 4.4 prescreve, e não é uma linha faltando —
   é a seção 3 deste documento. **Fazer só a tela deixa o trabalho furado**, porque sobe uma
   tela que mostra um número que ninguém defende linha a linha.

A conversa de 25 a 28/09/2026 levantou 6 decisões. Quatro foram respondidas, uma ficou aberta
(a do escopo) e é a razão de a task não ter sido executada na hora.

---

## 2 · Decisões já tomadas (não reabrir sem motivo)

| # | Decisão | Valor |
| --- | --- | --- |
| 1 | **App onde a tela mora** | **contas** |
| 2 | **Grupo de menu** | **Movimento** (junto de Pix Recebidos, Liquidações, Saldos) |
| 3 | **Permissão** | `[ADMINISTRADOR, FINANCEIRO, CONTADOR]`, com o backend alinhado |
| 4 | **Filtro de filial na tela** | **Sim**, `MgSelectFilial`, default "Todas" |
| 5 | **Formato** | **Painel na tela + PDF** |
| 6 | **Escopo da apuração** | **EM ABERTO** — ver seção 3 |

### Sobre a decisão 1 (app = contas)

Foi escolhido contra a alternativa `notas`. O argumento que ganhou: é dinheiro entrando
(cartão, PIX, liquidação de título), e se a linha do crediário for implementada ela sai de
`tblliquidacaotitulo`, que é domínio do contas. O app já tem Extrato e Saldos.

A alternativa `notas` tinha a favor ser o app das obrigações acessórias (Exportação Domínio,
Inutilizações, DFe) e o relatório ser peça de prova fiscal. Perdeu, mas o argumento continua
válido se um dia o assunto virar "gerar o arquivo DIMP" em vez de "conciliar".

### Sobre a decisão 3 (permissão)

O backend hoje é `Autorizador::autoriza(['Administrador', 'Gerente'])`
(`DimpConciliacaoController.php:18`). O app contas usa `[ADMINISTRADOR, FINANCEIRO]` em
praticamente toda rota, e tem `CONTADOR` disponível em `contas/src/constants/permissoes.js`.

Decidido: **`Administrador` + `Financeiro` + `Contador`**, e **mudar o backend para bater**.
Isso **tira o `Gerente`** — gerente de loja não responde malha fiscal. Se alguém reclamar de
gerente sem acesso, é uma palavra no `GRUPOS` do controller e na rota.

### Sobre a decisão 4 (filial)

Confirmado no banco: **cada filial tem CNPJ próprio** (`tblfilial` → `tblpessoa.cnpj`), então
filial é o eixo certo — a DIMP chega por CNPJ.

**Pegadinha:** a filial `199` ("Defeito") compartilha o CNPJ da `101` (Depósito) —
`04576775000160` nas duas. "Por CNPJ" de verdade seria as duas somadas. Hoje o filtro é por
`codfilial` e ninguém decidiu se isso importa. Se o relatório for virar anexo de resposta ao
fisco, provavelmente importa.

### Sobre a decisão 5 (painel + PDF)

Duas consequências práticas:

- **Precisa de um endpoint JSON novo.** `DimpConciliacaoService::apurar()` já é público e
  devolve array pronto (via `compact()`), então é barato: um método no controller e uma rota.
- **O painel NÃO pode carregar sozinho ao abrir a tela.** A apuração leva **30 a 50 segundos**
  por mês (medido: julho ~46s, junho ~29s). A tela tem que ser filtro + botão **Apurar**
  explícito, com estado de carregando; o botão de imprimir só acende depois de apurar.
  As consultas pesadas são `negociosSemNota()` e as três conferências, que varrem
  `tblnegocioprodutobarra` × `tblnotafiscalprodutobarra` do mês. **Medir de novo antes de
  desenhar a tela** — pode ter piorado ou melhorado com a conversão do legado.

O padrão do projeto para o PDF é `@components/abrirPdf` — modal `MgRelatorioPdfDialog` no
desktop, nova aba no mobile.

---

## 3 · O furo: por que a conta não fecha, e por que não é uma linha faltando

Esta é a seção que motivou não executar a task na hora.

### 3.1 O que a tese pede

A tese § 4.4 prescreve um demonstrativo que **fecha em zero**:

```
    Recebimentos cartão/PIX do mês (DIMP)          700.000
(−) Notas do mês pagas com cartão/PIX             (520.000)
(−) Recebimentos de títulos — crediário           (150.000)
(−) Vendas de vale compras do mês                  (30.000)
    Diferença não explicada                              0
```

O relatório implementado tem a linha 1, a 2 e a 4. **Não tem a 3.**

### 3.2 O crediário corta a conta nos DOIS lados, em direções opostas

Este é o achado que transforma "uma linha faltando" em problema de modelagem.

Quando o cliente paga um título de crediário com cartão, **a adquirente vê aquilo e informa na
DIMP** — é uma transação de cartão no CNPJ, como qualquer outra. Mas aquele pagamento **não tem
negócio nenhum**: é uma `tblliquidacaotitulo` contra uma nota de meses atrás. Então ele:

- **está** na DIMP de verdade e **não está** na linha 1 do relatório — porque a linha 1 sai de
  `tblnegocioformapagamento`, só dos negócios fechados no mês;
- **deveria** estar subtraído como explicação na linha 3, e não está.

Consequência: **a linha 1 do relatório é um proxy da DIMP, não a DIMP.** E o PDF afirma
literalmente *"Cartão + PIX — é isto que a adquirente informa na DIMP"*
(`conciliacao.blade.php:64`), o que não é exato. **Corrigir esse texto faz parte do trabalho**,
em qualquer opção de escopo escolhida.

Verificado no código: `DimpConciliacaoService` **nunca toca** em `tblliquidacaotitulo` nem em
`tblmovimentotitulo` — zero ocorrências.

### 3.3 O guarda-chuva, e o terceiro problema escondido nele

Tudo que não é vale cai numa linha única
(`conciliacao.blade.php:113`):

> *Resto — venda a prazo, crediário e nota emitida em mês diferente do negócio*

Em **julho/2026** esse resto foi **R$ 1.259,03** (R$ 1.732,26 de divergência − R$ 473,23
explicados por vale). Pouco dinheiro, mas é um número que ninguém consegue defender linha a
linha — que é exatamente para o que o relatório existe.

E o próprio rótulo confessa um terceiro problema: **nota emitida em mês diferente do negócio.**
Negócio fechado em 31/01 cujo cupom saiu em 01/02 entra como cartão em janeiro e como nota em
fevereiro. Não é crediário, não é vale, e nenhuma linha explica. Resolver isso exige amarrar
nota↔negócio em vez de comparar dois totais mensais.

### 3.4 A ressalva que limita todas as opções

A linha 1 da tese diz "(DIMP)" — o número da **adquirente**. Em todas as opções abaixo nós
reconstruímos esse número a partir dos nossos próprios dados, e **conciliar nosso dado contra
nosso dado prova menos** do que conciliar contra o extrato da adquirente.

A versão forte seria importar o extrato/arquivo real. **Não existe no projeto nenhum gerador nem
leitor de DIMP** — só o relatório de conciliação. Isso é assunto de outro tamanho, e
provavelmente de outra task.

### 3.5 As três opções de escopo

| | O que faz | Custo |
| --- | --- | --- |
| **A** | Deixa a apuração como está. A tela sobe sobre o relatório atual: divergência + fatia do vale + o resto no guarda-chuva. | zero além da tela |
| **B** | Apura as liquidações do mês por forma de pagamento, separa cartão/PIX, **soma na linha 1** (virando o total real da adquirente) e **subtrai na linha 3**. O resto encolhe para o descasamento de mês. | 2 consultas + blade + painel |
| **C** | B + amarra nota↔negócio em vez de comparar dois totais mensais, e aí fecha em zero de verdade. | mexe nas consultas mais pesadas, e já há problema de 30–50s |

**Recomendação registrada na conversa: B, e depois da tela.** Motivo: a tela é o que destrava o
relatório para qualquer pessoa usar, é barata, e o B mexe só em números do backend — a tela não
muda de forma (mesmos filtros, mesmos KPIs, uma linha a mais no painel). Então: tela primeiro,
valida, B como passo separado. C só se o contador olhar e disser que o resto incomoda.

**A decisão final de escopo não foi tomada.** Quem executar decide, ou pergunta.

---

## 4 · Onde mexer

### Backend (`api/`)

| Arquivo | O que fazer |
| --- | --- |
| `app/Mg/Dimp/DimpConciliacaoController.php` | Método novo para o JSON do painel (o `apurar()` já devolve tudo). Alinhar `GRUPOS` com a permissão da decisão 3. |
| `app/Mg/Dimp/DimpConciliacaoService.php` | Só na opção B/C: as consultas de liquidação. `apurar()` já é o ponto de entrada único. |
| `resources/views/dimp/conciliacao.blade.php` | Corrigir o texto da linha 64 (proxy ≠ DIMP). Linha 113 é o guarda-chuva. Na opção B, a linha nova. |
| `routes/api.php:518` | A rota atual. A do JSON entra ao lado. |

### Frontend (`contas/`)

| Arquivo | O que fazer |
| --- | --- |
| `src/pages/` | Página nova. O análogo mais próximo do projeto inteiro é `estoque/src/pages/relatorios/Index.vue` — filtros em `q-card bordered flat` + botão que chama `abrirPdf`. |
| `src/router/routes.js` | Rota com `permissions: [PERMISSOES.ADMINISTRADOR, PERMISSOES.FINANCEIRO, PERMISSOES.CONTADOR]`. |
| `src/layouts/MainLayout.vue` | Entrada no `menuGroups`, grupo **Movimento**. |

Padrões da casa que se aplicam: `q-page` com `max-width: 1086px; margin: auto` + `q-pa-md`;
campos por `@components/MgInput*` e `MgSelectFilial`, nunca Quasar cru; formatação por
`@components/formatters`; nunca `dense`; nunca `<style scoped>`; ícone `print` para relatório.

---

## 5 · O que mudou desde que o backend foi escrito (conferir ao executar)

O **milestone 9 da TASK-38 rodou** em 26/09/2026 (commit `d1fcca42d`): os 3.718 vales antigos
foram convertidos em negócio + `tblnegociovale`, e o legado foi removido.

Confirmado no banco de dev em 28/09/2026: **`tblvalecompra` não existe mais**
(`to_regclass('tblvalecompra')` → nulo).

O relatório continua rodando — o `tabelaExiste()` já tratava a ausência —, mas:

- a **seção 4 do PDF perdeu a linha do sistema antigo**;
- os vales convertidos **agora aparecem como `tblnegociovale` em negócios retroativos**, o que
  **muda a contagem dos meses históricos**. Os números medidos na seção 6 do
  `.claude/contexto-dimp.md` (julho, junho, setembro/2026) foram apurados **antes** da conversão
  e podem não se reproduzir.

**Isso não foi conferido.** É o critério de aceite #7 da task.

---

## 6 · Coisas que a apuração já achou e que não são desta task

Achados em dados de produção anteriores a todo este trabalho, encontrados pela **conferência 3**
(venda do PDV em que os pagamentos não cobrem o total). Relatados no chat na época, **sem abrir
task**, conforme a regra 1 do `CLAUDE.md`. Registrados aqui para não se perderem:

| Negócio | Total cobrado | Pagamentos lançados | Observação |
| --- | --- | --- | --- |
| 4485692 | 61,42 | 122,84 | exatamente o dobro |
| 4513488 | 85,00 | 149,00 | — |
| 4531948 | 169,06 | 338,12 | exatamente o dobro |
| 4501184 | 50,01 | 50,00 | 1 centavo |

E uma nota: `codnotafiscal 3485335`, modelo 65, número 1262041, autorizada em 22/06/2026, com
`vNF 50,01` contra `Σ vPag 50,00` — o mesmo negócio 4501184.

Se a tela subir e essas linhas aparecerem no painel, **não são defeito da tela nem da task** —
são esses dados. Decidir o que fazer com eles é outra conversa.

Na mesma linha, duas pendências da tese que **não** estão nesta task e ninguém decidiu:

- **§ 5 pede o relatório arquivado mensalmente.** Hoje o PDF é gerado sob demanda e não fica
  guardado em lugar nenhum. Não há rotina de arquivamento.
- **Ninguém do fiscal olhou o relatório ainda.** O PDF foi conferido página a página contra
  consultas diretas ao banco, mas não passou pelo contador.

---

## 7 · Como exercitar sem tela

```bash
# pela rota, autenticado (PDF, ou ?html=1 para o HTML cru)
curl -H "Authorization: Bearer $TOKEN" \
  'https://api-dev.mgpapelaria.com.br/api/v1/dimp/conciliacao?ano=2026&mes=7&html=1'
```

Atenção ao caminho: é `/api/v1/...`, não `/v1/...` — o `API_URL` dos apps já inclui o `/api/`.

A bancada usada no desenvolvimento está fora do repositório, em
`api/storage/app/vale-teste/`: `m7.php` (apura 3 meses e imprime tudo), `m7pdf.php` (gera o PDF),
`m7set.php` (mês com vale). Todos fazem `require __DIR__ . '/boot.php'`, que sobe o Laravel fora
do artisan — `php artisan tinker arquivo.php` **não executa** o arquivo nesta versão, só ecoa o
fonte.

SQL no banco de dev: `docker exec mgdb-mgdb-1 psql -U mgsis -d mgsis -c "…"`.
