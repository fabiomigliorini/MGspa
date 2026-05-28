<?php

namespace Mg\NotaFiscal;

use Exception;
use Illuminate\Support\Facades\DB;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\Operacao;
use Mg\NFePHP\NFePHPService;

class NotaFiscalLacunaService
{
    /**
     * Detecta lacunas na numeração de notas fiscais dos últimos 90 dias
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
                WHERE nf.codnotafiscal IS NULL
                ORDER BY s.numero
            ", [$c->min_numero, $c->max_numero, $c->codfilial, $c->serie, $c->modelo]);

            if (count($lacunas) > 0) {
                $resultado[] = [
                    'codfilial' => $c->codfilial,
                    'filial' => $c->filial,
                    'serie' => $c->serie,
                    'modelo' => $c->modelo,
                    'lacunas' => array_map(fn($l) => $l->numero, $lacunas),
                ];
            }
        }

        return $resultado;
    }

    /**
     * Cria registro de nota fiscal para um número saltado e inutiliza na SEFAZ
     */
    public static function criarParaInutilizar(int $codfilial, int $serie, int $modelo, int $numero, string $justificativa): object
    {
        // Verifica se já existe registro com esse número
        $existente = NotaFiscal::where('codfilial', $codfilial)
            ->where('serie', $serie)
            ->where('modelo', $modelo)
            ->where('numero', $numero)
            ->first();

        if ($existente) {
            if (!empty($existente->nfeinutilizacao)) {
                throw new Exception("Número {$numero} já está inutilizado.");
            }
            $nf = $existente;
        } else {
            // Busca emissão/saída da nota anterior
            $notaAnterior = NotaFiscal::where('codfilial', $codfilial)
                ->where('serie', $serie)
                ->where('modelo', $modelo)
                ->where('numero', '<', $numero)
                ->orderBy('numero', 'desc')
                ->first();

            $emissao = $notaAnterior ? $notaAnterior->emissao : now();
            $saida = $notaAnterior ? $notaAnterior->saida : now();

            // Natureza de operação de venda
            $codnaturezaoperacao = env('MERCOS_CODNATUREZAOPERACAO');
            if (!$codnaturezaoperacao) {
                throw new Exception('MERCOS_CODNATUREZAOPERACAO não configurado no .env.');
            }

            // Busca estoque local da filial
            $filial = Filial::findOrFail($codfilial);
            $estoqueLocal = $filial->EstoqueLocalS()->first();
            if (!$estoqueLocal) {
                throw new Exception('Estoque local não encontrado para a filial.');
            }

            // Cria registro mínimo
            $nf = NotaFiscal::create([
                'codfilial' => $codfilial,
                'serie' => $serie,
                'modelo' => $modelo,
                'numero' => $numero,
                'emitida' => true,
                'codpessoa' => 1, // Consumidor
                'codnaturezaoperacao' => $codnaturezaoperacao,
                'codoperacao' => Operacao::SAIDA,
                'codestoquelocal' => $estoqueLocal->codestoquelocal,
                'emissao' => $emissao,
                'saida' => $saida,
            ]);
        }

        // Inutiliza usando fluxo existente
        return NFePHPService::inutilizar($nf, $justificativa);
    }
}
