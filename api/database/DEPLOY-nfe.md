# Deploy — NFe (estrutura de arquivos, log SEFAZ, contingência, envio assíncrono)

Commits `50f1b1898` + `0e1a14821`. Ordem importa: **DDL → deploy → testes → migração de arquivos**.
A migração dos arquivos é a única etapa que exige janela; o resto entra com o sistema no ar.

---

## 1. DDL (antes do deploy do código)

Os três são aditivos — só criam tabela e coluna, não alteram nada existente.

```bash
cd /opt/www/MGspa/api
docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < database/sefaz_comunicacao.sql
docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < database/nfce_contingencia.sql
docker exec -i mgdb-mgdb-1 psql -U mgsis -d mgsis < database/inutilizacao.sql
```

**Conferência obrigatória do backfill** — os dois números TÊM que bater:

```bash
docker exec mgdb-mgdb-1 psql -U mgsis -d mgsis -c \
"select (select count(*) from tblinutilizacao) backfill,
        (select count(*) from tblnotafiscal where nfeinutilizacao is not null) origem;"
```

Se não bater, **pare**: o `detectarLacunas` voltaria a oferecer número já inutilizado e
alguém poderia reinutilizar por cima.

## 2. `.env` da API

```
REDIS_QUEUE_RETRY_AFTER=960
```

Tem que ser maior que o `$timeout` do job mais longo (`NFePHPResolverJob` = 900s). Abaixo
disso a fila devolve o job ainda em execução.

## 3. Deploy do código

```bash
cd /opt/www/MGspa && git pull
docker exec mgspa-api php artisan config:clear
docker exec mgspa-api php artisan route:clear
docker exec mgspa-api php artisan queue:restart     # os workers PRECISAM reiniciar
docker exec mgspa-api php artisan tinker --execute="echo config('queue.connections.redis.retry_after');"   # 960
```

Build dos fronts: `notas`, `negocios`, `pessoas` (o `contas` só consome o componente
compartilhado, mas rebuilda junto se o pipeline for único).

## 4. Fumaça, com o sistema no ar

```bash
docker exec mgspa-api php artisan route:list | grep -E "inutilizacao|contingencia|sefaz"
docker logs -f mgspa-api-worker-1
```

Emitir **uma** NFC-e de teste e conferir:

- O toast evolui "Criando arquivo XML" → "Enviando para a SEFAZ" → verde, num Notify só.
- No devtools: **1 POST** `/enviar` + vários **GET** `/enviar` de 3 em 3s, nenhum passando de 15s.
- Os arquivos aparecem em `nfce/{filial}/{ambiente}/{AAAA}/{MM}/{DD}/`.
- O card "Comunicação com a SEFAZ" na tela da nota lista a conversa, com duração.

```sql
select operacao, tentativa, cstat, duracaoms, sucesso from tblsefazcomunicacao
order by criacao desc limit 10;
```

## 5. Migração dos arquivos — FIM DE SEMANA

**Não há fallback de leitura.** Enquanto roda, DANFE/XML de nota cujo arquivo ainda não foi
movido dá 404. A **emissão não é afetada** (o código novo escreve direto na árvore nova).

**Não rodar a exportação da contabilidade (`DominioXMLService`) durante a janela.**

```bash
# 5.1 dimensionar
find /opt/www/Arquivos/NFePHP/Arquivos/NFe -type f | wc -l
du -sh /opt/www/Arquivos/NFePHP/Arquivos/

# 5.2 amostra e conferência do mapeamento
docker exec mgspa-api php artisan nfe-php:migrar-arquivos --limite=100 --dry-run
docker exec mgspa-api php artisan nfe-php:migrar-arquivos --limite=100
find /opt/www/Arquivos/NFePHP/Arquivos/nfe /opt/www/Arquivos/NFePHP/Arquivos/nfce -type f | head

# 5.3 lote completo, em background
docker exec -d mgspa-api php artisan nfe-php:migrar-arquivos
docker exec mgspa-api tail -f storage/logs/laravel-$(date +%F).log | grep -i migrar

# 5.4 O QUE SOBROU NA ÁRVORE ANTIGA É O RELATÓRIO DE ERRO
find /opt/www/Arquivos/NFePHP/Arquivos/NFe \
     /opt/www/Arquivos/NFePHP/Arquivos/Mdfe \
     /opt/www/Arquivos/NFePHP/Arquivos/DFe -type f | wc -l
find /opt/www/Arquivos/NFePHP/Arquivos/NFe -type f | head -50
```

Só depois de zerar (ou explicar) o resíduo é que as árvores antigas saem — **na mão, por
você**, nunca pelo command. `CTe/` e `Imagens/` podem ir junto: a primeira está vazia e a
segunda é resíduo de 2015 (o logo da DANFE vem de `public_path()`).

## 6. Depois do deploy, com calma

**Não ligue a contingência automática no primeiro dia.** Ela nasce `false` de propósito.
Deixe a `tblsefazcomunicacao` juntar alguns dias de dado real e calibre a tolerância com ele:

```sql
select date_trunc('hour', criacao) h, count(*),
       round(avg(duracaoms)) media, max(duracaoms) pior,
       count(*) filter (where not sucesso) erros
from tblsefazcomunicacao
where criacao >= now() - interval '48 hours'
group by 1 order by 1 desc;
```

Só então, empresa por empresa, ligue `contingenciaautomatica` na tela da Empresa (app
pessoas) com a tolerância que os números indicarem.

---

## Rollback

| Situação | O que fazer |
|---|---|
| Código com problema, arquivos **ainda não migrados** | `git revert` + `queue:restart`. As tabelas novas ficam sem uso, inofensivas. |
| Código com problema, arquivos **já migrados** | Não reverter só o código: o antigo lê da árvore velha, que está vazia. Corrigir para frente. |
| Contingência automática oscilando | `contingenciaautomatica = false` na empresa. Sai do automático na hora, sem deploy. |
| Envio travado numa nota | O progresso vive no Redis com TTL de 1h: `Cache::forget("nfe:envio:{cod}")`. |

## Pontos de atenção conhecidos

- **QR Code 3.0 (NT 2025.001)**: não foi tocado neste PR. Se a SEFAZ-MT já exigir a versão
  3.00, checar **antes** de ligar a contingência automática — em emissão off-line, valor
  divergente no QR Code faz a nota ser rejeitada na transmissão.
- **`NotaFiscalTerceiroPathService`** (árvore `DistDFe/`) ficou fora do refactor: lê por NSU
  sem data, incompatível com o particionamento por dia. Continua funcionando como está.
- **`nfeinutilizacao` serve a duas coisas**: o `vincularProtocoloDenegacao` grava o protocolo
  de denegação nessa coluna. O backfill herda a ambiguidade (documentado em
  `inutilizacao.sql`). É inócuo na prática.
- **`ReprocessarPeriodoJob`** (`$timeout = 1800`) segue acima do `retry_after` de 960. Já
  estava quebrado com 90; melhorou, não foi resolvido. Fora do escopo.
