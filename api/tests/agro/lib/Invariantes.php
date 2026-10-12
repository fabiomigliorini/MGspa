<?php

namespace AgroBateria;

use Illuminate\Support\Facades\DB;

/**
 * As invariantes de saldo, lidas do conferencia.sql — o MESMO SQL que roda só
 * leitura na PROD, então a bateria e a conferência nunca divergem.
 *
 * Escopo 'zz': só a massa ZZTESTE (o que a bateria criou). Escopo 'tudo': o
 * banco inteiro (o que o `conferir` e o psql fazem).
 */
final class Invariantes
{
    /** @return array<int, array{id:string, nivel:string, titulo:string, sql:string}> */
    public static function ler(): array
    {
        $texto = file_get_contents(__DIR__ . '/../conferencia.sql');
        $blocos = [];
        $atual = null;
        foreach (preg_split('/\R/', $texto) as $linha) {
            if (preg_match('/^-- @(\S+)\s+(FALHA|INFO)\s+(.*)$/', $linha, $m)) {
                if ($atual) {
                    $blocos[] = $atual;
                }
                $atual = ['id' => $m[1], 'nivel' => $m[2], 'titulo' => trim($m[3]), 'sql' => ''];
                continue;
            }
            if ($atual === null || str_starts_with(ltrim($linha), '\\')) {
                continue; // cabeçalho do arquivo e meta-comandos do psql
            }
            $atual['sql'] .= $linha . "\n";
        }
        if ($atual) {
            $blocos[] = $atual;
        }
        foreach ($blocos as &$b) {
            $b['sql'] = rtrim(trim($b['sql']), ';');
        }
        return $blocos;
    }

    public static function escopo(string $sql, string $escopo): string
    {
        if ($escopo !== 'zz') {
            return $sql;
        }
        $zz = "'" . Massa::PREFIXO . "%'";
        $sql = preg_replace(
            '#/\*ESCOPO:([\w.]+)\*/#',
            "and \$1 in (select codsafra from tblsafra where safra like {$zz})",
            $sql
        );
        return preg_replace(
            '#/\*ESCOPO_CULTURA:([\w.]+)\*/#',
            "and \$1 in (select codcultura from tblcultura where cultura like {$zz})",
            $sql
        );
    }

    /**
     * Roda todas e reporta. Em 'tudo', numa transação SÓ LEITURA.
     *
     * @return array<string, int> id => violações
     */
    public static function rodar(Relatorio $r, string $escopo, string $prefixo = ''): array
    {
        $resultado = [];
        $executar = function () use ($r, $escopo, $prefixo, &$resultado) {
            foreach (static::ler() as $inv) {
                $id = $prefixo . $inv['id'];
                try {
                    $linhas = DB::select(static::escopo($inv['sql'], $escopo));
                } catch (\Throwable $e) {
                    $r->erro($id, $inv['titulo'], 'SQL: ' . mb_substr($e->getMessage(), 0, 200));
                    continue;
                }
                $n = count($linhas);
                $resultado[$inv['id']] = $n;
                if ($n === 0) {
                    $r->ok($id, $inv['titulo']);
                    continue;
                }
                $amostra = array_slice(array_map(fn ($l) => array_values((array) $l), $linhas), 0, 8);
                $cab = array_keys((array) $linhas[0]);
                $txt = $n . ($n === 1 ? ' linha' : ' linhas');
                $inv['nivel'] === 'INFO' ? $r->info($id, $inv['titulo'], $txt) : $r->falha($id, $inv['titulo'], $txt);
                $r->tabela("{$id} — amostra", $cab, $amostra);
            }
        };

        if ($escopo === 'tudo') {
            DB::transaction(function () use ($executar) {
                DB::statement('set transaction read only');
                $executar();
            });
        } else {
            $executar();
        }
        return $resultado;
    }
}
