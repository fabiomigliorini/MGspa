# NFe — operação

Notas do subsistema de NFe/NFC-e/MDF-e depois da reorganização de agosto/2026:
estrutura de arquivos, log de comunicação com a SEFAZ, contingência e envio assíncrono.

## Estrutura de arquivos

Raiz em `NFE_PHP_PATH` (`/opt/www/Arquivos/NFePHP/`), definida em
[NFePHPPathService.php](../app/Mg/NFePHP/NFePHPPathService.php), que documenta a árvore
inteira. Em resumo:

```
{tipo}/{codfilial}/{ambiente}/{AAAA}/{MM}/{DD}/{arquivo}
```

com `tipo` ∈ `nfe`, `nfce`, `mdfe`, `inutilizacoes`, `dfe`, `conversas`, mais
`certificados/{codfilial}.pfx` na raiz.

O estado do documento vai no **sufixo do arquivo** (`-assinado`, `-proc`, `-cancelado`…),
nunca em pasta: tudo de uma mesma nota fica no mesmo diretório.

### Permissões

`docker exec` roda como **root**; o `php-fpm pool www` e os workers rodam como **uid 82**.
Diretório criado por root sai `drwxr-sr-x`, sem escrita para o grupo — e a emissão falha ao
gravar o XML naquela pasta.

Qualquer comando que crie diretório nessa árvore roda com `-u 82:82`. Para corrigir depois
do estrago existe o `permissoes.sh` na própria raiz (`chmod 2775` nas pastas — o setgid é o
que faz subpasta nova herdar o grupo).

## Tabelas

Os três DDL são aditivos e já estão aplicados em produção:

| Arquivo | O que criou |
|---|---|
| [sefaz_comunicacao.sql](sefaz_comunicacao.sql) | `tblsefazcomunicacao` — uma linha por conversa com a SEFAZ |
| [nfce_contingencia.sql](nfce_contingencia.sql) | `contingenciaautomatica` / `contingenciatolerancia` na empresa, `tpemis` na nota |
| [inutilizacao.sql](inutilizacao.sql) | `tblinutilizacao` (com backfill a partir de `tblnotafiscal.nfeinutilizacao`) |

## Log de comunicação com a SEFAZ

[SefazLogService](../app/Mg/NFePHP/SefazLogService.php) abre a linha **antes** da chamada e
completa depois. Duas razões: a conversa aparece na tela enquanto ainda está acontecendo, e
se o processo morrer no meio (timeout do FPM, kill) a tentativa deixa rastro.

O par request/response vai gzipado para `conversas/`, árvore separada de propósito: a
retenção é de 2 anos contra "para sempre" do arquivo fiscal. Quem apaga é o
`nfe-php:limpar-conversas`, agendado 03:20, com guard que recusa qualquer caminho sem
`/conversas/`.

```sql
select operacao, tentativa, cstat, duracaoms, sucesso, criacao
from tblsefazcomunicacao order by criacao desc limit 20;
```

## Contingência automática — ainda não ligada

Nasce `false` de propósito, e continua assim. Ligar exige antes calibrar a tolerância com
latência real, não com chute:

```sql
select date_trunc('hour', criacao) h, count(*),
       round(avg(duracaoms)) media, max(duracaoms) pior,
       count(*) filter (where not sucesso) erros
from tblsefazcomunicacao
where criacao >= now() - interval '48 hours'
group by 1 order by 1 desc;
```

A latência observada até agora é de **164 a 376 ms**, contra a tolerância padrão de 15 s —
folga grande demais para calibrar com ela. Espere a `tblsefazcomunicacao` registrar um
período **ruim** de SEFAZ; é esse número que interessa. Só então ligue
`contingenciaautomatica`, empresa por empresa, na tela da Empresa (app pessoas).

O gatilho está em [ContingenciaService](../app/Mg/NFePHP/ContingenciaService.php): 10
emissões consecutivas acima da tolerância entram em contingência, 20 abaixo dela saem.
Nunca usa o `sefazStatus` — ele responde OK em situação que na prática falha.

## Se algo travar

| Situação | O que fazer |
|---|---|
| Contingência automática oscilando | `contingenciaautomatica = false` na empresa. Sai na hora, sem deploy. |
| Envio travado numa nota | O progresso vive no Redis com TTL de 1h: `Cache::forget("nfe:envio:{cod}")`. |
| Erro ao gravar XML | Pasta criada por root — ver *Permissões* acima. |

`REDIS_QUEUE_RETRY_AFTER=960` precisa continuar maior que o `$timeout` do job mais longo
(`NFePHPResolverJob` = 900s). Abaixo disso a fila devolve o job ainda em execução.

## Pontos de atenção conhecidos

- **QR Code 3.0 (NT 2025.001)**: não foi tocado. Se a SEFAZ-MT já exigir a versão 3.00,
  checar **antes** de ligar a contingência automática — em emissão off-line, valor
  divergente no QR Code faz a nota ser rejeitada na transmissão.
- **`nfeinutilizacao` serve a duas coisas**: o `vincularProtocoloDenegacao` grava o
  protocolo de denegação nessa mesma coluna, e o backfill herdou a ambiguidade (documentado
  em [inutilizacao.sql](inutilizacao.sql)). Inócuo na prática.
- **`ReprocessarPeriodoJob`** (`$timeout = 1800`) segue acima do `retry_after` de 960. Já
  estava quebrado com 90; melhorou, não foi resolvido.
- **`Mg\NotaFiscalTerceiro`** tem uma camada de service/controller órfã (nenhuma rota,
  nenhum chamador) cujo `NotaFiscalTerceiroPathService` aponta para a árvore `DistDFe/`.
  Só os *models* do namespace estão em uso, como relações. Candidato a remoção.
- **`legado/`** guarda o que o classificador não conseguiu interpretar na reorganização.
  Retenção ainda não decidida.
