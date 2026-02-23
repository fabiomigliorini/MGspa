# Blueprint — Módulo de Gestão de Metas & Variáveis

## MG Papelaria — ERP

**Stack:** Laravel + Vue.js + Quasar + PostgreSQL
**Versão:** MVP
**Data:** 20/02/2026 (atualizado)

---

## 1. Glossário de Entidades

| Entidade               | Descrição                                                                                                                                                                    |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Período**            | Ciclo de apuração (ex: 26/01 a 25/02). Criado automaticamente pela primeira venda ou manualmente pelo RH.                                                                    |
| **Unidade de Negócio** | Agrupamento operacional desacoplado da estrutura societária (Loja Centro, Sinopel, Administrativo, etc.)                                                                     |
| **Setor**              | Subdivisão da Unidade de Negócio (Vendas, Xerox, Caixa, Venda Remota, etc.)                                                                                                  |
| **Tipo de Setor**      | Classificação do setor (Vendas, Xerox, Caixa, etc.). Cadastro livre. Usado pra rotear itens de produto.                                                                      |
| **Indicador**          | Acumulador de valores num período. Pode ser: Unidade, Setor coletivo, Vendedor individual ou Caixa individual.                                                               |
| **Meta**               | Valor alvo de um indicador (ex: R$320.000 de vendas).                                                                                                                        |
| **Rubrica**            | Regra de remuneração atrelada ao colaborador no período. Define cálculo (% ou fixo), condições (meta/ranking) e comportamento (absenteísmo). Tabela `tblcolaboradorrubrica`. |
| **Bonificação**        | Rubrica avulsa/pontual (não recorrente). Mesma tabela, flag `recorrente = false`.                                                                                            |
| **Encerrar**           | Travar o colaborador no período, gerar título de pagamento no financeiro.                                                                                                    |

---

## 2. Decisões de Arquitetura

### 2.1 Duas Estruturas Paralelas

**Societária (fiscal):** Empresa → Filial (CNPJ). Já existe no ERP. Define encargos e folha.

**Organizacional (operacional):** Unidade de Negócio → Setor. Desacoplada da estrutura fiscal. Define metas, KPIs e rubricas.

O colaborador tem vínculo com ambas:

- `tblcolaborador.codfilial` → estrutura societária
- `tblperiodocolaboradorsetor.codsetor` → estrutura organizacional (por período)

### 2.2 Cargo vs Setor

O cargo (`tblcargo` / `tblcolaboradorcargo`) é o que está na carteira CLT. O Setor define o que o colaborador está fazendo naquele período. Vendedor registrado como "Vendedor" pode estar atuando no Setor Xerox cobrindo férias.

### 2.3 codcolaborador (não codpessoa)

Todas as tabelas novas referenciam `codcolaborador`. Resolução: `tblnegocio.codpessoavendedor` → busca `tblcolaborador` onde `codpessoa = X` e `rescisao IS NULL`. Se houver mais de um vínculo ativo, pega o de menor `codcolaborador` (mais antigo).

### 2.4 Roteamento de Vendas

Dois níveis de roteamento determinam pra qual indicador cada item de venda vai:

**Nível 1 — PDV → Setor:** todo PDV aponta pra um setor (obrigatório). Define o destino padrão.

**Nível 2 — Produto → Tipo de Setor:** o produto pode ter um `codtiposetor`. Se preenchido, sobrescreve o setor do PDV, buscando o setor daquele tipo dentro da unidade de negócio do PDV.

**Regra de resolução por item:**

1. Produto tem `codtiposetor`? → busca setor daquele tipo na unidade do PDV → usa esse setor
2. Não tem? → usa o setor do PDV

**Toda venda também acumula no indicador da Unidade de Negócio** (sempre).

### 2.5 Flags de Acumulação do Setor

Cada setor tem flags independentes que definem quais indicadores individuais gera:

- `indicadorvendedor` — gera indicador individual por vendedor (via `codpessoavendedor`)
- `indicadorcaixa` — gera indicador individual por caixa (via `codusuario` → `codpessoa` → `codcolaborador`)
- `indicadorcoletivo` — gera indicador coletivo do setor (rateio entre o time)

O indicador da Unidade de Negócio **sempre** acumula, independente das flags.

**Configuração por setor:**

| Setor   | Vendedor | Caixa | Coletivo |
| ------- | -------- | ----- | -------- |
| Vendas  | ✓        | ✓     | ✗        |
| Xerox   | ✗        | ✗     | ✓        |
| Caixa   | ✗        | ✓     | ✗        |
| Sinopel | ✗        | ✗     | ✓        |

### 2.6 Base de Comissão

- Valor dos produtos **líquido de desconto**
- **Não inclui** frete, outras despesas e seguro
- Venda parcelada gera comissão **integral no período da venda**
- Se o item é de xerox e foi roteado pro setor xerox, **não conta** na base de comissão do caixa

---

## 3. Regras de Negócio

### 3.1 Períodos

- **Criação automática:** primeira venda sem período aberto → cria novo período replicando intervalo do último
- **Criação manual:** RH pode criar pelo botão na drawer (datas customizadas)
- **Sem gap, sem sobreposição:** todo dia do calendário deve pertencer a um período
- **Múltiplos abertos:** pode ter mais de um período aberto simultaneamente (anterior em fechamento + atual recebendo vendas)
- **Status:** A=Aberto, F=Fechado
- **Reabrir:** período fechado pode ser reaberto pelo RH (volta pra status A)

**Cores na drawer:**

- 🟢 Verde — período atual (recebendo vendas)
- 🟡 Amarelo — anterior, ainda em fechamento
- 🔴 Vermelho — fechado

### 3.2 Criação de Novo Período — O que puxa do anterior

| Dado                       | Puxa? | Observação                            |
| -------------------------- | ----- | ------------------------------------- |
| Vínculos colaborador-setor | ✓     | Todos (se tinha 2, puxa 2 com alerta) |
| Percentual de rateio       | ✓     | Mantém do período anterior            |
| Dias trabalhados           | ✗     | RH lança                              |
| Rubricas recorrentes       | ✓     | Percentuais, valores fixos, condições |
| Metas dos indicadores      | ✓     | RH ajusta o que mudou                 |
| Bonificações avulsas       | ✗     | Não puxa (recorrente = false)         |
| Colaboradores rescindidos  | ✗     | Não vêm (rescisao preenchida)         |

### 3.3 Encerramento do Colaborador

1. RH revisa os lançamentos e valores calculados
2. Clica "Encerrar"
3. Sistema trava o colaborador (status E)
4. Gera registro em `tbltitulo`:
   - Valor positivo → título de crédito (pagar ao colaborador)
   - Valor negativo → título de débito (colaborador deve)
5. Encontro de contas é feito pelo financeiro (fora do módulo)

### 3.4 Estorno do Colaborador

1. RH clica "Estornar"
2. Estorna o título no financeiro
3. Reabre o colaborador (status A)
4. RH corrige o necessário
5. Encerra novamente → gera novo título

### 3.5 Fechamento do Período

- **Manual** — RH clica "Fechar período"
- Não precisa ter todos os colaboradores encerrados (mas deveria)
- Pode reabrir → volta pra status A

### 3.6 Vendas, Cancelamentos e Devoluções

**Venda (status 2 - Fechada):**

- Acumula valor positivo nos indicadores
- Data de referência: `tblnegocio.lancamento`

**Cancelamento (status 3):**

- Acumula valor negativo nos indicadores
- Data de referência: data do cancelamento

**Devolução:**

- Novo `tblnegocio` com natureza de operação de devolução
- Itens apontam pro original via `codnegocioprodutobarradevolucao`
- Acumula valor negativo nos indicadores
- Data de referência: data da devolução
- Roteamento segue a mesma lógica de produto/PDV

**Venda/estorno em período fechado:** cai no próximo período aberto.

### 3.7 Tipos de Rubrica

Toda rubrica é configurável como um "lego" com estas propriedades:

| Propriedade            | Valores                                           |
| ---------------------- | ------------------------------------------------- |
| Tipo de valor          | Percentual ou Fixo                                |
| Base de cálculo (se %) | Indicador individual, setor coletivo ou unidade   |
| Condição               | Sem condição, Meta atingida, Ranking (1º lugar)   |
| Concedido              | Sim / Não (toggle — RH desmarca quem não cumpriu) |
| Desconta absenteísmo   | Sim / Não                                         |
| Recorrente             | Sim (puxa pro próximo período) / Não (avulso)     |

**Exemplos de configuração:**

| Rubrica                        | Tipo | Base            | Condição | Concedido | Absenteísmo | Recorrente |
| ------------------------------ | ---- | --------------- | -------- | --------- | ----------- | ---------- |
| Comissão vendedor 0,6%         | %    | Indiv. vendedor | Sem      | ✓         | Não         | Sim        |
| Bônus meta vendedor 0,25%      | %    | Indiv. vendedor | Meta     | ✓         | Não         | Sim        |
| Prêmio 1º lugar vendedor R$200 | Fixo | —               | Ranking  | ✓         | Não         | Sim        |
| Comissão caixa                 | %    | Indiv. caixa    | Sem      | ✓         | Não         | Sim        |
| Bônus meta caixa               | %    | Indiv. caixa    | Meta     | ✓         | Não         | Sim        |
| Prêmio 1º caixa R$200          | Fixo | —               | Ranking  | ✓         | Não         | Sim        |
| Participação subgerente 0,1%   | %    | Unidade         | Sem      | ✓         | Não         | Sim        |
| Bônus meta subgerente R$200    | Fixo | —               | Meta     | ✓         | Não         | Sim        |
| Auxílio alimentação R$25/dia   | Fixo | —               | Sem      | ✓         | Sim         | Sim        |
| Gratificação fixa R$300        | Fixo | —               | Sem      | ✓         | Sim         | Sim        |
| Bonificação assiduidade R$200  | Fixo | —               | Sem      | toggle    | Não         | Sim        |
| Gratificação entregador R$500  | Fixo | —               | Sem      | ✓         | Sim         | Não        |

### 3.8 Rateio Coletivo (Xerox, Sinopel)

- Cada colaborador tem `percentualrateio` (soma do setor ≤ 100%)
- Cálculo: **média ponderada** por percentual × dias trabalhados
- Total **sempre distribui inteiro** entre os participantes

**Fórmula:**

```
pontos(colab) = percentualrateio × diastrabalhados
total_pontos = soma de todos os pontos do setor
valor(colab) = (pontos(colab) / total_pontos) × valor_total_setor
```

**Exemplo:**

```
Total comissão xerox: R$1.000 (6% de R$16.667 vendas)
Dias úteis: 22

Marcos:  40% × 22 dias = 8,80 pontos
Lucia:   30% × 20 dias = 6,00 pontos
Rafael:  30% × 22 dias = 6,60 pontos
Total:                    21,40 pontos

Marcos: 8,80/21,40 × R$1.000 = R$411,21
Lucia:  6,00/21,40 × R$1.000 = R$280,37
Rafael: 6,60/21,40 × R$1.000 = R$308,41
Total:                          R$999,99 (centavos ignorados)
```

### 3.9 Desconto de Absenteísmo

Para rubricas com `descontaabsenteismo = true`:

```
valor_final = valor × (soma_dias_trabalhados / dias_uteis_periodo)
```

`soma_dias_trabalhados` = soma dos dias de todos os vínculos do colaborador no período.
`dias_uteis_periodo` = campo `diasuteis` em `tblperiodo`.

### 3.10 Ranking

- 1º lugar = colaborador com maior `valoracumulado` no indicador do mesmo tipo dentro da unidade
- Empate: paga pra todos os empatados
- Ranking é calculado por último na ordem de processamento

### 3.11 Ordem de Cálculo

1. Acumula vendas nos indicadores
2. Calcula rubricas sem condição (comissão base, fixos, auxílios)
3. Verifica quem bateu meta
4. Calcula rubricas condicionais de meta (bônus %)
5. Calcula ranking
6. Calcula rubricas condicionais de ranking (prêmio 1º lugar)

Ordem fixa no sistema, não configurável.

### 3.12 Arredondamento

`numeric(14,2)` — arredondamento padrão do PostgreSQL. Diferenças de centavos são ignoradas.

---

## 4. Processamento de Vendas

### 4.1 Fluxo (Job por venda)

Venda fecha/cancela/devolve → dispara `ProcessarVendaIndicador` via queue do Laravel.

```
1. Identifica o período pela data do evento
   - Se não existe período aberto → cria automaticamente
   - Se período está fechado → usa o próximo aberto

2. Para cada item da venda:
   a. Produto tem codtiposetor?
      SIM → busca setor daquele tipo na unidade do PDV
      NÃO → usa o setor do PDV

   b. Calcula valor do item: valortotal (já líquido de desconto)
      - Ignora frete, seguro, outras despesas

   c. Acumula no indicador da UNIDADE DE NEGÓCIO (sempre)

   d. Conforme flags do setor destino:
      - indicadorvendedor E tem codpessoavendedor?
        → acumula no indicador individual do vendedor
        → (resolve codpessoavendedor → codcolaborador)

      - indicadorcaixa?
        → acumula no indicador individual do caixa
        → (resolve codusuario → codpessoa → codcolaborador)
        → SOMENTE se o item ficou no setor do PDV (xerox não conta)

      - indicadorcoletivo?
        → acumula no indicador coletivo do setor

   e. Cria registro em tblindicadorlancamento

3. Recalcula rubricas de todos os colaboradores afetados
```

### 4.2 Resolução de Colaborador

**Vendedor:**

```sql
SELECT col.codcolaborador
FROM tblcolaborador col
WHERE col.codpessoa = :codpessoavendedor
  AND col.rescisao IS NULL
ORDER BY col.codcolaborador ASC
LIMIT 1
```

**Caixa:**

```sql
SELECT col.codcolaborador
FROM tblusuario u
JOIN tblcolaborador col ON col.codpessoa = u.codpessoa AND col.rescisao IS NULL
WHERE u.codusuario = :codusuario
ORDER BY col.codcolaborador ASC
LIMIT 1
```

---

## 5. Visões de Custo

### 5.1 Composição do Custo

Para cada colaborador:

- **Folha bruta:** `tblcolaborador.salario` (valor atual)
- **Provisão de encargos:** folha × `tblempresa.fatorencargos` (da empresa onde está registrado)
- **Rubricas:** soma de `tblcolaboradorrubrica.valorcalculado` no período

### 5.2 Quatro Níveis de Visão

1. **Setor** — custo do setor vs faturamento do setor
2. **Unidade de Negócio** — soma dos setores vs faturamento da unidade
3. **Filial** — soma das unidades daquela filial
4. **Grupo consolidado** — tudo, incluindo unidades sem faturamento (administrativo, depósito)

Unidades sem faturamento não têm percentual individual, mas entram no consolidado.

---

## 6. Telas — Dashboard do RH

### 6.1 Layout Geral

```
┌──────────────────────────────────────────────────────────────────────┐
│  HEADER — Gestão de Metas & Variáveis — Painel do RH               │
├────────────┬─────────────────────────────────────────────────────────┤
│            │  BARRA DE ALERTAS                                      │
│  DRAWER    │  ⚠ 2 colaboradores sem setor · 3 metas pendentes      │
│  ESQUERDA  ├─────────────────────────────────────────────────────────┤
│            │  CARDS DE RESUMO                                       │
│  Períodos  │  [Total Var.] [Colaboradores] [Unidades] [Progresso]   │
│            ├─────────────────────────────────────────────────────────┤
│  🟢 26/02  │  VISÃO CONSOLIDADA DE CUSTOS                           │
│     a      │  ┌─────────────────────────────────────────────┐       │
│     25/03  │  │ Barras comparativas por unidade             │       │
│            │  │ Folha | Encargos | Variáveis | % Fat.       │       │
│  🟡 01/02  │  └─────────────────────────────────────────────┘       │
│     a      ├─────────────────────────────────────────────────────────┤
│     25/02  │  UNIDADES DE NEGÓCIO → SETORES → COLABORADORES         │
│            │                                                         │
│  🔴 01/01  │  ┌─ LOJA CENTRO ──────────────────────────────┐       │
│     a      │  │  Meta: R$320k  Vendas: R$287k  89,7%       │       │
│     30/01  │  │  [══════════════░░░] Total var: R$3.212     │       │
│            │  │                                              │       │
│  + Novo    │  │  ┌─ Setor Vendas ─────────────────────┐    │       │
│  período   │  │  │                                     │    │       │
│            │  │  │  [CARD COLABORADOR colapsado]        │    │       │
│            │  │  │  [CARD COLABORADOR expandido]        │    │       │
│            │  │  │  [CARD COLABORADOR colapsado]        │    │       │
│            │  │  │                                     │    │       │
│            │  │  └─────────────────────────────────────┘    │       │
│            │  │                                              │       │
│            │  │  ┌─ Setor Xerox ──────────────────────┐    │       │
│            │  │  │  [CARD COLABORADOR colapsado]        │    │       │
│            │  │  └─────────────────────────────────────┘    │       │
│            │  └──────────────────────────────────────────────┘       │
│            │                                                         │
│            │  ┌─ SINOPEL ───────────────────────────────────┐       │
│            │  │  ...                                         │       │
│            │  └──────────────────────────────────────────────┘       │
└────────────┴─────────────────────────────────────────────────────────┘
```

### 6.2 Drawer Esquerda — Períodos

```
┌───────────────────┐
│  PERÍODOS         │
│                   │
│  🟢 26/02 - 25/03 │  ← período atual (recebendo vendas)
│     R$4.250       │     resumo rápido de total variáveis
│     3/70 encerr.  │     progresso de fechamento
│                   │
│  🟡 01/02 - 25/02 │  ← em fechamento (selecionado, destaque)
│     R$7.010       │
│     62/70 encerr. │
│                   │
│  🔴 01/01 - 30/01 │  ← fechado
│     R$6.890       │
│     70/70 encerr. │
│                   │
│  🔴 01/12 - 30/12 │
│     R$7.320       │
│     70/70 encerr. │
│                   │
│  ┌───────────────┐│
│  │ + Novo período││
│  └───────────────┘│
└───────────────────┘
```

### 6.3 Barra de Alertas

```
┌──────────────────────────────────────────────────────────────────┐
│ ⚠ 2 colaboradores sem setor                                     │
│   → João da Silva (admitido 10/02) · Maria Santos (admitida 18/02)│
│ ⚠ 3 metas não definidas                                         │
│   → Loja Imperial · Xerox Imperial · Caixa Imperial              │
│ ⚠ 1 colaborador com múltiplos setores                            │
│   → Pedro Lima (Vendas Centro + Xerox Centro)                    │
└──────────────────────────────────────────────────────────────────┘
```

Clicável — leva direto ao colaborador ou à configuração pendente.

### 6.4 Cards de Resumo

```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ TOTAL VAR.   │ │ COLABORADORES│ │ UNIDADES     │ │ PROGRESSO    │
│              │ │              │ │              │ │              │
│  R$7.010,00  │ │     70       │ │      7       │ │   88,6%      │
│ Período atual│ │  62 encerr.  │ │ de negócio   │ │ fechamento   │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
```

### 6.5 Visão Consolidada de Custos

```
┌────────────────────────────────────────────────────────────────────┐
│  CUSTO POR UNIDADE                           Visão: [UN ▼] [Fil] │
│                                                                    │
│  Loja Centro       [████████░░] R$67.052    Fat: R$287k   23,4%   │
│                     Folha    Encargos  Var.                        │
│                                                                    │
│  Sinopel           [██████░░░░] R$42.848    Fat: R$195k   22,0%   │
│                                                                    │
│  Loja Botânico     [███████░░░] R$55.200    Fat: R$210k   26,3%   │
│                                                                    │
│  Loja Imperial     [██████░░░░] R$38.100    Fat: R$165k   23,1%   │
│                                                                    │
│  Loja André Maggi  [█████░░░░░] R$32.500    Fat: R$140k   23,2%   │
│                                                                    │
│  Administrativo    [████░░░░░░] R$59.300    Fat: —         —       │
│                                                                    │
│  Depósito          [███░░░░░░░] R$35.500    Fat: —         —       │
│  ──────────────────────────────────────────────────────────────    │
│  GRUPO CONSOLIDADO              R$330.500   Fat: R$997k   33,1%   │
│                                                                    │
│  Barra empilhada: [█Folha█|█Encargos█|█Variáveis█]               │
│  Legenda: ■ Folha  ■ Encargos  ■ Variáveis                       │
└────────────────────────────────────────────────────────────────────┘
```

Toggle pra alternar entre visão por Unidade de Negócio e por Filial.

### 6.6 Cabeçalho da Unidade de Negócio

```
┌────────────────────────────────────────────────────────────────────┐
│  ▼ LOJA CENTRO                              Filial Cuiabá Centro  │
│    Meta: R$320.000  ·  Vendas: R$287.000  ·  89,7%               │
│    [══════════════════════████░░░░░]                               │
│    Total variáveis: R$3.212,36  ·  1,12% das vendas               │
│    8/10 encerrados                                                 │
│                                                                    │
│    ┌─ Setor Vendas ────────────────────────────────────────┐      │
│    │  (indicadores abaixo)                                  │      │
│    └────────────────────────────────────────────────────────┘      │
│                                                                    │
│    ┌─ Setor Xerox ─────────────────────────────────────────┐      │
│    │  (indicadores abaixo)                                  │      │
│    └────────────────────────────────────────────────────────┘      │
│                                                                    │
│    ┌─ Setor Caixa ─────────────────────────────────────────┐      │
│    │  (indicadores abaixo)                                  │      │
│    └────────────────────────────────────────────────────────┘      │
└────────────────────────────────────────────────────────────────────┘
```

Clicável — colapsa/expande a unidade.

### 6.7 Card do Colaborador — Colapsado

```
┌────────────────────────────────────────────────────────────────────┐
│  [JV]  João Victor Santos         🟢 Aberto      Total variável   │
│        Vendedor · Setor Vendas                       R$808,00     │
│                                                                    │
│        Garantido ████████████░░░░ Potencial                        │
│        R$498,00                    R$310,00                    ▾   │
└────────────────────────────────────────────────────────────────────┘
```

```
┌────────────────────────────────────────────────────────────────────┐
│  [ML]  Maria Luiza Costa          🟢 Aberto      Total variável   │
│        Vendedora · Setor Vendas                      R$210,00     │
│        ⚠ 2 setores                                                │
│        Garantido ██████░░░░░░░░░░ Potencial                        │
│        R$210,00                    R$305,00                    ▾   │
└────────────────────────────────────────────────────────────────────┘
```

Barra horizontal empilhada: verde escuro = garantido, verde claro/hachurado = potencial.

### 6.8 Card do Colaborador — Expandido

```
┌────────────────────────────────────────────────────────────────────┐
│  [JV]  João Victor Santos         🔵 Encerrado   Total variável   │
│        Vendedor · Setor Vendas                       R$808,00     │
│                                                                    │
│  ┌─ INDICADORES & RUBRICAS ────────── [+ Bonificação] [↩ Estornar]│
│  │                                                                 │
│  │  Comissão Vendas Pessoal — 0,6% sobre vendas                   │
│  │  [████████████████████░░░] 114,3% da meta (R$48k / R$42k)      │
│  │  Atual: R$288,00                                                │
│  │                                                                 │
│  │  Bônus Meta — 0,25% sobre vendas                               │
│  │  ✅ Meta atingida                                               │
│  │  Valor: R$120,00                                                │
│  │                                                                 │
│  │  Bônus Meta Batida — Fixo                                      │
│  │  ✅ Meta atingida                                               │
│  │  Valor: R$200,00                                                │
│  │                                                                 │
│  │  Prêmio 1º Lugar Unidade — Fixo                                │
│  │  🏆 1º da unidade                                               │
│  │  Valor: R$200,00                                                │
│  │                                                                 │
│  │  Bonificação Assiduidade — Fixo R$200                          │
│  │  [✓ Concedido]  toggle                                          │
│  │  Valor: R$200,00                                                │
│  │                                                                 │
│  ├─────────────────────────────────────────────────────────────────│
│  │  RESUMO                                                         │
│  │  Garantido: R$498,00  ·  Potencial: R$310,00                   │
│  │                                                                 │
│  │  [█████Garantido█████|███Potencial███]                          │
│  │                                                                 │
│  ├─────────────────────────────────────────────────────────────────│
│  │                                           TOTAL A PAGAR         │
│  │                                              R$808,00           │
│  └─────────────────────────────────────────────────────────────────│
│                                                                ▴   │
└────────────────────────────────────────────────────────────────────┘
```

### 6.9 Card do Colaborador — Expandido (não bateu meta)

```
┌────────────────────────────────────────────────────────────────────┐
│  [ML]  Maria Luiza Costa          🟢 Aberto      Total variável   │
│        Vendedora · Setor Vendas                      R$210,00     │
│                                                                    │
│  ┌─ INDICADORES & RUBRICAS ──────── [+ Bonificação] [✓ Encerrar] │
│  │                                                                 │
│  │  Comissão Vendas Pessoal — 0,6% sobre vendas                   │
│  │  [█████████████░░░░░░░░░] 83,3% da meta (R$35k / R$42k)        │
│  │  Atual: R$210,00                                                │
│  │  ↗ Se bater meta: +R$105,00 (0,25%) +R$200,00 (prêmio)        │
│  │                                                                 │
│  │  Bônus Meta — 0,25% sobre vendas                               │
│  │  ⏳ Aguardando meta (faltam R$7.000)                            │
│  │  Potencial: R$105,00                                            │
│  │                                                                 │
│  │  Bônus Meta Batida — Fixo R$200                                │
│  │  ⏳ Aguardando meta                                             │
│  │  Potencial: R$200,00                                            │
│  │                                                                 │
│  │  Bonificação Assiduidade — Fixo R$200                          │
│  │  [✓ Concedido]  toggle                                          │
│  │  Valor: R$200,00                                                │
│  │                                                                 │
│  ├─────────────────────────────────────────────────────────────────│
│  │  RESUMO                                                         │
│  │  Garantido: R$410,00  ·  Potencial: R$305,00                   │
│  │                                                                 │
│  │  [████Garantido████|██████Potencial██████]                      │
│  │                                                                 │
│  ├─────────────────────────────────────────────────────────────────│
│  │                                           TOTAL A PAGAR         │
│  │                                              R$410,00           │
│  └─────────────────────────────────────────────────────────────────│
│                                                                ▴   │
└────────────────────────────────────────────────────────────────────┘
```

### 6.10 Card do Colaborador — Expandido (Rateio Xerox)

```
┌────────────────────────────────────────────────────────────────────┐
│  [MO]  Marcos Oliveira             🟢 Aberto     Total variável   │
│        Operador Xerox · Setor Xerox Centro           R$1.090,00   │
│                                                                    │
│  ┌─ VÍNCULO COM SETOR ────────────────────────── [editar]         │
│  │  Setor: Xerox Centro                                            │
│  │  Percentual rateio: 40%  ·  Dias trabalhados: 22               │
│  │                                                                 │
│  ├─ INDICADORES & RUBRICAS ──────── [+ Bonificação] [✓ Encerrar] │
│  │                                                                 │
│  │  Rateio Setor Xerox — coletivo                                  │
│  │  Total setor: R$1.368,00  ·  Sua parte: 41,1%                  │
│  │  [██████████████████░░░░░] 91,2% da meta (R$22.8k / R$25k)     │
│  │  Atual: R$540,00                                                │
│  │                                                                 │
│  │  Auxílio Alimentação — R$25,00/dia · desc. absenteísmo         │
│  │  22 dias trabalhados / 22 dias úteis                            │
│  │  Valor: R$550,00                                                │
│  │                                                                 │
│  ├─────────────────────────────────────────────────────────────────│
│  │                                           TOTAL A PAGAR         │
│  │                                            R$1.090,00           │
│  └─────────────────────────────────────────────────────────────────│
│                                                                ▴   │
└────────────────────────────────────────────────────────────────────┘
```

### 6.11 Modal de Edição — Vínculo com Setor

```
┌─────────────────────────────────────────┐
│  EDITAR VÍNCULO                     ✕   │
│                                         │
│  Colaborador: Marcos Oliveira           │
│                                         │
│  Setor:              [Xerox Centro  ▼]  │
│  Percentual rateio:  [40,000    ] %     │
│  Dias trabalhados:   [22        ]       │
│                                         │
│  ┌─────────────────────────────────┐    │
│  │ + Adicionar outro vínculo       │    │
│  └─────────────────────────────────┘    │
│                                         │
│           [Cancelar]  [Salvar]          │
└─────────────────────────────────────────┘
```

### 6.12 Modal de Edição — Rubrica

```
┌─────────────────────────────────────────┐
│  EDITAR RUBRICA                     ✕   │
│                                         │
│  Descrição:    [Comissão Vendas 0,6%  ] │
│                                         │
│  Tipo valor:   (●) Percentual  (○) Fixo │
│  Percentual:   [0,600    ] %            │
│                                         │
│  Base de cálculo:                       │
│  [Indic. individual vendedor       ▼]   │
│                                         │
│  Condição:     [Sem condição       ▼]   │
│                                         │
│  ☐ Desconta absenteísmo                 │
│  ☑ Recorrente                           │
│  ☑ Concedido                            │
│                                         │
│           [Cancelar]  [Salvar]          │
└─────────────────────────────────────────┘
```

### 6.13 Modal de Bonificação Avulsa

```
┌─────────────────────────────────────────┐
│  NOVA BONIFICAÇÃO                   ✕   │
│                                         │
│  Colaborador: Maria Luiza Costa         │
│                                         │
│  Descrição:    [Gratificação cobertura] │
│  Valor fixo:   [500,00             ]    │
│                                         │
│  ☑ Desconta absenteísmo                 │
│  ☐ Concedido                            │
│                                         │
│  (recorrente = false automático)        │
│                                         │
│           [Cancelar]  [Adicionar]       │
└─────────────────────────────────────────┘
```

### 6.14 Modal de Novo Período

```
┌─────────────────────────────────────────┐
│  NOVO PERÍODO                       ✕   │
│                                         │
│  Data inicial:   [26/02/2026     ]      │
│  Data final:     [25/03/2026     ]      │
│  Dias úteis:     [22             ]      │
│                                         │
│  ☑ Duplicar do período anterior         │
│    (vínculos, rubricas recorrentes,     │
│     metas)                              │
│                                         │
│           [Cancelar]  [Criar]           │
└─────────────────────────────────────────┘
```

### 6.15 Fluxo de Interação — Resumo

```
DRAWER: Seleciona período
  → Área central carrega dados do período

BARRA ALERTAS: Clica no alerta
  → Scrolla até o colaborador / abre modal de configuração

CARD COLAPSADO: Clica
  → Expande com indicadores, rubricas, projeções

CARD EXPANDIDO:
  → Clica "editar" no vínculo → Modal 6.11
  → Clica em rubrica → Modal 6.12
  → Clica "+ Bonificação" → Modal 6.13
  → Toggle "Concedido" → atualiza direto, recalcula
  → Clica "Encerrar" → confirma → gera título → status muda
  → Clica "Estornar" → confirma → estorna título → reabre

CABEÇALHO UNIDADE:
  → Clica meta → edita inline
  → Colapsa/expande unidade

VISÃO CUSTOS:
  → Toggle UN/Filial alterna agrupamento

DRAWER:
  → "+ Novo período" → Modal 6.14
  → Clica "Fechar período" → confirma → muda status
  → Clica "Reabrir" em período fechado → confirma → reabre
```

---

## 7. Modelo de Dados

### 7.1 Alterações em Tabelas Existentes

**tblpdv** — adicionar:

```sql
ALTER TABLE tblpdv ADD COLUMN codsetor bigint NOT NULL REFERENCES tblsetor(codsetor);
```

**tblproduto** — adicionar:

```sql
ALTER TABLE tblproduto ADD COLUMN codtiposetor bigint REFERENCES tbltiposetor(codtiposetor);
-- migrar bonificacaoxerox → codtiposetor (ver seção 8)
```

**tblempresa** — adicionar:

```sql
ALTER TABLE tblempresa ADD COLUMN fatorencargos numeric(6,3) NOT NULL DEFAULT 0.600;
```

### 7.2 Novas Tabelas

```sql
-- ===========================================
-- TIPO DE SETOR
-- ===========================================
CREATE TABLE tbltiposetor (
    codtiposetor bigserial PRIMARY KEY,
    tiposetor varchar(100) NOT NULL,
    inativo timestamp(0),
    criacao timestamp(0) NOT NULL DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) NOT NULL DEFAULT now(),
    codusuarioalteracao bigint
);

-- ===========================================
-- SETOR
-- ===========================================
CREATE TABLE tblsetor (
    codsetor bigserial PRIMARY KEY,
    codunidadenegocio bigint NOT NULL REFERENCES tblunidadenegocio(codunidadenegocio),
    codtiposetor bigint NOT NULL REFERENCES tbltiposetor(codtiposetor),
    setor varchar(100) NOT NULL,
    indicadorvendedor boolean NOT NULL DEFAULT false,
    indicadorcaixa boolean NOT NULL DEFAULT false,
    indicadorcoletivo boolean NOT NULL DEFAULT false,
    inativo timestamp(0),
    criacao timestamp(0) NOT NULL DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) NOT NULL DEFAULT now(),
    codusuarioalteracao bigint,
    UNIQUE(codunidadenegocio, codtiposetor)
);

-- ===========================================
-- PERÍODO
-- ===========================================
CREATE TABLE tblperiodo (
    codperiodo bigserial PRIMARY KEY,
    periodoinicial date NOT NULL,
    periodofinal date NOT NULL,
    diasuteis integer NOT NULL DEFAULT 0,
    status char(1) NOT NULL DEFAULT 'A',
    observacoes text,
    criacao timestamp(0) NOT NULL DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) NOT NULL DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT chk_periodo_status CHECK (status IN ('A', 'F')),
    CONSTRAINT chk_periodo_datas CHECK (periodofinal > periodoinicial)
);

-- ===========================================
-- INDICADOR
-- ===========================================
CREATE TABLE tblindicador (
    codindicador bigserial PRIMARY KEY,
    codperiodo bigint NOT NULL REFERENCES tblperiodo(codperiodo),
    codunidadenegocio bigint REFERENCES tblunidadenegocio(codunidadenegocio),
    codsetor bigint REFERENCES tblsetor(codsetor),
    codcolaborador bigint REFERENCES tblcolaborador(codcolaborador),
    tipo char(1) NOT NULL,
    meta numeric(14,2),
    valoracumulado numeric(14,2) NOT NULL DEFAULT 0,
    criacao timestamp(0) NOT NULL DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) NOT NULL DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT chk_indicador_tipo CHECK (tipo IN ('U', 'S', 'V', 'C'))
);

CREATE INDEX idx_indicador_periodo ON tblindicador(codperiodo);
CREATE INDEX idx_indicador_unidade ON tblindicador(codperiodo, codunidadenegocio, tipo);
CREATE INDEX idx_indicador_setor ON tblindicador(codperiodo, codsetor, tipo);
CREATE INDEX idx_indicador_colaborador ON tblindicador(codperiodo, codcolaborador, tipo);

-- ===========================================
-- INDICADOR LANÇAMENTO
-- ===========================================
CREATE TABLE tblindicadorlancamento (
    codindicadorlancamento bigserial PRIMARY KEY,
    codindicador bigint NOT NULL REFERENCES tblindicador(codindicador),
    codnegocio bigint REFERENCES tblnegocio(codnegocio),
    codnegocioprodutobarra bigint REFERENCES tblnegocioprodutobarra(codnegocioprodutobarra),
    valor numeric(14,2) NOT NULL,
    descricao varchar(200),
    manual boolean NOT NULL DEFAULT false,
    criacao timestamp(0) NOT NULL DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) NOT NULL DEFAULT now(),
    codusuarioalteracao bigint
);

CREATE INDEX idx_indicadorlanc_indicador ON tblindicadorlancamento(codindicador);
CREATE INDEX idx_indicadorlanc_negocio ON tblindicadorlancamento(codnegocio);

-- ===========================================
-- PERÍODO COLABORADOR
-- ===========================================
CREATE TABLE tblperiodocolaborador (
    codperiodocolaborador bigserial PRIMARY KEY,
    codperiodo bigint NOT NULL REFERENCES tblperiodo(codperiodo),
    codcolaborador bigint NOT NULL REFERENCES tblcolaborador(codcolaborador),
    status char(1) NOT NULL DEFAULT 'A',
    codtitulo bigint REFERENCES tbltitulo(codtitulo),
    encerramento timestamp(0),
    valortotal numeric(14,2) NOT NULL DEFAULT 0,
    criacao timestamp(0) NOT NULL DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) NOT NULL DEFAULT now(),
    codusuarioalteracao bigint,
    UNIQUE(codperiodo, codcolaborador),
    CONSTRAINT chk_periodocolab_status CHECK (status IN ('A', 'E'))
);

CREATE INDEX idx_periodocolab_periodo ON tblperiodocolaborador(codperiodo);
CREATE INDEX idx_periodocolab_colaborador ON tblperiodocolaborador(codcolaborador);

-- ===========================================
-- PERÍODO COLABORADOR SETOR (vínculo)
-- ===========================================
CREATE TABLE tblperiodocolaboradorsetor (
    codperiodocolaboradorsetor bigserial PRIMARY KEY,
    codperiodocolaborador bigint NOT NULL REFERENCES tblperiodocolaborador(codperiodocolaborador),
    codsetor bigint NOT NULL REFERENCES tblsetor(codsetor),
    percentualrateio numeric(6,3) NOT NULL DEFAULT 0,
    diastrabalhados numeric(10,2) NOT NULL DEFAULT 0,
    criacao timestamp(0) NOT NULL DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) NOT NULL DEFAULT now(),
    codusuarioalteracao bigint
);

CREATE INDEX idx_periodocolabsetor_pc ON tblperiodocolaboradorsetor(codperiodocolaborador);
CREATE INDEX idx_periodocolabsetor_setor ON tblperiodocolaboradorsetor(codsetor);

-- ===========================================
-- COLABORADOR RUBRICA
-- ===========================================
CREATE TABLE tblcolaboradorrubrica (
    codcolaboradorrubrica bigserial PRIMARY KEY,
    codperiodocolaborador bigint NOT NULL REFERENCES tblperiodocolaborador(codperiodocolaborador),
    codperiodocolaboradorsetor bigint REFERENCES tblperiodocolaboradorsetor(codperiodocolaboradorsetor),
    codindicador bigint REFERENCES tblindicador(codindicador),
    codindicadorcondicao bigint REFERENCES tblindicador(codindicador),
    descricao varchar(200) NOT NULL,
    tipovalor char(1) NOT NULL,              -- P=Percentual, F=Fixo
    percentual numeric(6,3),                 -- ex: 0.600 = 0,6%
    valorfixo numeric(14,2),                 -- ex: 200.00
    tipocondicao char(1),                    -- M=Meta, R=Ranking, NULL=sem condição
    concedido boolean NOT NULL DEFAULT true,  -- toggle: RH desmarca quem não cumpriu
    descontaabsenteismo boolean NOT NULL DEFAULT false,
    recorrente boolean NOT NULL DEFAULT true,
    valorcalculado numeric(14,2) NOT NULL DEFAULT 0,
    criacao timestamp(0) NOT NULL DEFAULT now(),
    codusuariocriacao bigint,
    alteracao timestamp(0) NOT NULL DEFAULT now(),
    codusuarioalteracao bigint,
    CONSTRAINT chk_colabrub_tipovalor CHECK (tipovalor IN ('P', 'F')),
    CONSTRAINT chk_colabrub_tipocondicao CHECK (tipocondicao IS NULL OR tipocondicao IN ('M', 'R'))
);

CREATE INDEX idx_colabrubrica_periodocolab ON tblcolaboradorrubrica(codperiodocolaborador);
CREATE INDEX idx_colabrubrica_indicador ON tblcolaboradorrubrica(codindicador);
```

### 7.3 Relacionamentos

```
tblunidadenegocio 1──N tblsetor
tbltiposetor 1──N tblsetor
tbltiposetor 1──N tblproduto (via codtiposetor)
tblsetor 1──N tblpdv (via codsetor)
tblsetor 1──N tblperiodocolaboradorsetor

tblperiodo 1──N tblperiodocolaborador
tblperiodo 1──N tblindicador
tblcolaborador 1──N tblperiodocolaborador

tblperiodocolaborador 1──N tblperiodocolaboradorsetor
tblperiodocolaborador 1──N tblcolaboradorrubrica
tblperiodocolaborador 0──1 tbltitulo

tblindicador 1──N tblindicadorlancamento
tblindicador 1──N tblcolaboradorrubrica (como base — codindicador)
tblindicador 1──N tblcolaboradorrubrica (como condição — codindicadorcondicao)

tblnegocio 1──N tblindicadorlancamento
tblnegocioprodutobarra 1──N tblindicadorlancamento
```

---

## 8. Carga Inicial (executada)

Script completo rodado em `etapa-1.1-ddl-carga-inicial.sql`. Resumo:

### Dados carregados

**Tipos de Setor (4):** Vendas, Xerox, Caixa, Venda Remota

**Unidades de Negócio (7):**

| codunidadenegocio | descricao      | codfilial |
| ----------------- | -------------- | --------- |
| 1                 | Botânico       | 102       |
| 2                 | Centro         | 103       |
| 3                 | Imperial       | 104       |
| 4                 | André Maggi    | 105       |
| 5                 | Sinopel        | NULL      |
| 6                 | Administrativo | 101       |
| 7                 | Depósito       | 101       |

**Setores (13):** 3 por loja (Vendas, Xerox, Caixa) + 1 Sinopel (Venda Remota)

**Fator de Encargos:** Migliorini (codempresa 1) = 0.680, FDF (codempresa 2) = 0.450

**Produtos:** 73 com codtiposetor = Xerox (migrados de bonificacaoxerox)

**PDVs mapeados:** Filiais 102-105 + Sinopel. 57 PDVs admin sem setor (filial 101 e sem filial).

**Períodos:**

| codperiodo | datas              | diasuteis | status | obs          |
| ---------- | ------------------ | --------- | ------ | ------------ |
| 1          | 01/02 a 25/02/2026 | 18        | A      | Transição    |
| 2          | 01/12 a 31/12/2025 | 22        | F      | Teste legado |
| 3          | 01/01 a 31/01/2026 | 22        | F      | Teste legado |

**Mapeamento PDVs Botânico (filial 102):**

- Sinopel (12 PDVs: 85, 120, 129, 132, 146, 155, 165, 183, 212, 234, 237, 245) → Venda Remota Sinopel
- Xerox (2 PDVs: 118, 243) → Xerox Botânico
- Resto → Vendas Botânico

**Mapeamento PDVs Centro/Imperial/André Maggi:**

- alocacao 'X' → Xerox da unidade
- Resto → Vendas da unidade

---

## 9. Eloquent Models

### Namespaces (definidos pelo gerador)

**`Mg\Rh`** — tabelas novas do módulo:

| Model                     | Tabela                       | Primary Key                  |
| ------------------------- | ---------------------------- | ---------------------------- |
| `Periodo`                 | `tblperiodo`                 | `codperiodo`                 |
| `Indicador`               | `tblindicador`               | `codindicador`               |
| `IndicadorLancamento`     | `tblindicadorlancamento`     | `codindicadorlancamento`     |
| `PeriodoColaborador`      | `tblperiodocolaborador`      | `codperiodocolaborador`      |
| `PeriodoColaboradorSetor` | `tblperiodocolaboradorsetor` | `codperiodocolaboradorsetor` |
| `ColaboradorRubrica`      | `tblcolaboradorrubrica`      | `codcolaboradorrubrica`      |

**`Mg\Filial`** — tabelas organizacionais (gerador colocou aqui por causa do codfilial):

| Model            | Tabela              | Primary Key         |
| ---------------- | ------------------- | ------------------- |
| `TipoSetor`      | `tbltiposetor`      | `codtiposetor`      |
| `Setor`          | `tblsetor`          | `codsetor`          |
| `UnidadeNegocio` | `tblunidadenegocio` | `codunidadenegocio` |

### Relações principais:

**Periodo:**

- `PeriodoColaboradorS` → hasMany PeriodoColaborador
- `IndicadorS` → hasMany Indicador

**Setor:**

- `UnidadeNegocio` → belongsTo UnidadeNegocio
- `TipoSetor` → belongsTo TipoSetor
- `PdvS` → hasMany Pdv
- `IndicadorS` → hasMany Indicador
- `PeriodoColaboradorSetorS` → hasMany PeriodoColaboradorSetor

**Indicador:**

- `Periodo` → belongsTo Periodo
- `UnidadeNegocio` → belongsTo UnidadeNegocio
- `Setor` → belongsTo Setor
- `Colaborador` → belongsTo Colaborador
- `IndicadorLancamentoS` → hasMany IndicadorLancamento
- `ColaboradorRubricaS` → hasMany ColaboradorRubrica (via codindicador)
- `ColaboradorRubricaCondicaoS` → hasMany ColaboradorRubrica (via codindicadorcondicao)

**PeriodoColaborador:**

- `Periodo` → belongsTo Periodo
- `Colaborador` → belongsTo Colaborador
- `Titulo` → belongsTo Titulo
- `PeriodoColaboradorSetorS` → hasMany PeriodoColaboradorSetor
- `ColaboradorRubricaS` → hasMany ColaboradorRubrica

**PeriodoColaboradorSetor:**

- `PeriodoColaborador` → belongsTo PeriodoColaborador
- `Setor` → belongsTo Setor

**ColaboradorRubrica:**

- `PeriodoColaborador` → belongsTo PeriodoColaborador
- `PeriodoColaboradorSetor` → belongsTo PeriodoColaboradorSetor
- `Indicador` → belongsTo Indicador (base de cálculo)
- `IndicadorCondicao` → belongsTo Indicador (condição)

---

## 10. Services

Todos em `app/Mg/Rh/`. Métodos estáticos. Transactions no controller/command (nunca nested).

### PeriodoService

**Constantes:**

```php
const STATUS_ABERTO = 'A';
const STATUS_FECHADO = 'F';
const STATUS_COLABORADOR_ABERTO = 'A';
const STATUS_COLABORADOR_ENCERRADO = 'E';
```

**Métodos:**

- `criar(array $data): Periodo` — validação de gap/sobreposição
- `duplicarDoAnterior(int $codperiodo): Periodo` — duplica indicadores (com mapa), vínculos, rubricas recorrentes, remapeia FKs
- `fechar(int $codperiodo): Periodo` — A → F
- `reabrir(int $codperiodo): Periodo` — F → A

**Status:** ✅ Implementado

### ProcessarVendaService

**Constantes:**

```php
const TIPO_UNIDADE = 'U';
const TIPO_SETOR = 'S';
const TIPO_VENDEDOR = 'V';
const TIPO_CAIXA = 'C';
```

**Métodos:**

- `processar(int $codnegocio): void` — roteamento + acumulação de indicadores + lançamentos
- `findOrCreateIndicador(...)` — busca ou cria indicador (idempotente)
- `resolverColaboradorPorPessoa(int $codpessoa): ?int`
- `resolverColaboradorPorUsuario(int $codusuario): ?int`
- `resolverPeriodo(Carbon $data): Periodo` — busca por data → próximo aberto → cria automático

**Idempotência:** verifica duplicidade por (codindicador, codnegocioprodutobarra) antes de criar lançamento.

**Status:** 🔄 Em implementação

### CalculoRubricaService

**Constantes:**

```php
const TIPO_PERCENTUAL = 'P';
const TIPO_FIXO = 'F';
const CONDICAO_META = 'M';
const CONDICAO_RANKING = 'R';
```

**Métodos:**

- `calcular(int $codperiodo): void` — recalcula todos os colaboradores do período
- `calcularColaborador(int $codperiodocolaborador): void` — recalcula um colaborador
- Ordem fixa: base → condicionais meta → ranking
- Rateio ponderado pra setores coletivos
- Desconto de absenteísmo
- Flag `concedido` (ignora se false)
- Atualiza `valorcalculado` em cada rubrica e `valortotal` no período colaborador

### EncerramentoService

**Métodos:**

- `encerrar(int $codperiodocolaborador): PeriodoColaborador` — gerar título em tbltitulo
- `estornar(int $codperiodocolaborador): PeriodoColaborador` — cancelar título, reabrir colaborador

---

## 11. Plano de Implementação (MVP)

### Fase 1 — Banco + Carga ✅

- DDL direto no PostgreSQL (sem migrations)
- 7 tabelas novas + 3 ALTER TABLE
- Inserts de tipos de setor, unidades, setores
- Mapeamento de PDVs, migração de produtos
- Períodos de transição e teste

### Fase 2 — Models ✅

- `php artisan gerador:model` pra 16 tabelas
- Relationships gerados automaticamente e testados
- Namespaces: `Mg\Rh` (módulo) + `Mg\Filial` (organizacional)

### Fase 3 — Services (motor de cálculo) 🔄

- 3.1 PeriodoService ✅
- 3.2+3.3 ProcessarVendaService 🔄
- 3.4-3.10 CalculoRubricaService
- 3.11-3.12 EncerramentoService

### Fase 4 — API (Controllers)

- Controllers em `app/Mg/Rh/`, extends `Illuminate\Routing\Controller` (padrão Laravel, SEM MgController)
- Transactions no controller (DB::beginTransaction / commit / rollBack)
- Padrão de resposta: `response()->json($data)` ou `response()->json(['erro' => $msg], 422)`
- Validação inline (sem FormRequests)
- 6 controllers: PeriodoController, PeriodoColaboradorController, PeriodoColaboradorSetorController, ColaboradorRubricaController, IndicadorController, DashboardController
- Rotas prefixo `/api/rh/`

### Fase 5 — Frontend (Vue + Quasar)

- Drawer de períodos, barra de alertas, cards de resumo
- Visão de custos, cards de colaboradores
- Modais de edição, ações de encerrar/estornar

### Fase 6 — Integração com Vendas

- Disparar ProcessarVendaJob nos eventos de venda
- Criação automática de período

### Fase 7 — Testes com Dados Reais

- Carga dos 70 colaboradores
- Processar vendas de dezembro pra comparar com legado
- Validação end-to-end

### Pós-MVP

- Sanitizar tblunidadenegocio (remover campos/relacionamentos do legado)
- CRUDs das tabelas auxiliares (tipo setor, setor, unidade)
- Dashboard do gestor e do colaborador
- Importação do Secullum
- Transferência de venda entre setores
- Histórico de salário
- Templates de rubricas por cargo/setor
