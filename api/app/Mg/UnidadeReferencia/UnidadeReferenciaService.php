<?php

namespace Mg\UnidadeReferencia;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mg\MgService;

class UnidadeReferenciaService extends MgService
{
    const SEFAZ_UPF_URL = 'https://www5.sefaz.mt.gov.br/upf-mt';

    const MESES = [
        'janeiro' => 1, 'fevereiro' => 2, 'marco' => 3, 'março' => 3, 'abril' => 4,
        'maio' => 5, 'junho' => 6, 'julho' => 7, 'agosto' => 8, 'setembro' => 9,
        'outubro' => 10, 'novembro' => 11, 'dezembro' => 12,
    ];

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = UnidadeReferencia::query()->with('Estado', 'Cidade');

        if (!empty($filter['codigo'])) {
            $qry->where('codigo', 'ilike', "%{$filter['codigo']}%");
        }
        if (!empty($filter['ente'])) {
            $qry->where('ente', $filter['ente']);
        }
        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['codigo']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function detalhe(int $cod): UnidadeReferencia
    {
        return UnidadeReferencia::with([
            'Estado',
            'Cidade',
            'UnidadeReferenciaValorS' => fn ($q) => $q->orderByDesc('competencia'),
        ])->findOrFail($cod);
    }

    /** Upsert de um valor por competência (1º dia do mês). */
    public static function salvarValor(int $codunidadereferencia, string $competencia, float $valor): UnidadeReferenciaValor
    {
        $comp = Carbon::parse($competencia)->startOfMonth()->toDateString();
        $reg = UnidadeReferenciaValor::firstOrNew([
            'codunidadereferencia' => $codunidadereferencia,
            'competencia' => $comp,
        ]);
        $reg->valor = $valor;
        $reg->save();
        return $reg;
    }

    /**
     * Importa os valores da UPF-MT do site da SEFAZ-MT (best-effort). O HTML do
     * portal pode mudar — por isso a importação é tolerante: extrai pares
     * "mês/ano + valor" e faz upsert. Falha não lança (loga e segue), pois roda
     * em cron. Retorna a lista de competências importadas/atualizadas.
     *
     * ATENÇÃO: o parser depende do layout do portal; valide o resultado e use o
     * CRUD manual como caminho confiável.
     */
    public static function importarUpfMt(): array
    {
        $unidade = UnidadeReferencia::where('codigo', 'UPF')
            ->where('codestado', 8956)
            ->first();
        if (!$unidade) {
            Log::warning('UPF-MT: unidade de referência não cadastrada; importação abortada.');
            return [];
        }

        try {
            $resp = Http::timeout(30)->withoutVerifying()->get(self::SEFAZ_UPF_URL);
            if (!$resp->ok()) {
                Log::warning('UPF-MT: SEFAZ retornou ' . $resp->status());
                return [];
            }
            $html = $resp->body();
        } catch (\Throwable $e) {
            Log::warning('UPF-MT: falha ao buscar SEFAZ: ' . $e->getMessage());
            return [];
        }

        $importados = [];
        foreach (self::extrairValores($html) as $comp => $valor) {
            self::salvarValor($unidade->codunidadereferencia, $comp, $valor);
            $importados[] = $comp;
        }
        Log::info('UPF-MT: importadas ' . count($importados) . ' competências.', $importados);
        return $importados;
    }

    /**
     * Extrai pares competência(Y-m-01) => valor do HTML. Procura padrões como
     * "Janeiro/2026 ... 254,36" ou "Janeiro de 2026 R$ 254,36".
     */
    public static function extrairValores(string $html): array
    {
        $texto = strip_tags($html);
        $texto = preg_replace('/\s+/u', ' ', mb_strtolower($texto));
        $meses = implode('|', array_keys(self::MESES));
        $out = [];

        // mês <sep> ano <ate 40 chars> valor (12,34 ou 1.234,56)
        $re = "/($meses)\D{0,8}(\d{4})[^\d]{0,40}?(\d{1,3}(?:\.\d{3})*,\d{2})/u";
        if (preg_match_all($re, $texto, $m, PREG_SET_ORDER)) {
            foreach ($m as $g) {
                $mes = self::MESES[$g[1]] ?? null;
                if (!$mes) {
                    continue;
                }
                $ano = (int) $g[2];
                $valor = (float) str_replace(['.', ','], ['', '.'], $g[3]);
                if ($valor <= 0 || $ano < 2000 || $ano > 2100) {
                    continue;
                }
                $comp = sprintf('%04d-%02d-01', $ano, $mes);
                $out[$comp] = $valor; // dedup por competência (último vence)
            }
        }
        ksort($out);
        return $out;
    }
}
