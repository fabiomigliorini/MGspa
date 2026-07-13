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
        $qry = UnidadeReferencia::query()->with([
            'Estado',
            'Cidade',
            'UnidadeReferenciaValorS' => fn ($q) => $q->orderByDesc('competencia'),
        ]);

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

    /**
     * Upsert de um valor por competência (1º dia do mês). USO EXCLUSIVO do
     * import da SEFAZ (refresh dos valores oficiais) — pode sobrescrever. O
     * lançamento MANUAL usa criarValor/atualizarValor (que NÃO sobrescrevem
     * silenciosamente).
     */
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
     * Cria um valor NOVO (lançamento manual). Rejeita (409) se já existe valor
     * para a competência — o usuário deve editar o registro existente, nunca
     * sobrescrever sem querer.
     */
    public static function criarValor(int $codunidadereferencia, string $competencia, float $valor): UnidadeReferenciaValor
    {
        $comp = Carbon::parse($competencia)->startOfMonth();
        $existe = UnidadeReferenciaValor::where('codunidadereferencia', $codunidadereferencia)
            ->where('competencia', $comp->toDateString())
            ->exists();
        if ($existe) {
            abort(409, 'Já existe um valor lançado para ' . $comp->format('m/Y')
                . '. Edite o registro existente.');
        }
        $reg = new UnidadeReferenciaValor();
        $reg->codunidadereferencia = $codunidadereferencia;
        $reg->competencia = $comp->toDateString();
        $reg->valor = $valor;
        $reg->save();
        return $reg;
    }

    /** Atualiza um valor existente; impede colidir com outra competência. */
    public static function atualizarValor(int $codunidadereferencia, int $codvalor, string $competencia, float $valor): UnidadeReferenciaValor
    {
        $reg = UnidadeReferenciaValor::where('codunidadereferencia', $codunidadereferencia)
            ->findOrFail($codvalor);
        $comp = Carbon::parse($competencia)->startOfMonth();
        $colide = UnidadeReferenciaValor::where('codunidadereferencia', $codunidadereferencia)
            ->where('competencia', $comp->toDateString())
            ->where('codunidadereferenciavalor', '!=', $codvalor)
            ->exists();
        if ($colide) {
            abort(409, 'Já existe um valor lançado para ' . $comp->format('m/Y') . '.');
        }
        $reg->competencia = $comp->toDateString();
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
            // O portal da SEFAZ-MT tem WAF que derruba a conexão (cURL 56) sem um
            // User-Agent de navegador — por isso mandamos um explícito.
            $resp = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                        . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
                ])
                ->get(self::SEFAZ_UPF_URL);
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
     * Extrai pares competência(Y-m-01) => valor do HTML da SEFAZ-MT.
     * Layout atual (2026): o ano ativo aparece UMA vez, seguido da lista com o
     * VALOR ANTES do mês, ex.:
     *   "2026 r$ 254,36 - janeiro r$ 255,20 - fevereiro r$ 256,04 - março ..."
     * (o portal carrega só o ano ativo; os demais vêm via JS ao clicar.)
     */
    public static function extrairValores(string $html): array
    {
        $texto = strip_tags($html);
        $texto = preg_replace('/\s+/u', ' ', mb_strtolower($texto));
        $meses = implode('|', array_keys(self::MESES));
        $out = [];

        // Ano ativo = os 4 dígitos imediatamente antes da lista "r$ ...".
        if (!preg_match('/(\d{4})\s+r\$/u', $texto, $anoMatch)) {
            return $out;
        }
        $ano = (int) $anoMatch[1];
        if ($ano < 2000 || $ano > 2100) {
            return $out;
        }

        // Pares "r$ <valor> - <mês>" (valor 12,34 ou 1.234,56 antes do mês).
        $re = "/r\\\$\\s*(\\d{1,3}(?:\\.\\d{3})*,\\d{2})\\s*-\\s*($meses)/u";
        if (preg_match_all($re, $texto, $m, PREG_SET_ORDER)) {
            foreach ($m as $g) {
                $mes = self::MESES[$g[2]] ?? null;
                if (!$mes) {
                    continue;
                }
                $valor = (float) str_replace(['.', ','], ['', '.'], $g[1]);
                if ($valor <= 0) {
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
