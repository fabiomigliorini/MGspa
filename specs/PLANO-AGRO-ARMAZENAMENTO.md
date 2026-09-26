# Plano de ação — armazenamento de grãos do /agro

Origem: auditoria de 2026-09-25 (lógica do /agro, com foco em entradas e saídas simultâneas).
Bateria de testes que reproduz cada defeito: `api/tests/agro-armazenamento/` (linha de base
em 2026-09-25: **20 cenários, 17 falhas**).

Este arquivo é para ser **executado de cima para baixo**. Cada fase termina num ponto de
parada: trabalho na árvore, **sem `git add` e sem commit**, com o roteiro de teste. Só se passa
para a próxima fase depois do "ok" de quem testa. Isso segue a seção **Commits** do `CLAUDE.md`.

---

## 0. Decisões de negócio — confirmar ANTES de começar

O plano já traz uma **recomendação** para cada decisão e foi escrito em cima dela. Se a
resposta for outra, ajuste a fase indicada.

| # | Pergunta | Recomendação (o que o plano executa) | Afeta |
|---|---|---|---|
| D1 | Saída maior que o saldo do silo e entrada acima da capacidade: **bloquear** ou **avisar**? | **Avisar e pedir confirmação no pátio; o servidor não bloqueia.** O grão já saiu fisicamente quando o romaneio fecha, e um aparelho offline não tem como saber o saldo. Um silo negativo aparece em vermelho no Estoque. | Fase 5 |
| D2 | Combinações de origem e destino permitidas por sentido. Pode sair **direto da lavoura para o comprador**? | ENTRADA: origem = talhão ou contrato de COMPRA; destino = silo ou contrato de VENDA (entrega direta). SAÍDA: silo → contrato de VENDA. TRANSFERÊNCIA: silo → **outro** silo. | Fase 4 |
| D3 | Carga FINALIZADA pode ter o peso alterado depois? | **Pode** (corrigir romaneio é rotina). A versão da Fase 1 já impede que dois aparelhos se atropelem. Auditoria de quem mudou o quê fica fora deste plano. | nenhuma |
| D4 | Ajustes manuais de **retirada** de silo já gravados em PROD somaram ao saldo. Inverter o sinal deles? | **Sim**, com o script da Fase 3 rodado depois de conferir a lista com quem lançou. No dev não há nenhum ajuste manual. | Fase 3 |
| D5 | Os problemas de sincronização entre aparelhos viram task nova ou entram na TASK-169 ("Pátio não atualiza sozinho")? | **Task nova.** A TASK-169 trata de mostrar dado velho; aqui é dado velho **gravando por cima** do novo, com outro teste e outro risco. | Fase 0 |

---

## Fase 0 — Backlog (só com o "pode criar" explícito)

> Regra 1 do `CLAUDE.md`: task só nasce a pedido de quem prioriza. Os comandos abaixo são o
> que **será** rodado depois do OK. Não rodar antes.

### 0.1 Critérios novos em tasks existentes

```bash
# TASK-170 — "Os números do grão não fecham" (mesma conta: a trava do contrato)
./backlog.sh task edit TASK-170 \
  --ac "Dois caminhões fechando ao mesmo tempo no mesmo contrato não passam do contratado" \
  --ac "Reativar uma carga cancelada respeita o teto do contrato" \
  --ac "Cancelar uma carga nunca é barrado pela trava do contrato"

# TASK-130 — "expedição não avisa saldo do silo"
./backlog.sh task edit TASK-130 \
  --ac "Linha de ORIGEM (silo) mostra o saldo do silo, como a do contrato mostra o saldo a entregar" \
  --ac "Saída maior que o saldo pede confirmação no pátio; entrada acima da capacidade também" \
  --ac "Saldo do silo no pátio é da safra da carga (não soma milho com soja no mesmo silo)" \
  --ac "Silo inativo não recebe carga nova" \
  --ac "Tela de Estoque destaca silo com saldo negativo"
./backlog.sh task edit TASK-130 --priority high   # confirmar: vira dinheiro/estoque errado toda semana na colheita
```

### 0.2 Tasks novas

```bash
./backlog.sh task create "Carga finalizada ou cancelada volta atrás quando outro aparelho sincroniza" \
  --type bug -l agro --priority high \
  -d "Com dois aparelhos no pátio, a cópia velha de um sobrescreve o que o outro já lançou: carga finalizada volta de etapa e o grão some do silo; carga cancelada volta a valer; em corrida, carga fica FINALIZADA sem ter entrado no estoque. Reenvio da mesma carga dá erro 'Rejeitado' falso. Plano: specs/PLANO-AGRO-ARMAZENAMENTO.md (Fases 1 e 2)." \
  --ac "Cópia velha de outro aparelho não desfaz finalização nem cancelamento; o aparelho é avisado e recebe a versão atual" \
  --ac "Carga FINALIZADA sempre tem o extrato correspondente (nunca uma sem a outra)" \
  --ac "Reenvio da mesma carga (Salvar + ciclo de sync, ou rede caindo) não dá erro nem duplica" \
  --ac "Editar a carga enquanto o envio anterior está no ar não perde a edição nova"

./backlog.sh task create "Pátio deixa lançar grão no contrato ou talhão errado (milho em contrato de soja)" \
  --type bug -l agro --priority medium \
  -d "O pátio aceita contrato de outra cultura, talhão de outra safra, contrato de venda como origem, transferência de um silo para ele mesmo e tara maior que o PBT. Plano: specs/PLANO-AGRO-ARMAZENAMENTO.md (Fase 4)." \
  --ac "Contrato de outra cultura é recusado, e o select só oferece contratos da cultura da safra" \
  --ac "Talhão de outra safra é recusado como origem" \
  --ac "Origem e destino seguem as combinações permitidas por sentido (D2)" \
  --ac "Tara maior que o PBT é recusada com mensagem clara"
```

Anote os ids gerados. Neste documento eles aparecem como **TASK-SYNC** (a primeira) e
**TASK-DOM** (a segunda).

### 0.3 Linha de base

```bash
docker cp api/tests/agro-armazenamento mgspa-api:/tmp/agrotest
docker exec -u www-data mgspa-api php /tmp/agrotest/run.php
docker exec mgspa-api rm -rf /tmp/agrotest
```

Esperado hoje: **17 falhas**. A bateria cria e apaga os próprios registros (`ZZTESTE`) e
termina com `limpeza: registros ZZTESTE restantes = 0`.

Como a bateria vai evoluindo: cada fase fecha um grupo de cenários. O critério de pronto de
cada fase é **esses cenários passarem e nenhum outro piorar**. Os `[INFO]` não reprovam.

| Fase | Task | Cenários que passam a `[OK]` |
|---|---|---|
| 1 | TASK-SYNC | S3, S4a, S4b, S7, S15 |
| 2 | TASK-SYNC | (front; roteiro manual) |
| 3 | TASK-170 | S2.1–S2.3, S8, S8c, S14 (S8b e S8d continuam OK) |
| 4 | TASK-DOM | S9, S10, S13, S13b, S16 |
| 5 | TASK-130 | S12 (S5/S6 continuam `[INFO]` se D1 = avisar) |

---

## Fase 1 — Servidor: carga serializada e versionada (TASK-SYNC, backend)

```bash
./backlog.sh task edit TASK-SYNC -s "In Progress" -a @<você>
```

### Causa (medida)
- `CargaService::sincronizar` faz `firstOrNew(uuid)` **sem trava**. Dois envios do mesmo uuid
  inserem juntos, e 4 de 5 terminam em 500 por `tblcarga_uuid_key` (S3).
- O payload é aplicado por cima do que está no banco, **sem saber de que versão ele partiu**.
  A cópia velha regride a etapa, zera a tara e apaga o extrato (S4a), ou reativa uma carga
  cancelada, porque o app manda `inativo: null` (S7).
- Em corrida, o modelo lido antes da trava de linha grava só os campos "sujos" dele. A linha
  fica FINALIZADA, mas o `gerarMovimento` roda com a etapa velha em memória e apaga o extrato
  (S4b: 2 em 10 rodadas).
- uuid fora do formato chega no Postgres (`uuid` é tipo `uuid`) e vira 500 (S15).

### 1.1 DDL — `api/database/agro_carga_versao.sql` (arquivo novo)

```sql
-- Versao otimista da carga: incrementada a cada gravacao no servidor. O aparelho
-- manda a versao da qual partiu; se o servidor ja estiver em outra, responde 409.
ALTER TABLE tblcarga ADD COLUMN IF NOT EXISTS versao integer NOT NULL DEFAULT 1;
COMMENT ON COLUMN tblcarga.versao IS
  'Versao otimista (sync offline do patio). Ver CargaService::sincronizar.';
```

Rodar no dev: `docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < api/database/agro_carga_versao.sql`.
**Em PROD o DDL vai antes do deploy do backend** (memória `ddl_scripts_manuais_sem_tracking`:
nada registra o que já foi aplicado, então anote no PR).

### 1.2 `Carga.php`
- `versao` em `$casts` (`integer`). **Não** colocar em `$fillable`: quem controla é o servidor.

### 1.3 `CargaService::sincronizar` — nova ordem dentro da transação

```php
return DB::transaction(function () use ($data) {
    // 1) Serializa TUDO que mexe nesta carga (mesmo uuid chegando 2x ao mesmo tempo).
    //    Trava consultiva de transacao: libera sozinha no commit/rollback.
    DB::select('select pg_advisory_xact_lock(hashtext(?))', ['carga:' . $data['uuid']]);

    // 2) So agora le o estado atual — ja serializado.
    $carga = Carga::firstOrNew(['uuid' => $data['uuid']]);

    // 3) Concorrencia otimista. `versao` ausente/null = aparelho que nunca recebeu
    //    resposta desta carga (criacao reenviada) ou app antigo: aplica, como hoje.
    $versaoCliente = $data['versao'] ?? null;
    if ($carga->exists && $versaoCliente !== null && (int) $versaoCliente !== (int) $carga->versao) {
        throw new CargaConflitoException($carga->fresh(static::WITH));
    }

    // 4) Aplica e incrementa.
    $carga->fill($data);
    $carga->versao = $carga->exists ? ((int) $carga->versao + 1) : 1;
    // ... resto igual (snapshot, save, pontos, classificacao, calcular, save, validar, gerarMovimento)
});
```

- Com a leitura depois da trava, o S4b some: o modelo em memória é o estado real.
- `CargaConflitoException` (arquivo novo em `app/Mg/Grao/`) estende `HttpException(409)`,
  com a mensagem "Esta carga foi alterada em outro aparelho." e o método `render()`
  devolvendo `{ message, carga: new CargaResource($carga) }`. É com a `carga` que o front
  resolve o conflito sem precisar de outro request.
- **Defesa do uuid (S15):** no início do `sincronizar`, se `!Str::isUuid($data['uuid'])`,
  lançar `ValidationException::withMessages(['uuid' => 'Identificador da carga inválido.'])`.
  No `CargaSincronizarRequest`, trocar `'uuid' => ['required', 'string']` por
  `['required', 'uuid']` e acrescentar `'versao' => ['nullable', 'integer', 'min:1']`.
- **`versao` e o `fill`:** como `versao` não está no `$fillable`, o `fill` ignora a chave.
  A leitura é feita direto em `$data['versao']`.

### 1.4 `inativar` / `ativar` da carga
Mesmo padrão, dentro de `DB::transaction`: trava consultiva pelo uuid, depois
`lockForUpdate()` na linha e `versao++`. Hoje eles nem estão em transação: se o
`gerarMovimento` falhar, a carga fica cancelada com extrato velho.
(`ativar` ainda ganha a validação da Fase 3.)

### 1.5 Verificação
- `docker exec -w /opt/www/MGspa/api mgspa-api ./vendor/bin/pint app/Mg/Grao` (se o repo usar;
  senão, conferir o estilo à mão).
- Bateria: **S3, S4a, S4b, S7 e S15 em `[OK]`**. S3 aceita respostas `ok` ou `409`, desde que
  sem 500 e sem duplicar.
- Teste de fumaça por HTTP: um `POST v1/carga/sincronizar` com `versao` errada tem que devolver
  **409** com `carga` no corpo.

**PARADA 1.** Mostrar o diff e o resultado da bateria. Ainda não tem roteiro de tela: o front
só muda na Fase 2, e o app atual continua funcionando porque não manda `versao`.

---

## Fase 2 — Front do pátio: envio em fila por carga e tratamento de conflito (TASK-SYNC, front)

### Causa (leitura de código)
- `carga.js::salvar` dispara `enviarCarga` **sem esperar**, e o `enviarCargasPendentes` do ciclo
  de sync pode mandar a mesma carga ao mesmo tempo. Com os blocos salvando na hora (TASK-171),
  dois salvamentos seguidos viraram rotina.
- `sincronizacao.js::enviarCarga` recebe um **objeto capturado** e, na volta, marca
  `sincronizado: 1` e regrava `classificacao` **incondicionalmente**. Se o operador editou
  enquanto o envio estava no ar, a edição nova é marcada como enviada e nunca sobe, ou é
  sobrescrita pela resposta velha.

### 2.1 `utils/carga.js::normalizarCargaDoServidor`
- Acrescentar `versao: cs.versao ?? null`.

### 2.2 `stores/carga.js::salvar`
- Cada gravação local incrementa um contador só do aparelho:
  `limpa.revisao = (atual?.revisao || 0) + 1`, lendo o registro atual do Dexie antes do `put`.
  Campo não indexado, **não precisa** de nova versão do schema Dexie.
- Preservar `versao` (vem do servidor; o form não mexe).
- Trocar `sincronizacao.enviarCarga(limpa)` por `sincronizacao.enviarCargaPorUuid(limpa.uuid)`.
  O tratamento de erro atual continua, e o 409 passa a ser tratado lá dentro (2.3).

### 2.3 `stores/sincronizacao.js`
- **Fila por uuid:** `const envioEmVoo = new Map()`. A função `enviarCargaPorUuid(uuid)`
  encadeia na promessa em voo daquele uuid, se houver, e só então executa. Nunca há dois POSTs
  da mesma carga no ar.
- **Ler do Dexie na hora de enviar:** `const carga = await db.carga.get(uuid)`. Se ela já
  estiver `sincronizado === 1`, não faz nada. Guardar `const revisaoEnviada = carga.revisao`.
  O payload inclui `versao`.
- **Na volta (200)**, dentro de `db.transaction('rw', db.carga, ...)`:
  - relê o registro; se `atual.revisao === revisaoEnviada`, aplica o patch completo de hoje +
    `versao` + `sincronizado: 1`;
  - senão (houve edição no meio), aplica **só** `codcarga` e `versao` e mantém
    `sincronizado: 0`. A edição nova sai no próximo envio da fila, já com a versão certa.
- **Na volta 409:** gravar a `carga` do corpo (normalizada, `sincronizado: 1`, `syncerro: null`)
  e guardar o que o operador tinha lançado em `conflito: { em: agoraLocal(), local: <payload enviado> }`.
  Avisar uma vez: "A carga {placa} foi alterada em outro aparelho. Ficou valendo a versão de lá;
  o que você lançou está guardado na carga." **Não** marcar `syncerro` (não é rejeição).
- `enviarCargasPendentes` passa a chamar `enviarCargaPorUuid(c.uuid)`.
- `puxarPaginasCarga` não muda: continua não tocando em pendente local.

### 2.4 Aviso na carga
- Em `CargaResumo.vue` (ou no topo do `CargaForm.vue`, o que estiver visível ao abrir a carga):
  quando `carga.conflito`, mostrar um `q-banner` com "Alterada em outro aparelho em {em}",
  um botão **Ver o que eu lancei** (dialog com o JSON formatado em campos: pesos, pontos,
  classificação) e **Dispensar**, que limpa `conflito` via `db.carga.update`.
- Formulário tocado: trocar os `q-input` que existirem por `MgInput`/`MgInputValor`
  (`CLAUDE.md` → Campos de formulário).

### 2.5 Verificação
- `cd agro && npx eslint src/stores/carga.js src/stores/sincronizacao.js src/utils/carga.js src/components/carga`
- Conferir o layout com o headless Chrome (memória `env_headless_chrome_bloqueado`).

**PARADA 2 — roteiro de teste** (`https://sistema-dev.mgpapelaria.com.br:8088/#/carga`, com
Ctrl+Shift+R antes, por causa da memória `hmr_nao_pega_components`):
1. **Dois aparelhos:** abra o pátio em duas janelas, sendo uma anônima (Dexie separado). Na janela A,
   crie uma ENTRADA, pese o PBT e sincronize. Na B, sincronize e abra a mesma carga.
2. Na A, lance a tara e finalize. Na B, **sem sincronizar**, edite a placa e salve.
   → B mostra o aviso de conflito e passa a exibir a carga FINALIZADA; o silo não perde o grão
   (Extrato → saldo do silo).
3. Na B, "Ver o que eu lancei" mostra a placa editada.
4. **Cancelada:** cancele uma carga na A; na B (cópia velha), salve qualquer campo → aviso de
   conflito e a carga continua cancelada.
5. **Edição durante o envio:** DevTools → Network → throttling "Slow 3G". Salve o bloco Pesagem
   e, logo em seguida, o bloco Classificação. Tire o throttling e sincronize → as duas edições
   estão no servidor (abrir a ficha em `#/cargas`).
6. **Offline:** DevTools → Offline, lance uma carga inteira, volte online → sobe sem erro.

Ao fim, com o OK: `./backlog.sh task edit TASK-SYNC --check-ac 1 --check-ac 2 --check-ac 3 --check-ac 4 -s Done`.
Sugestão de commit: `[FIX] TASK-SYNC Carga finalizada ou cancelada nao volta atras com copia velha de outro aparelho`
(DDL + backend + front + `api/tests/agro-armazenamento/` + o `.md` da task).

---

## Fase 3 — Trava do contrato e extrato manual (TASK-170)

```bash
./backlog.sh task edit TASK-170 -s "In Progress" -a @<você>
```

### Causa (medida)
- `validarOverloadContrato` lê o "já entregue" **sem travar o contrato**. Seis cargas ao mesmo
  tempo passaram todas: 12.000 kg num teto de 6.000 (S2, 3 de 3 rodadas).
- A mesma consulta ignora `inativo` e usa `codcarga != X`. Numa linha com `codcarga` NULL
  (todo ajuste manual) isso dá NULL e a linha fica de fora: ajuste manual de 5.000 + carga de
  4.000 passou num teto de 6.000 (S8c). O S8b só passa porque os dois defeitos se anulam.
- `CargaService::ativar` **não valida nada**: a carga reativada levou o contrato a 8.000/6.000 (S8).
- `lancarManual` ignora o papel: uma retirada de 1.000 kg levou o silo de 10.000 para 11.000 (S14).
- ExtratoPage lê `kpis.acolherkg` e `kpis.disponivelkg`, que a API não devolve (critério #1).

### 3.1 Fonte única do "entregue" — `ContratoService`

```php
/**
 * KG fisico entregue/recebido no contrato = SUM(liquido) das linhas ATIVAS do
 * extrato (automaticas + manuais). Mesma regra do withSum 'carregadokg' do
 * pesquisar() — e o numero que o operador ve como "Saldo a entregar".
 * $excetoCodcarga tira as linhas automaticas da propria carga (revalidacao).
 */
public static function entregueKg(int $codcontrato, ?int $excetoCodcarga = null): float
{
    return (float) MovimentoGrao::where('contatipo', 'CONTRATO')
        ->where('codcontrato', $codcontrato)
        ->whereNull('inativo')
        ->when($excetoCodcarga, fn ($q) => $q->where(
            fn ($w) => $w->whereNull('codcarga')->orWhere('codcarga', '!=', $excetoCodcarga)
        ))
        ->sum('liquido');
}
```

- Todos os papéis entram, como no `carregadokg`. Com a Fase 4, contrato de VENDA só aparece
  como DESTINO e de COMPRA só como ORIGEM, então isso é exatamente o entregue/recebido.
- Trocar o `withSum` do `pesquisar()` para usar o mesmo critério (já usa) e **comentar** nos dois
  lugares que um depende do outro.

### 3.2 `validarOverloadContrato`
- **Pular quando `$carga->inativo !== null`.** Cancelar nunca pode ser barrado (S8d; hoje só
  passa porque o ajuste manual não é contado).
- Considerar os pontos de **qualquer papel** ligados a contrato com teto; a mensagem não muda.
- **Travar os contratos antes de ler o entregue:**
  ```php
  $contratos = Contrato::with('Cultura')
      ->whereIn('codcontrato', array_keys($kgPorContrato))
      ->orderBy('codcontrato')      // ordem fixa = sem deadlock entre cargas
      ->lockForUpdate()
      ->get()->keyBy('codcontrato');
  ```
  A trava vale até o commit do `sincronizar`, que é onde o extrato desta carga é gravado.
  A próxima carga do mesmo contrato espera e, ao ler, já enxerga o extrato gravado.
  Ordem das travas no sistema inteiro: **carga (consultiva) → contratos (por código) →
  silos (Fase 5, se D1 mudar)**. Manter sempre essa ordem.
- `$jaOutros = ContratoService::entregueKg($cod, $carga->codcarga)`.

### 3.3 `CargaService::ativar`
Dentro da transação da Fase 1.4: `parent::ativar`, depois `$carga->load('CargaPontoS')`,
`static::validar($carga)` (422 desfaz a reativação) e por fim `gerarMovimento`.

### 3.4 Ajuste manual com sinal (critério #2)
- `MovimentoGraoService::lancarManual`: depois de arredondar, se
  `contatipo === 'UNIDADE' && papel === 'ORIGEM'`, gravar `bruto = -abs(bruto)`,
  `desconto = -abs(desconto)`, `liquido = bruto - desconto`. É a mesma regra do
  `CargaService::sinal`; extrair `CargaService::sinal` para `public static` e reutilizar.
- ExtratoPage (form do ajuste): o operador digita **quantidade positiva**. Trocar o texto
  "Líquido = bruto − desconto" por "Entrada no silo" / "Retirada do silo" conforme o papel.
  No `MovimentoGraoManualRequest`, `bruto`/`desconto` passam a `gte:0`. Para PLANTIO e
  CONTRATO, correção para baixo = estornar o lançamento errado e lançar o certo.
- **Migração (D4)** — `api/database/agro_movimento_manual_sinal.sql`:
  ```sql
  -- 1) conferir antes (levar a lista pra quem lancou):
  SELECT codmovimentograo, data, codunidadearmazenadora, liquido, observacao, codusuariocriacao
    FROM tblmovimentograo
   WHERE manual AND contatipo = 'UNIDADE' AND papel = 'ORIGEM' AND liquido > 0 AND inativo IS NULL;
  -- 2) inverter (retirada lancada positiva somou ao silo):
  UPDATE tblmovimentograo
     SET bruto = -bruto, desconto = -desconto, liquido = -liquido
   WHERE manual AND contatipo = 'UNIDADE' AND papel = 'ORIGEM' AND liquido > 0;
  ```
  Linhas com `liquido < 0` são de quem já descobriu o truque do bruto negativo e ficam como estão.

### 3.5 KPIs do Extrato (critério #1)
- `SafraService::resumoComercial`: acrescentar
  `'acolherkg' => max(0, ($producaoTotal - $colhidoTotal) * $pesosaca)` e
  `'disponivelkg' => ($producaoTotal - $contratado) * $pesosaca`, **sem** o `max(0)`, porque a
  tela pinta de vermelho quando fica negativo. Manter `disponivel` (sacas), que a
  SafraDetailPage usa.
- Conferir se `ExtratoPage.vue` já lê esses nomes (lê: linhas 105–120) e remover qualquer
  fallback morto.

### 3.6 Verificação
- Bateria: **S2.1–S2.3, S8, S8c e S14 em `[OK]`**, e S8b/S8d continuam OK.
- Roteiro de tela (`#/extrato`, safra de milho):
  1. Os cards "A colher" e "Disponível p/ negociar" mostram valor.
  2. Lance um ajuste de **retirada** de 1.000 kg no SILO → o saldo do silo cai 1.000.
  3. Num contrato com saldo de 2.000 kg, lance uma saída de 3.000 kg no pátio → a carga é
     recusada com a mesma cifra do "Saldo a entregar" mostrado no formulário.
  4. Cancele uma carga desse contrato e reative → se não couber mais, a reativação é recusada.

**PARADA 3.** Com o OK: `--check-ac` de todos os critérios da TASK-170, depois `-s Done`.
Commit: `[FIX] TASK-170 Trava do contrato serializada e fonte unica do entregue; ajuste manual com sinal`.

---

## Fase 4 — Regras de domínio da carga (TASK-DOM)

```bash
./backlog.sh task edit TASK-DOM -s "In Progress" -a @<você>
```

### 4.0 Diagnóstico ANTES de ligar as regras (dev e PROD)
Carga antiga que viole a regra passa a ser recusada **ao ser editada**. Levantar antes:
```sql
-- contrato de outra cultura
SELECT c.codcarga, c.uuid, s.codcultura AS cultura_safra, k.codcultura AS cultura_contrato
  FROM tblcarga c JOIN tblsafra s USING (codsafra)
  JOIN tblcargaponto p USING (codcarga) JOIN tblcontrato k USING (codcontrato)
 WHERE p.contatipo = 'CONTRATO' AND k.codcultura <> s.codcultura;
-- talhao de outra safra
SELECT c.codcarga, c.codsafra, pl.codsafra AS safra_talhao
  FROM tblcarga c JOIN tblcargaponto p USING (codcarga) JOIN tblplantio pl USING (codplantio)
 WHERE p.contatipo = 'PLANTIO' AND pl.codsafra <> c.codsafra;
-- combinacoes fora da matriz (D2)
SELECT c.codcarga, c.sentido, p.papel, p.contatipo, k.operacao
  FROM tblcarga c JOIN tblcargaponto p USING (codcarga) LEFT JOIN tblcontrato k USING (codcontrato)
 ORDER BY 1;
```
Se aparecer linha, levar para quem opera antes de seguir. Corrigir o dado ou aceitar que
aquela carga precise ser acertada na próxima edição.

### 4.1 Backend — `CargaService::validarPontos(Carga $carga)` (novo, chamado no `validar` sempre, em qualquer etapa)
Recusar com 422 (`pontos`) e mensagem na língua do pátio:

| Regra | Mensagem |
|---|---|
| CONTRATO com `codcultura` ≠ cultura da safra da carga | "Contrato {contrato} é de {cultura}; esta carga é de {cultura da safra}." |
| CONTRATO de VENDA como ORIGEM, ou de COMPRA como DESTINO | "Contrato de venda só pode ser destino." / "Contrato de compra só pode ser origem." |
| PLANTIO com `codsafra` ≠ `carga.codsafra` | "Talhão {talhao} é de outra safra." |
| PLANTIO como DESTINO | "Talhão só pode ser origem." |
| Combinação fora da matriz D2 por `sentido` | "Numa {sentido} a origem precisa ser …" |
| TRANSFERÊNCIA com a mesma unidade dos dois lados | "Origem e destino são o mesmo silo." |

Matriz (D2), numa constante `CargaService::PONTOS_POR_SENTIDO`:
```php
const PONTOS_POR_SENTIDO = [
    'ENTRADA'       => ['ORIGEM' => ['PLANTIO', 'CONTRATO'], 'DESTINO' => ['UNIDADE', 'CONTRATO']],
    'SAIDA'         => ['ORIGEM' => ['UNIDADE'],             'DESTINO' => ['CONTRATO']],
    'TRANSFERENCIA' => ['ORIGEM' => ['UNIDADE'],             'DESTINO' => ['UNIDADE']],
];
```
Carregar os contratos e plantios dos pontos em **uma** query cada (`whereIn`), não um por ponto.

Contrato **inativo** como ponto novo: mesma regra do silo inativo (4.3 / Fase 5).

### 4.2 Backend — pesos
Em `validar`, antes de tudo: se `pbt !== null && tara !== null && tara > pbt`, recusar com
`'tara' => 'A tara (… kg) é maior que o PBT (… kg). Confira as pesagens.'` (S16).

### 4.3 Front — o pátio não oferece o que o servidor vai recusar
- `utils/carga.js`: exportar a mesma matriz (`PONTOS_POR_SENTIDO`). O `SelectContaTipo.vue`
  filtra por `sentido` + `papel`, no lugar do filtro fixo atual (`papel === 'DESTINO'` sem PLANTIO).
- `SelectContrato.vue`: novas props `codcultura` e `operacao` (`'VENDA'|'COMPRA'`, maiúsculo,
  **usado para filtrar**, não só no rótulo). `CargaBlocoPontos.vue` passa
  `codcultura` = cultura da safra da carga, `operacao="COMPRA"` na origem e `"VENDA"` no destino.
- `SelectUnidade` na transferência: esconder, no destino, o silo escolhido na origem.
- `CargaBlocoPesagem.vue`: regra no campo da tara (`tara <= pbt`) com a mesma mensagem.
- Componentes tocados: trocar `q-input` por `MgInput`/`MgInputValor` (`CLAUDE.md`).

### 4.4 Verificação
- Bateria: **S9, S10, S13, S13b e S16 em `[OK]`**.
- `npx eslint` nos arquivos tocados.
- Roteiro de tela (`#/carga`, safra de milho):
  1. SAÍDA: o select de contrato só lista contratos de **venda de milho**.
  2. ENTRADA: a origem oferece talhão e contrato de compra; o destino, silo e contrato de venda.
  3. TRANSFERÊNCIA: o destino não oferece o silo da origem.
  4. Pesagem com tara maior que o PBT → o campo avisa e não salva.

**PARADA 4.** Com o OK: marcar os critérios, `-s Done`.
Commit: `[FIX] TASK-DOM Patio recusa contrato de outra cultura, talhao de outra safra e combinacao invalida`.

---

## Fase 5 — Saldo do silo no pátio (TASK-130)

```bash
./backlog.sh task edit TASK-130 -s "In Progress" -a @<você>
```

### Causa (medida)
- Nada confere saldo nem capacidade: 3 saídas de 8 t de um silo com 10 t deixaram −14 t
  (S5), e 500 sc entraram num silo de 100 sc (S6).
- O snapshot do pátio (`sincronizacao.js::puxarSaldos`) chama `saldos-unidades` **sem safra**
  e soma milho com soja no mesmo silo (medido: 10.000 + 7.000 = 17.000 "kg de grão").
- `saldoUnidadeOffline`/`saldosUnidades` não são usados por nenhum componente.
- Silo inativo recebe carga (S12).

### 5.1 Backend
- `MovimentoGraoService::saldosUnidades`: trocar o N+1 (um `saldo()` por unidade) por um só
  `GROUP BY codunidadearmazenadora` e devolver também `capacidadekg = capacidadesacas × pesosaca`
  da cultura da safra pedida (null quando não houver capacidade ou safra).
- **Silo inativo (S12):** em `validarPontos` (Fase 4), recusar UNIDADE inativa **só se o ponto
  for novo na carga**. Comparar com os pontos lidos antes do `sincronizarPontos`, guardados numa
  variável no `sincronizar`. Carga antiga de silo desativado continua editável.
  Mensagem: "O silo {nome} está inativo."
- D1 = avisar: **não** bloquear saldo nem capacidade no servidor. Se a decisão mudar para
  bloquear: travar as linhas de `tblunidadearmazenadora` envolvidas (`lockForUpdate`, por código,
  **depois** dos contratos) e comparar `MovimentoGraoService::saldo('UNIDADE', …, codsafra)` −
  saída ≥ 0. O S5 da bateria passa a ser regra (`registra` no lugar de `info`).

### 5.2 Front
- `sincronizacao.js::puxarSaldos(codsafra)`: passar a safra ativa. `carga.js::definirSafra` e
  `sincronizar` repassam `codsafraAtiva`. Trocar de safra refaz o snapshot.
- `saldoUnidadeOffline(cod)`: continua snapshot + `deltaPendente`, agora os dois da mesma safra.
  Corrigir também: depois de um `enviarCarga` bem-sucedido, a carga sai do `deltaPendente`, mas o
  snapshot ainda não a contém. Chamar `puxarSaldos` no fim do envio, ou manter a carga no delta
  até o próximo snapshot. **Recomendado:** `puxarSaldos` em background após o envio com sucesso.
- `CargaBlocoPontos.vue`, linha de **ORIGEM** com UNIDADE: mostrar "Saldo no silo: X sc / Y kg",
  no mesmo padrão do "Saldo a entregar" do contrato. Em vermelho quando a fatia desta carga
  passar do saldo.
- Linha de **DESTINO** com UNIDADE e capacidade conhecida: "Espaço livre: X sc", em laranja
  quando a fatia passar da capacidade.
- Ao **finalizar** (`validarFinalizacao` no `CargaForm.vue`): se alguma origem fica negativa ou
  algum destino passa da capacidade, abrir um dialog de confirmação nomeando o silo e o número
  ("O SILO 1 fica com −14.000 kg. Finalizar mesmo assim?"). Isso **não** bloqueia.
- `SelectUnidade.vue`: já usa `unidadesAtivas`; conferir que uma carga antiga com silo inativo
  ainda mostra o rótulo (fallback do `rotuloPonto`).
- Tela de Estoque (ExtratoPage, cards por silo): saldo negativo em `text-negative` com um ícone
  de alerta.

### 5.3 Verificação
- Bateria: **S12 em `[OK]`**. S5/S6 continuam `[INFO]` (D1 = avisar).
- Roteiro de tela (`#/carga`):
  1. SAÍDA do SILO: a origem mostra o saldo do silo **da safra ativa**. Trocar a safra muda o número.
  2. Lançar uma saída maior que o saldo → número em vermelho; ao finalizar, o dialog pede
     confirmação; confirmando, finaliza.
  3. Em `#/extrato`, o silo negativo aparece em vermelho.
  4. Desative um silo em Cadastros → ele some dos selects; uma carga antiga dele ainda abre e salva.

**PARADA 5.** Com o OK: marcar os critérios, `-s Done`.
Commit: `[FIX] TASK-130 Patio mostra saldo e espaco do silo e pede confirmacao ao passar`.

---

## Fechamento

1. Bateria completa: **0 falhas**, só `[INFO]` em S5, S6 e S17.
2. PROD, na ordem:
   `agro_carga_versao.sql` → deploy do api → conferir e rodar `agro_movimento_manual_sinal.sql`
   (D4) → deploy do agro.
   O backend novo aceita o app antigo (sem `versao` = comportamento de hoje), então a janela
   entre os deploys é segura. O app antigo só é protegido depois que o PWA atualizar.
3. Memória do projeto: registrar que `tblcarga.versao` é o controle de concorrência do sync e
   que a ordem de travas é carga → contratos → silos.

### Fora deste plano (já têm casa)
- Permissões do agro, qualquer login lança ajuste e finaliza carga → **TASK-129**.
- FAB "salvar sem avançar" e a UI de edição da carga → **TASK-171**.
- Pátio lento e sem atualizar sozinho → **TASK-169**.
