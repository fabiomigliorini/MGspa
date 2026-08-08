# Deploy — NFe (estrutura de arquivos, log SEFAZ, contingência, envio assíncrono)

Ordem importa: **certificados → DDL → deploy → fumaça → migração dos arquivos (em paralelo)**.

O passo 0 (certificados) é bloqueante: sem ele a emissão para por completo. Os demais
entram com o sistema no ar, e a migração roda em janelas crescentes durante o expediente.

---

## 0. CERTIFICADOS — antes de tudo, senão a emissão para

O `CertificadoService::pfxPath()` passou a ler de `certificado/{codfilial}.pfx`. Se o código
subir antes de o `.pfx` estar lá, o `instanciaTools()` lança exceção e **nenhuma NFe é
emitida** — não é degradação de leitura, é parada total.

Não dá para mover antes (o código antigo lê de `Certs/`). Então **copia**, e as duas árvores
ficam válidas durante a transição — inclusive se for preciso reverter o deploy.

```bash
docker exec mgspa-api php artisan nfe-php:migrar-arquivos --apenas=certificado
ls -la /opt/www/Arquivos/NFePHP/certificados/
```

> **Se você já rodou este passo antes da correção de caminho**, os `.pfx` foram parar em
> `NFePHP/Arquivos/certificado/`. Basta rodar o command de novo: ele agora também procura
> ali e copia para o lugar certo (`NFePHP/certificados/`).

O command avisa se sobrar alguma filial sem certificado no destino. **Se avisar, pare** —
aquela filial não emite. Os `Certs/` antigos só saem na limpeza manual, no fim de tudo.

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
NFE_PHP_PATH='/opt/www/Arquivos/NFePHP/'
```

`REDIS_QUEUE_RETRY_AFTER` tem que ser maior que o `$timeout` do job mais longo
(`NFePHPResolverJob` = 900s). Abaixo disso a fila devolve o job ainda em execução.

`NFE_PHP_PATH` **muda**: sai o `Arquivos/` que existia dentro de `NFePHP/` e era redundante.
A árvore legada continua em `NFePHP/Arquivos/` até a migração terminar — o command sabe ler
de lá e escrever na raiz nova.

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
- Os arquivos aparecem em `/opt/www/Arquivos/NFePHP/nfce/{filial}/{ambiente}/{AAAA}/{MM}/{DD}/`.
- O card "Comunicação com a SEFAZ" na tela da nota lista a conversa, com duração.

```sql
select operacao, tentativa, cstat, duracaoms, sucesso from tblsefazcomunicacao
order by criacao desc limit 10;
```

## 5. Migração dos arquivos — em paralelo, logo após o deploy

**Não há fallback de leitura.** Enquanto a migração não passa por um arquivo, DANFE/XML
daquela nota dá 404. A **emissão não é afetada** (o código novo escreve direto na árvore
nova, e o certificado já foi para o lugar no passo 0).

Por isso roda em **janelas crescentes**: as notas que as pessoas de fato pedem saem da zona
de risco nos primeiros segundos, e o acervo antigo fica para o fim. A distribuição justifica:

| Faixa | Notas | % |
|---|---|---|
| Últimos 12 meses | 327.674 | 9,8% |
| 1 a 3 anos | 663.338 | 19,9% |
| Mais de 3 anos | 2.339.163 | **70,2%** |

```bash
# Confere o mapeamento antes (não move nada)
docker exec mgspa-api php artisan nfe-php:migrar-arquivos --fases --dry-run | head -40

# Roda as fases: 7d, 15d, 30d, 90d, 1a, 2a, 5a, tudo
docker exec -d mgspa-api php artisan nfe-php:migrar-arquivos --fases

# Acompanha
docker exec mgspa-api tail -f storage/logs/laravel-$(date +%F).log | grep -i migrar
```

Cada fase é idempotente: o que já foi movido não existe mais na origem e é pulado de
imediato, então a sobreposição entre janelas custa quase nada.

**Não rodar a exportação da contabilidade (`DominioXMLService`) enquanto a fase "todo o
acervo" não terminar** — ela lê mês fechado, que pode estar no meio da migração.

Se quiser rodar em pedaços manualmente, em vez do `--fases`:

```bash
docker exec mgspa-api php artisan nfe-php:migrar-arquivos --desde=$(date -d '7 days ago' +%F)
docker exec mgspa-api php artisan nfe-php:migrar-arquivos --desde=$(date -d '30 days ago' +%F)
# ... e assim por diante, até sem --desde
```

### O que sobrou é o relatório de erro

```bash
find /opt/www/Arquivos/NFePHP/Arquivos/NFe \
     /opt/www/Arquivos/NFePHP/Arquivos/Mdfe \
     /opt/www/Arquivos/NFePHP/Arquivos/DFe -type f | wc -l
find /opt/www/Arquivos/NFePHP/Arquivos/NFe -type f | head -50
```

Só depois de zerar (ou explicar) o resíduo é que as árvores antigas saem — **na mão, por
você**, nunca pelo command. `Certs/`, `CTe/` e `Imagens/` podem ir junto: a primeira já foi
copiada, a segunda está vazia e a terceira é resíduo de 2015 (o logo da DANFE vem de
`public_path()`).

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
