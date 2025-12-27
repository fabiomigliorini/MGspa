<?php

namespace Mg\Tributacao;

use Mg\NotaFiscal\NotaFiscalItemTributo;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TributacaoService
{

    /**
     * ============================================================
     * MÉTODO PÚBLICO E ESTÁTICO (COMPATIBILIDADE TOTAL)
     * ============================================================
     */
    public static function recalcularTributosItem(NotaFiscalProdutoBarra $item): int
    {
        $service = new self();

        return $service->recalcularTributosItemInterno($item);
    }

    /**
     * ============================================================
     * ORQUESTRADOR INTERNO
     * ============================================================
     */
    protected function recalcularTributosItemInterno(NotaFiscalProdutoBarra $item): int
    {
        $idsMantidos = [];

        $nota    = $item->NotaFiscal;
        $produto = $item->ProdutoBarra->Produto;

        $regras = $this->resolverRegras(
            $nota->codnaturezaoperacao,
            $nota->Pessoa->codcidade,
            $nota->Pessoa->Cidade->codestado,
            Carbon::parse($nota->emissao),
            $produto->Ncm->ncm
        );

        foreach ($regras as $regra) {

            $dados = $this->montarDadosItemTributo(
                $regra,
                $item->valortotal
            );

            // 🔹 Upsert controlado
            $itemTributo = NotaFiscalItemTributo::firstOrNew([
                'codnotafiscalprodutobarra' => $item->codnotafiscalprodutobarra,
                'codtributo'               => $regra->codtributo,
            ]);

            $itemTributo->fill($dados);
            $itemTributo->save();

            $idsMantidos[] = $itemTributo->codnotafiscalitemtributo;
        }

        $this->removerTributosNaoAplicaveis($item, $idsMantidos);

        return count($idsMantidos);
    }

    /**
     * ============================================================
     * MOTOR DE REGRAS (FONTE DA VERDADE)
     * ============================================================
     */
    protected function resolverRegras(
        int $codnaturezaoperacao,
        int $codcidade,
        int $codestado,
        Carbon $data,
        string $ncm
    ): array {
        $sql = <<<SQL
            SELECT DISTINCT ON (r.codtributo)
                r.*
            FROM tbltributacaoregra r
            WHERE
                (r.codnaturezaoperacao IS NULL OR r.codnaturezaoperacao = :codnatureza)
            AND (r.codestadodestino IS NULL OR r.codestadodestino = :codestado)
            AND (r.codcidadedestino IS NULL OR r.codcidadedestino = :codcidade)
            AND r.vigenciainicio <= :emissao
            AND (r.vigenciafim IS NULL OR r.vigenciafim >= :emissao)
            AND (r.ncm IS NULL OR :ncm LIKE r.ncm || '%')
            ORDER BY
                r.codtributo,
                LENGTH(r.ncm) DESC NULLS LAST,
                r.codcidadedestino DESC NULLS LAST,
                r.codestadodestino DESC NULLS LAST,
                r.codnaturezaoperacao DESC NULLS LAST
        SQL;

        // dd([
        //     'sql'      => $sql,
        //     'codnatureza' => $codnaturezaoperacao,
        //     'codcidade'   => $codcidade,
        //     'codestado'   => $codestado,
        //     'emissao'     => $data->toDateString(),
        //     'ncm'         => $ncm
        // ]);
        return DB::select($sql, [
            'codnatureza' => $codnaturezaoperacao,
            'codcidade'   => $codcidade,
            'codestado'   => $codestado,
            'emissao'     => $data->toDateString(),
            'ncm'         => $ncm,
        ]);
    }

    /**
     * ============================================================
     * CÁLCULO PURO (SEM ELOQUENT / SEM SQL)
     * ============================================================
     */
    protected function calcularTributo(object $regra, float $valorItem): array
    {
        if ($regra->basepercentual < 100) {
            $baseCalculada = round(
                $valorItem * ($regra->basepercentual / 100),
                2
            );

            $valorReducao = round(
                $valorItem - $baseCalculada,
                2
            );

            $percentualReducao = round(
                100 - $regra->basepercentual,
                4
            );
        } else {
            $baseCalculada     = round($valorItem, 2);
            $valorReducao      = null;
            $percentualReducao = null;
        }

        $valorTributo = round(
            $baseCalculada * ($regra->aliquota / 100),
            2
        );

        return [
            'basereducaopercentual' => $percentualReducao,
            'basereducao'           => $valorReducao,
            'base'                  => $baseCalculada,
            'aliquota'              => $regra->aliquota,
            'valor'                 => $valorTributo,
            'cst'                   => $regra->cst,
            'cclasstrib'            => $regra->cclasstrib,
            'geracredito'           => $regra->geracredito,
            'valorcredito'          => $regra->geracredito ? $valorTributo : null,
            'beneficiocodigo'       => $regra->beneficiocodigo,
            'fundamentolegal'       => $regra->observacoes,
        ];
    }

    /**
     * ============================================================
     * PONTE ENTRE REGRA + CÁLCULO
     * ============================================================
     */
    protected function montarDadosItemTributo(object $regra, float $valorItem): array
    {
        return $this->calcularTributo($regra, $valorItem);
    }

    /**
     * ============================================================
     * LIMPEZA DE TRIBUTOS QUE NÃO SE APLICAM MAIS
     * ============================================================
     */
    protected function removerTributosNaoAplicaveis(
        NotaFiscalProdutoBarra $item,
        array $idsMantidos
    ): void {
        if (empty($idsMantidos)) {
            return;
        }

        NotaFiscalItemTributo::where(
            'codnotafiscalprodutobarra',
            $item->codnotafiscalprodutobarra
        )
        ->whereNotIn(
            'codnotafiscalitemtributo',
            $idsMantidos
        )
        ->delete();
    }

    /**
     * ============================================================
     * SIMULAÇÃO (SEM PERSISTÊNCIA)
     * ============================================================
     */
    public function simular(
        int $codnaturezaoperacao,
        int $codcidade,
        int $codestado,
        string $ncm,
        float $valorVenda,
        ?Carbon $data = null
    ): array {
        $regras = $this->resolverRegras(
            $codnaturezaoperacao,
            $codcidade,
            $codestado,
            $data ?? Carbon::now(),
            $ncm
        );

        $resultado = [];

        foreach ($regras as $regra) {
            $resultado[] = $this->calcularTributo($regra, $valorVenda);
        }

        return $resultado;
    }
}
