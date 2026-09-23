---
id: doc-1
title: Tese de regularidade fiscal da venda de vale compras
type: other
created_date: '2026-09-23 20:24'
---

# Tese de regularidade fiscal da operação de vale compras

**MG Papelaria — Sinop/MT** · Memorando jurídico-fiscal · 23/09/2026
**Tributos:** ICMS · NFC-e · DIMP · IBS/CBS
**Tarefa relacionada:** TASK-38 — Fazer venda de vale-compras (decisão 2, tratamento fiscal)

> **Documento de trabalho** — validar com contador e advogado tributarista antes de qualquer uso formal.

Fundamentos, exemplos numéricos e elementos de prova que sustentam a praxe de venda de crédito
(vale compras escolar) com emissão do documento fiscal no momento da entrega da mercadoria.

---

## 1. Objeto

Este memorando consolida a tese jurídica que sustenta a operação de **vale compras escolar**:
recebimento antecipado de valores (à vista ou em transação única de cartão/PIX junto com
mercadorias), com emissão do documento fiscal **apenas no momento da saída da mercadoria**, ainda
que isso produza, pontualmente, transações de pagamento em valor superior ao documento fiscal do dia.

Destina-se a (i) orientar o desenho do módulo no ERP, (ii) servir de base para parecer do contador e
(iii) subsidiar eventual **consulta formal à SEFAZ-MT**.

## 2. Descrição da operação (a praxe)

A MG Papelaria comercializa, no período de volta às aulas, um **crédito em reais** ("vale compras")
vinculado a listas de material sugeridas por colégio e turma ("modelos"). A operação tem três momentos:

1. **Venda do vale** — o responsável pelo aluno paga o valor (dinheiro, PIX ou cartão, frequentemente
   na mesma transação em que leva mercadorias). Não há saída de mercadoria referente ao vale: o cliente
   recebe comprovante não fiscal com número, valor, favorecido (colégio), aluno e validade.
2. **Retirada (troca)** — o colégio favorecido retira mercadorias ao longo do ano, de forma *agregada e
   variável*: ora tudo junto, ora por turma, ora por vales avulsos entregues a professores. Os itens
   retirados **não necessariamente coincidem** com a lista sugerida; o consumo é controlado **por valor**,
   abatendo o crédito. Nesse momento é emitida a NFC-e normal da venda, com `tPag = 12` (Vale Presente).
3. **Expiração** — o saldo não utilizado até a validade é revertido de passivo para outras receitas
   (breakage), sem emissão de documento fiscal, pois não houve circulação de mercadoria.

### Caracterização essencial

O vale é **crédito em valor, de uso genérico no estabelecimento** — não é reserva de itens determinados.
A lista/modelo é *orçamento sugerido*; o consumo real diverge dela, é agregado por escola ou turma e
abatido por valor. Essa característica afasta o enquadramento como "venda para entrega futura"
(CFOP 5.922/5.117), que pressupõe mercadorias determinadas.

## 3. Fundamentos jurídicos

### 3.1 O fato gerador do ICMS é a saída da mercadoria, não o recebimento

> "Considera-se ocorrido o fato gerador do imposto no momento: I — da saída de mercadoria de
> estabelecimento de contribuinte […]"
> — Lei Complementar nº 87/1996 (Lei Kandir), art. 12, I — reproduzido pelo RICMS-MT

O recebimento do preço é evento **financeiro**, estranho à materialidade do imposto. A obrigação
tributária surge com o fato gerador (CTN, arts. 113 e 114); antes da saída da mercadoria não há operação
de circulação, não há base de cálculo determinável (itens, NCM, alíquotas desconhecidos) e, portanto,
**não há documento fiscal a emitir**. O fluxo financeiro ou acordo comercial não descaracteriza — nem
antecipa — a ocorrência do fato gerador.

A mesma lógica rege situações corriqueiras e pacíficas: **recebimento de crediário** (nota emitida meses
antes, pagamento hoje, sem novo documento), **sinal de encomenda**, adiantamentos de clientes em geral.

### 3.2 O modelo é expressamente reconhecido pelo layout nacional da NFC-e

A tabela nacional de meios de pagamento da NF-e/NFC-e (campo `tPag`) contém o código
**12 — Vale Presente**. A existência do código pressupõe, por construção, que:

- houve um momento anterior em que o vale foi vendido **sem** emissão de documento fiscal de mercadoria; e
- o documento fiscal é emitido **na troca**, tendo o vale como meio de pagamento.

É a prática consolidada do varejo nacional (grandes redes de vestuário, livrarias, marketplaces), operada
abertamente há décadas sob as mesmas regras.

### 3.3 A soma dos pagamentos vincula-se à nota, não à transação financeira

A regra de validação do layout exige `Σ vPag − vTroco = vNF` — coerência **interna** ao documento. Não
existe norma que exija igualdade entre o valor autorizado na transação de cartão/PIX e o valor do
documento fiscal do dia. Em venda mista (mercadoria + vale numa transação única), informa-se no `detPag`
apenas a fração do pagamento que corresponde à nota, mantendo o `cAut` da transação real como elo de
auditoria.

### 3.4 A divergência DIMP × documentos fiscais é presunção relativa — e se desconstitui com prova

As administradoras de cartão e PSPs informam à SEFAZ todas as transações (DIMP). As legislações estaduais
tipificam a diferença entre esses valores e as receitas documentadas como **presunção de omissão de
receita** — presunção *juris tantum*, que admite prova em contrário. Comprovado que o ingresso corresponde
a hipótese **fora do campo de incidência** naquele momento (adiantamento, crediário, aluguel…), a
presunção resta desconstituída.

A jurisprudência administrativa e judicial condena, nesse cruzamento, o contribuinte que recebe por cartão
**sem emitir documento algum e sem escrituração que explique a diferença**. A situação da empresa é a
oposta: cada real recebido a título de vale é escriturado como passivo na data do ingresso e convertido em
NFC-e com `tPag=12` na retirada.

## 4. Exemplos numéricos

### 4.1 Venda isolada de vale

Venda do vale de R$ 200,00 — PIX

| Evento | Documento | Valor |
| --- | --- | --- |
| Recebimento PIX | Comprovante não fiscal do vale | 200,00 |
| Lançamento contábil | D: Caixa/Bancos · C: Passivo — Adiantamento de clientes (vales) | 200,00 |
| NFC-e emitida | Nenhuma — não houve saída de mercadoria | — |

### 4.2 Venda mista — o caso que gera o "gap"

Cliente leva R$ 500,00 em mercadorias e compra vale de R$ 200,00, pagando **R$ 700,00 em transação única**
de cartão (3x).

| Registro | Conteúdo | Valor |
| --- | --- | --- |
| Transação cartão (POS) | Autorização única `cAut 123456` — é o que a DIMP reporta | 700,00 |
| NFC-e | Itens de mercadoria; `vNF = 500`; `detPag`: `tPag=03, vPag=500, cAut=123456` | 500,00 |
| Vale nº V-xxxx | Crédito emitido, vinculado ao negócio e ao `cAut` | 200,00 |
| Divergência DIMP do dia | Explicada integralmente pelo vale (passivo escriturado) | 200,00 |

#### Condutas vedadas

- **Não** usar `vPag=700` com `vTroco=200`: troco declara devolução em dinheiro que não ocorreu —
  registro inverídico, com potencial enquadramento na Lei nº 8.137/1990.
- **Não** incluir o vale como item da NFC-e (CFOP 5.949 ou similar) para "fechar" valores: vale não é
  mercadoria, não tem NCM, e a nota passaria a documentar operação inexistente.

### 4.3 Retirada pelo colégio

Colégio retira R$ 150,00 em mercadorias (itens quaisquer, não necessariamente os da lista), consumindo
créditos por ordem de emissão (FIFO) dentro do escopo apresentado (escola, turma ou vales avulsos).

| Registro | Conteúdo | Valor |
| --- | --- | --- |
| NFC-e | Itens retirados; CNPJ do colégio; `tPag = 12` (Vale Presente) | 150,00 |
| Movimento do vale | USO — rateio por vale consumido, vinculado à NFC-e | −150,00 |
| Lançamento contábil | D: Passivo — vales · C: Receita de vendas (+ CMV/estoque) | 150,00 |
| ICMS | Devido normalmente nesta operação — momento correto do fato gerador | — |

### 4.4 Conciliação mensal DIMP — o relatório que fecha a tese

Modelo de conciliação (valores ilustrativos)

| Linha | Valor |
| --- | --- |
| Recebimentos cartão/PIX do mês (DIMP) | 700.000,00 |
| (−) NFC-e/NF-e do mês pagas com cartão/PIX | (520.000,00) |
| (−) Recebimentos de títulos — crediário (notas de meses anteriores) | (150.000,00) |
| (−) Vendas de vale compras do mês (→ passivo) | (30.000,00) |
| Diferença não explicada | 0,00 |

Gerado e arquivado mensalmente pelo ERP, este relatório converte qualquer notificação de malha em resposta
documental imediata, ainda na fase de intimação prévia.

### 4.5 Expiração (breakage)

Vale de R$ 200,00 com R$ 50,00 não utilizados no vencimento

| Registro | Conteúdo | Valor |
| --- | --- | --- |
| Movimento do vale | EXPIRACAO | −50,00 |
| Lançamento contábil | D: Passivo — vales · C: Outras receitas operacionais | 50,00 |
| ICMS / NFC-e | Não incide / não se emite — não houve circulação de mercadoria | — |
| IRPJ/CSLL (e PIS/COFINS cf. regime) | Incidem sobre a receita reconhecida — tratar com o contador | — |

## 5. Elementos de prova (trilha de auditoria exigida do ERP)

- **Escrituração tempestiva do passivo**: conta "Adiantamentos de clientes — vale compras", movimentada na
  data de cada ingresso, refletida no SPED.
- **Vínculo individualizado**: cada vale carrega o negócio de origem e o identificador da transação
  (`cAut` do cartão / E2E do PIX).
- **Baixa casada**: cada consumo gera movimento USO amarrado à NFC-e de troca (`tPag=12`), com rateio por vale.
- **Relatório de conciliação DIMP** mensal (§ 4.4), com diferença zero, arquivado.
- **Saldo do passivo** = soma dos saldos dos vales ativos, conciliado mensalmente com a contabilidade.
- **Comprovante do vale** com texto que reforce a natureza: *"crédito no valor de R$ X para aquisição de
  produtos no estabelecimento; lista sugerida em anexo; validade …"*.

## 6. Riscos identificados e mitigação

| Risco | Avaliação | Mitigação |
| --- | --- | --- |
| Requalificação como venda para entrega futura (5.922/5.117), por existir lista/modelo com itens e preços | Ponto mais sensível da tese | Operar e documentar como crédito em valor: consumo agregado, itens livres, saldo em reais; modelo tratado como orçamento sugerido (§ 2). Submeter expressamente a questão na consulta formal. |
| Malha DIMP (pagamento > documento fiscal) | Presunção relativa — administrável | Trilha do § 5 + conciliação § 4.4. Sazonalidade (venda ago–dez, consumo jan–mar) reforça a narrativa. |
| Registro inverídico para "fechar" valores (vTroco, item fictício) | Único cenário com risco penal-tributário | Vedado por política interna e pelo sistema (§ 4.2). |
| Validade curta do vale × CDC | Risco consumerista, não fiscal | Prazo de validade razoável e regras claras no comprovante. |

## 7. Reforma tributária (IBS/CBS — LC 214/2025)

O art. 10 da LC 214/2025 mantém o **fornecimento** como momento do fato gerador do IBS/CBS, confirmando a
lógica da tese. Há, porém, hipóteses de **antecipação quando há pagamento prévio** (art. 10, §§ 4º–5º, com
regras de cálculo e cancelamento), cuja aplicação a vale de uso genérico — em que o fornecimento é
indeterminado no pagamento — ainda não está assentada. No direito comparado (Diretiva UE 2016/1065),
vouchers de finalidade múltipla (*multi-purpose*) tributam apenas no resgate; a caracterização do § 2
aproxima o vale dessa figura. **Incluir o ponto na consulta formal** antes do desenho definitivo para 2027+.

## 8. Conclusão e providências

### Síntese da tese

A venda de vale compras é **recebimento antecipado sem fato gerador de ICMS**; o documento fiscal é devido
— e emitido — na saída da mercadoria, com o vale como meio de pagamento (`tPag=12`). A eventual divergência
entre transação financeira e documento fiscal do dia é **presunção relativa**, integralmente desconstituída
pela escrituração do passivo e pela trilha de auditoria. A conduta é a praxe consolidada do varejo nacional,
reconhecida pelo próprio layout da NFC-e.

1. **Protocolar consulta formal à SEFAZ-MT** descrevendo a operação exata (incluindo o caso § 4.2 e a
   questão da lista/modelo) — único instrumento que vincula o fisco e suspende autuação sobre a matéria
   consultada.
2. **Parecer escrito** do contador/tributarista referendando a contabilização e afastando dolo em cenário
   adverso.
3. **Implementar no ERP a trilha do § 5 como requisito fiscal**, com o relatório de conciliação § 4.4
   gerado mensalmente.
4. Revisar o texto do comprovante do vale e as regras de validade.

---

**Aviso.** Este documento foi elaborado com auxílio de IA como material de trabalho interno. Não constitui
parecer jurídico ou contábil. As referências normativas (LC 87/96, CTN, layout NF-e/NFC-e, LC 214/2025,
Lei 8.137/90) devem ser conferidas na redação vigente, e os dispositivos correspondentes do RICMS-MT e da
legislação mato-grossense sobre presunções de omissão de receita devem ser identificados pelo contador
antes do protocolo de qualquer consulta.

*MG Papelaria · Sinop/MT · setembro de 2026*
