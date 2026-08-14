<?php

namespace Mg\NotaFiscal;

use Illuminate\Support\Facades\DB;

class NotaFiscalLacunaService
{
    /**
     * Detecta lacunas na numeracao de notas fiscais dos ultimos 90 dias.
     *
     * Devolve FAIXAS de numeros consecutivos, nao numeros soltos, porque inutilizacao e
     * ato sobre um intervalo — e a tela oferece "Inutilizar 1201-1250" num clique.
     *
     * O "nf.emitida = true" do LEFT JOIN nao e detalhe: tblnotafiscal guarda TAMBEM as notas
     * de ENTRADA (compra/transferencia recebida), que carregam o numero de quem EMITIU e
     * ficam gravadas sob o NOSSO codfilial/modelo. Sem esse filtro uma entrada de terceiro
     * "ocupa" o numero e a lacuna some da tela — foi exatamente o caso da 74577 da filial 102.
     *
     * Numeros ja cobertos por inutilizacao homologada sao excluidos. Sem isso a lacuna
     * reapareceria para sempre: antes deste PR o sistema criava uma tblnotafiscal falsa
     * para "tapar" o buraco, o que era justamente o efeito colateral que se quer eliminar.
     */
    public static function detectarLacunas(): array
    {
        // Busca combinações ativas nos últimos 90 dias com range de numeração
        $combinacoes = DB::select("
            SELECT nf.codfilial, nf.serie, nf.modelo, MIN(nf.numero) as min_numero, MAX(nf.numero) as max_numero, f.filial
            FROM tblnotafiscal nf
            INNER JOIN tblfilial f ON f.codfilial = nf.codfilial
            WHERE nf.emitida = true
            AND nf.emissao >= NOW() - INTERVAL '90 days'
            AND nf.numero > 0
            GROUP BY nf.codfilial, nf.serie, nf.modelo, f.filial
            ORDER BY nf.codfilial, nf.serie, nf.modelo
        ");

        $resultado = [];

        foreach ($combinacoes as $c) {
            $lacunas = DB::select("
                SELECT s.numero
                FROM generate_series(?::int, ?::int) AS s(numero)
                LEFT JOIN tblnotafiscal nf
                    ON nf.numero = s.numero
                    AND nf.codfilial = ?
                    AND nf.serie = ?
                    AND nf.modelo = ?
                    AND nf.emitida = true
                WHERE nf.codnotafiscal IS NULL
                AND NOT EXISTS (
                    SELECT 1
                    FROM tblinutilizacao i
                    WHERE i.codfilial = ?
                    AND i.modelo = ?
                    AND i.serie = ?
                    AND i.protocolo IS NOT NULL
                    AND s.numero BETWEEN i.numeroinicial AND i.numerofinal
                )
                ORDER BY s.numero
            ", [
                $c->min_numero, $c->max_numero,
                $c->codfilial, $c->serie, $c->modelo,
                $c->codfilial, $c->modelo, $c->serie,
            ]);

            if (count($lacunas) > 0) {
                $numeros = array_map(fn($l) => (int) $l->numero, $lacunas);
                $resultado[] = [
                    'codfilial' => $c->codfilial,
                    'filial' => $c->filial,
                    'serie' => $c->serie,
                    'modelo' => $c->modelo,
                    'lacunas' => $numeros,
                    'faixas' => static::agruparEmFaixas($numeros),
                ];
            }
        }

        return $resultado;
    }

    /**
     * Agrupa numeros consecutivos em faixas: [1,2,3,7,9,10] -> 1-3, 7-7, 9-10.
     */
    public static function agruparEmFaixas(array $numeros): array
    {
        if (empty($numeros)) {
            return [];
        }

        sort($numeros);
        $faixas = [];
        $inicio = $anterior = $numeros[0];

        foreach (array_slice($numeros, 1) as $numero) {
            if ($numero === $anterior + 1) {
                $anterior = $numero;
                continue;
            }
            $faixas[] = static::faixa($inicio, $anterior);
            $inicio = $anterior = $numero;
        }
        $faixas[] = static::faixa($inicio, $anterior);

        return $faixas;
    }

    protected static function faixa(int $inicio, int $fim): array
    {
        return [
            'numeroinicial' => $inicio,
            'numerofinal' => $fim,
            'quantidade' => ($fim - $inicio) + 1,
        ];
    }
}
