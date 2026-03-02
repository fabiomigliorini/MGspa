# SPEC — Aba ACERTOS (Encontro de Contas RH)

## 1. Visão Geral

Nova aba **ACERTOS** na tela Metas & Variáveis (`/rh/{codperiodo}`), ao lado de RESUMO / COLABORADORES / INDICADORES. Permite ao RH visualizar a situação de créditos e débitos de cada colaborador do período e executar o encontro de contas, gerando liquidações, recibos e relatório para contabilidade.

**Fluxo resumido:**
1. RH encerra colaboradores na aba COLABORADORES (gera título de crédito com variável)
2. RH abre aba ACERTOS — vê panorama de créditos × débitos de todos os colaboradores
3. RH clica no colaborador encerrado, abre modal, ajusta valores se necessário, confirma
4. Sistema gera liquidações e movimentos no financeiro
5. RH imprime recibos e relatório de folha para contabilidade

---

## 2. Alterações no Banco de Dados

Já aplicadas:

```sql
-- Campo percentual máximo desconto folha no período
ALTER TABLE tblperiodo
ADD COLUMN percentualmaxdesconto numeric(5,2) NOT NULL DEFAULT 30;

-- Campo codperiodo na liquidação + FK + índice
ALTER TABLE tblliquidacaotitulo
ADD COLUMN codperiodo bigint;

ALTER TABLE tblliquidacaotitulo
ADD CONSTRAINT tblliquidacaotitulo_codperiodo_fkey
FOREIGN KEY (codperiodo) REFERENCES tblperiodo(codperiodo);

CREATE INDEX idx_tblliquidacaotitulo_codperiodo
ON tblliquidacaotitulo(codperiodo);
```

Models já regenerados via `php artisan gerador:model tblperiodo` e `php artisan gerador:model tblliquidacaotitulo`.

---

## 3. Constantes e Referências

| Conceito | Tabela | Campo | Valor |
|---|---|---|---|
| Tipo movimento liquidação RH | tbltipomovimentotitulo | codtipomovimentotitulo | 601 |
| Conta contábil Rubrica RH | tblcontacontabil | codcontacontabil | 360 |
| Portador Caixa Financeiro | tblportador | codportador | 100 |
| Portador Acerto Folha Salarial | tblportador | codportador | 202018 |
| Tipo título RH (encerramento) | tbltipotitulo | codtipotitulo | 952 |
| Default N dias vencimento | — | — | 5 |
| Default % máx desconto folha | tblperiodo | percentualmaxdesconto | 30 |

---

## 4. Aba ACERTOS — Listagem

### 4.1 Visibilidade
- Aparece **sempre**, independente de haver colaboradores encerrados
- Lista **todos** os colaboradores do período (status A e E)
- Colaboradores com status A: mostra saldos de títulos existentes, mas **não permite** executar encontro
- Colaboradores com status E: permite executar encontro
- Indicador visual (badge/ícone) diferenciando status A de E

### 4.2 Filtro no Topo
- Input numérico **"Dias"** — default 5, controla quais títulos vêm pré-selecionados no modal (vencimento <= hoje + N dias)
- **Apenas visual** (não persiste), recalcula simulação dos pendentes ao alterar
- Colaboradores já efetivados mostram valores reais do banco, não recalculam

### 4.3 Colunas da Tabela

| Coluna | Descrição |
|---|---|
| **Nome** | Nome do colaborador (tblpessoa.pessoa) |
| **Créditos** | Total dos créditos selecionados/efetivados (valor que a empresa paga) |
| **Débitos** | Total dos débitos selecionados/efetivados (valor que o colaborador devolve) |
| **Financeiro** | Créditos - Débitos quando positivo (pago em espécie via Caixa Financeiro). Zero se negativo |
| **Folha** | Débitos - Créditos quando positivo (desconto na folha salarial). Zero se negativo |
| **Remanescente** | Soma líquida dos títulos não incluídos no encontro + quantidade. Ex: `1.050,00 (7)` |
| **Status** | Pendente / Efetivado. Estorno volta pra Pendente |
| **Ações** | Botões conforme status (ver 4.5) |

### 4.4 Agrupamento
- Por **Unidade de Negócio** (mesmo padrão da aba COLABORADORES)

### 4.5 Botões de Ação por Linha

**Status Pendente (colaborador encerrado):**
- Botão de **encontro** (abre modal) — só habilitado se status = E

**Status Pendente (colaborador aberto):**
- Sem botões de ação — apenas visualização

**Status Efetivado:**
- Dropdown/menu de **impressão** com opções disponíveis:
  - Recibo do que recebeu (só se houve créditos)
  - Recibo do que descontou (só se houve compensação)
  - Recibo desconto folha (só se houve desconto em folha)
  - Imprimir todos (concatena os recibos aplicáveis)
- Botão de **estorno**

### 4.6 Botões no Topo da Aba
- **Imprimir Todos os Recibos** — PDF consolidado de todos os colaboradores efetivados, separados por quebra de página
- **Relatório Folha** — PDF para contabilidade (ver seção 8)

### 4.7 Simulação vs Dados Reais
- **Pendente:** valores calculados com base nos títulos com `saldo != 0` do colaborador, simulando o encontro com títulos vencidos + N dias pré-selecionados
- **Efetivado:** valores reais lidos das liquidações gravadas no banco

---

## 5. Modal de Encontro de Contas

### 5.1 Cabeçalho
- Nome do colaborador
- Cargo (tblcolaboradorcargo mais recente)
- Tempo de casa (calculado a partir de tblcolaborador.contratacao, formato moment().fromNow)

### 5.2 Tabela de Títulos

Tabela única listando **todos os títulos com saldo != 0** do colaborador (codpessoa), independente de tipo, portador ou filial.

| Coluna | Descrição |
|---|---|
| **Título** | codtitulo ou numero do título |
| **Vencimento** | tbltitulo.vencimento |
| **Saldo** | tbltitulo.saldo (negativo = crédito, positivo = débito) |
| **Pagando** | Input habilitado apenas quando saldo < 0 (crédito). Valor absoluto |
| **Descontando** | Input habilitado apenas quando saldo > 0 (débito). Valor absoluto |

**Ordenação:** vencimento ASC, saldo ASC, codtitulo ASC

**Comportamento dos inputs:**
- Títulos com vencimento <= hoje + N dias: input **pré-preenchido** com valor absoluto do saldo
- Títulos com vencimento > hoje + N dias: input **zerado**
- Botão **X** ao lado de cada input para limpar o valor
- **Validação:** não permite valor maior que o saldo absoluto do título
- Atualiza totais do rodapé em tempo real conforme edição

### 5.3 Rodapé do Modal

| Campo | Valor |
|---|---|
| **Total Pagando** | Soma dos inputs da coluna Pagando |
| **Total Descontando** | Soma dos inputs da coluna Descontando |
| **Resultado** | Total Pagando - Total Descontando |

Indicação visual conforme resultado:
- **Positivo** → label "Financeiro" em verde (valor pago em espécie via Caixa Financeiro)
- **Negativo** → label "Acerto Folha" em vermelho (valor descontado na folha)
- **Zero** → label "Encontro total" em cinza

### 5.4 Alerta de Limite Folha
Quando resultado é negativo (desconto em folha), o sistema verifica:
- Busca salário: `tblcolaborador.salario` → fallback `tblcolaboradorcargo.salario` (cargo mais recente)
- Calcula percentual: `abs(resultado) / salario * 100`
- Se excede `tblperiodo.percentualmaxdesconto`: exibe alerta amarelo **não bloqueante**
- Texto: "Atenção: desconto de R$ {valor} representa {pct}% do salário (R$ {salario}). Limite configurado: {limite}%."

### 5.5 Campo de Observação
- Textarea livre abaixo da tabela
- Grava em `tblliquidacaotitulo.observacao`
- Máximo 200 caracteres

### 5.6 Botão Confirmar
- Habilitado quando pelo menos um input (Pagando ou Descontando) tem valor > 0
- Executa a gravação (ver seção 6)

---

## 6. Lógica de Gravação (Efetivação)

### 6.1 Cenários Possíveis

**Cenário A — Só créditos selecionados (só Pagando):**
- 1 liquidação com portador Caixa Financeiro (100)
- Movimentos debitam os títulos de crédito
- Resultado: pago em espécie

**Cenário B — Só débitos selecionados (só Descontando):**
- 1 liquidação com portador Acerto Folha Salarial (202018)
- Movimentos creditam os títulos de débito
- Resultado: desconto integral em folha

**Cenário C — Créditos >= Débitos (resultado positivo ou zero):**
- 1 liquidação com portador Caixa Financeiro (100)
- Movimentos creditam os títulos de débito (quita)
- Movimentos debitam os títulos de crédito (compensa + paga espécie)
- Soma débitos dos movimentos = soma créditos dos movimentos

**Cenário D — Créditos < Débitos (resultado negativo):**
- 2 liquidações:
  - Liquidação 1 — portador Caixa Financeiro (100): compensa créditos contra débitos até zerar o crédito
  - Liquidação 2 — portador Acerto Folha Salarial (202018): débito restante que vai pra folha
- Cada liquidação tem seus movimentos equilibrados internamente

### 6.2 Registros Gerados

**tblliquidacaotitulo (1 ou 2 registros):**

| Campo | Valor |
|---|---|
| transacao | date do dia (now()) |
| sistema | timestamp now() |
| codportador | 100 ou 202018 conforme cenário |
| observacao | "RH Período de DD/MMM/YYYY a DD/MMM/YYYY" + observação do modal se houver |
| codusuario | usuário logado |
| codpessoa | codpessoa do colaborador |
| codperiodo | codperiodo do período |
| tipo | NULL |
| codpdv | NULL |
| debito | NULL (calculado pela soma dos movimentos) |
| credito | NULL (calculado pela soma dos movimentos) |

**tblmovimentotitulo (N registros por liquidação):**

| Campo | Valor |
|---|---|
| codtipomovimentotitulo | 601 (Liquidação RH) |
| codtitulo | codtitulo do título sendo afetado |
| codportador | mesmo da liquidação pai |
| debito | valor quando debitando (título com saldo crédito) |
| credito | valor quando creditando (título com saldo débito) |
| historico | NULL |
| transacao | date do dia |
| sistema | timestamp now() |
| codliquidacaotitulo | FK pra liquidação criada |

**Regra fundamental:** Em cada liquidação, soma dos campos `debito` de todos movimentos = soma dos campos `credito` de todos movimentos. Sempre equilibrado.

**Nota:** Os campos `debitosaldo`, `creditosaldo` e `saldo` da `tbltitulo` são atualizados automaticamente por trigger ao inserir em `tblmovimentotitulo`.

### 6.3 Transação
- `DB::beginTransaction()` no controller
- Cria liquidação(ões)
- Cria movimentos
- `DB::commit()`
- Em caso de erro: `DB::rollBack()` + response 422

### 6.4 Restrições
- Só executa para colaboradores com status **E** (Encerrado) no período
- Um colaborador só pode ter **um encontro** por período (validar que não existe `tblliquidacaotitulo` com `codperiodo` + `codpessoa` ativos para o mesmo período)
- Bloquear reabertura de colaborador (E → A) se existir liquidação vinculada não estornada

---

## 7. Estorno

### 7.1 Fluxo
1. RH clica botão estornar na linha do colaborador efetivado
2. Confirmação (dialog "Tem certeza?")
3. Backend:
   - Marca `tblliquidacaotitulo.estornado = now()` e `codusuarioestorno = usuário logado` em todas as liquidações do colaborador/período
   - Triggers revertem os saldos dos títulos automaticamente
4. Status volta para **Pendente**
5. RH pode refazer o encontro

### 7.2 Observações
- Recibos ficam indisponíveis após estorno
- Botão estornar **não aparece** se não houver liquidação ativa

---

## 8. Relatório Folha (PDF para Contabilidade)

### 8.1 Conteúdo
- Agrupado por **empresa** (tblcolaborador → tblfilial → tblempresa)
- Apenas colaboradores com liquidação efetivada que tenha portador Acerto Folha Salarial (202018)
- Pode gerar parcial (somente confirmados até o momento)

### 8.2 Colunas por Empresa

| Coluna | Origem |
|---|---|
| Nome | tblpessoa.pessoa |
| CPF | tblpessoa.cnpj (quando fisica = true, formata como CPF) |
| Filial | tblfilial.filial |
| Valor Desconto | soma dos movimentos da liquidação com portador 202018 |

- Total por empresa no rodapé de cada grupo
- Total geral no final

### 8.3 Cabeçalho
- Título: "Relatório de Desconto em Folha"
- Período: "Período de DD/MMM/YYYY a DD/MMM/YYYY"
- Data de geração

---

## 9. Recibos (PDF)

### 9.1 Tipos de Recibo

Cada recibo só é gerado se a condição for satisfeita:

| Recibo | Condição | Portador |
|---|---|---|
| **Recibo de Créditos** | Houve valores na coluna Pagando | — |
| **Recibo de Encontro** | Houve compensação (Pagando E Descontando) | Caixa Financeiro (100) |
| **Recibo Desconto Folha** | Resultado negativo (Descontando > Pagando) | Acerto Folha Salarial (202018) |

### 9.2 Dados Comuns

| Dado | Origem |
|---|---|
| Empresa (CNPJ, Razão Social) | tblcolaborador → tblfilial → tblpessoa (da empresa) |
| Colaborador (Nome, CPF) | tblcolaborador → tblpessoa (fisica = true → formata como CPF) |
| Período | tblperiodo.periodoinicial / periodofinal |
| Data | data da liquidação |

### 9.3 Corpo do Recibo

**Recibo de Créditos (o que recebeu):**
- Lista de títulos de crédito com valor usado
- Se título vinculado ao período (codtipotitulo=952), detalha as rubricas (via tblperiodocolaborador → tblcolaboradorrubrica)
- Demais títulos: apenas número, vencimento e valor
- Total de créditos

**Recibo de Encontro (compensação):**
- Lista de títulos de débito compensados com valor
- Lista de títulos de crédito usados na compensação com valor
- Total compensado

**Recibo Desconto Folha:**
- Valor total do desconto
- Lista dos títulos de débito que serão descontados em folha

### 9.4 Layout
- Simples, sem logo
- Uma via apenas
- Campo de assinatura do colaborador
- Formato A4

### 9.5 Impressão em Lote
- **Por colaborador:** concatena os recibos aplicáveis em um PDF, quebra de página entre cada
- **Global (topo da aba):** todos os colaboradores efetivados, agrupados por colaborador com quebra de página

---

## 10. Permissões

- Todas as operações restritas ao grupo **Recursos Humanos**
- Qualquer usuário do grupo pode efetivar e estornar

---

## 11. API — Contrato de Endpoints

### 11.1 Listagem de Acertos

```
GET /api/rh/periodo/{codperiodo}/acertos?dias=5
```

Response:
```json
{
    "data": [
        {
            "codperiodocolaborador": 123,
            "codcolaborador": 45,
            "codpessoa": 678,
            "nome": "Fulano de Tal",
            "status_periodo": "E",
            "status_acerto": "pendente",
            "creditos": 500.00,
            "debitos": 370.00,
            "financeiro": 130.00,
            "folha": 0.00,
            "remanescente_valor": 1050.00,
            "remanescente_qtd": 7,
            "codunidadenegocio": 1,
            "unidade": "Botânico"
        }
    ]
}
```

### 11.2 Títulos do Colaborador (para o Modal)

```
GET /api/rh/periodo/{codperiodo}/acertos/{codperiodocolaborador}/titulos?dias=5
```

Response:
```json
{
    "data": {
        "colaborador": {
            "codperiodocolaborador": 123,
            "codpessoa": 678,
            "nome": "Fulano de Tal",
            "cargo": "Vendedor",
            "tempo_casa": "3 anos",
            "salario": 1500.00,
            "percentual_max_desconto": 30
        },
        "titulos": [
            {
                "codtitulo": 2219,
                "numero": "001234",
                "vencimento": "2026-02-15",
                "saldo": 23.00,
                "debitosaldo": 23.00,
                "creditosaldo": 0.00,
                "sugestao_descontando": 23.00,
                "sugestao_pagando": 0.00
            },
            {
                "codtitulo": 209767,
                "numero": "RH-00456",
                "vencimento": "2026-02-25",
                "saldo": -242.50,
                "debitosaldo": 0.00,
                "creditosaldo": 242.50,
                "sugestao_descontando": 0.00,
                "sugestao_pagando": 242.50
            }
        ]
    }
}
```

### 11.3 Efetivar Encontro

```
POST /api/rh/periodo/{codperiodo}/acertos/{codperiodocolaborador}/efetivar
```

Request:
```json
{
    "observacao": "Texto opcional do RH",
    "titulos": [
        {
            "codtitulo": 2219,
            "descontando": 23.00,
            "pagando": 0.00
        },
        {
            "codtitulo": 209767,
            "descontando": 0.00,
            "pagando": 242.50
        }
    ]
}
```

Response:
```json
{
    "data": {
        "status": "efetivado",
        "liquidacoes": [
            {
                "codliquidacaotitulo": 9901,
                "portador": "Caixa Financeiro",
                "total": 219.50
            }
        ],
        "financeiro": 219.50,
        "folha": 0.00
    }
}
```

### 11.4 Estornar Encontro

```
POST /api/rh/periodo/{codperiodo}/acertos/{codperiodocolaborador}/estornar
```

Response:
```json
{
    "data": {
        "status": "pendente",
        "liquidacoes_estornadas": 1
    }
}
```

### 11.5 Recibos

```
GET /api/rh/periodo/{codperiodo}/acertos/recibos?colaboradores[]={codperiodocolaborador}&tipos[]={tipo}
```

Parâmetros query:
- `colaboradores[]` — array de codperiodocolaborador. Vazio = todos efetivados
- `tipos[]` — array: "creditos", "encontro", "folha". Vazio = todos aplicáveis

Response: `application/pdf`

### 11.6 Relatório Folha

```
GET /api/rh/periodo/{codperiodo}/acertos/relatorio-folha
```

Response: `application/pdf`

---

## 12. Query Base — Títulos com Saldo do Colaborador

```sql
SELECT
    t.codtitulo,
    t.numero,
    t.vencimento,
    t.saldo,
    t.debitosaldo,
    t.creditosaldo,
    tt.tipotitulo,
    CASE
        WHEN t.saldo > 0 AND t.vencimento <= CURRENT_DATE + :dias
            THEN t.saldo
        ELSE 0
    END AS sugestao_descontando,
    CASE
        WHEN t.saldo < 0 AND t.vencimento <= CURRENT_DATE + :dias
            THEN ABS(t.saldo)
        ELSE 0
    END AS sugestao_pagando
FROM tbltitulo t
JOIN tbltipotitulo tt ON tt.codtipotitulo = t.codtipotitulo
WHERE t.codpessoa = :codpessoa
  AND t.saldo != 0
ORDER BY t.vencimento, t.saldo, t.codtitulo
```

---

## 13. Alterações no CRUD de Período

### 13.1 Tela de Edição
- Novo input: "% Máx. Desconto Folha" (numeric, min 0, max 100)
- Default: 30

### 13.2 Criação Automática de Período
- No código PHP que cria períodos automaticamente, copiar `percentualmaxdesconto` do último período existente
- Se não houver período anterior, usar default 30

---

## 14. Resumo de Arquivos a Criar/Alterar

### Backend (Laravel — namespace Mg\Rh)
- **Service:** `AcertoService` — lógica de simulação, efetivação, estorno
- **Controller:** `AcertoController` — endpoints da API
- **FormRequest:** `EfetivarAcertoRequest` — validação do POST
- **Resource:** `AcertoListResource`, `AcertoTitulosResource` — formatação de responses
- **PDF:** Geração dos recibos e relatório de folha
- **Alterar:** `PeriodoService` (cópia do percentualmaxdesconto), CRUD do Período (novo campo)

### Frontend (Vue 3 + Quasar)
- **Componente:** `TabAcertos.vue` — aba com listagem
- **Componente:** `AcertoModal.vue` — modal de encontro de contas
- **Alterar:** Tela do período (input do percentual), formulário de período
