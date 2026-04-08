<?php

namespace Mg\NotaFiscal\Controle;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscalNegocioService;
use Mg\NotaFiscal\NotaFiscalTransferenciaService;

class ControleController extends Controller
{
    /**
     * GET /nota-fiscal/controle/negocios-sem-nfce
     * Lista negócios fechados sem NFC-e gerada (últimos 30 dias)
     */
    public function negociosSemNfce(Request $request)
    {
        $codfilial = $request->input('codfilial');

        $sql = "
            SELECT DISTINCT
                n.codnegocio,
                n.lancamento,
                n.codfilial,
                f.filial,
                n.valortotal,
                p.fantasia AS cliente
            FROM tblnegocio n
            INNER JOIN tblnaturezaoperacao nat ON (nat.codnaturezaoperacao = n.codnaturezaoperacao)
            INNER JOIN tblfilial f ON (f.codfilial = n.codfilial)
            INNER JOIN tblpessoa p ON (p.codpessoa = n.codpessoa)
            INNER JOIN tblnegocioprodutobarra npb ON (npb.codnegocio = n.codnegocio)
            INNER JOIN tblnegocioformapagamento nfp ON (nfp.codnegocio = n.codnegocio)
            LEFT JOIN tblnegocioprodutobarra dev ON (dev.codnegocioprodutobarradevolucao = npb.codnegocioprodutobarra)
            LEFT JOIN (
                SELECT nfpb.codnegocioprodutobarra
                FROM tblnotafiscal nf
                INNER JOIN tblnotafiscalprodutobarra nfpb ON (nfpb.codnotafiscal = nf.codnotafiscal)
                WHERE nf.emitida = true
                AND nf.nfeinutilizacao IS NULL
                AND nf.nfecancelamento IS NULL
            ) nfs ON (nfs.codnegocioprodutobarra = npb.codnegocioprodutobarra)
            WHERE n.codnegociostatus = 2
            AND nat.venda = true
            AND npb.inativo IS NULL
            AND n.lancamento >= now() - INTERVAL '30 days'
            AND dev.codnegocioprodutobarra IS NULL
            AND nfs.codnegocioprodutobarra IS NULL
            AND nfp.integracao = true
        ";

        $bindings = [];

        if ($codfilial) {
            $sql .= " AND n.codfilial = :codfilial";
            $bindings['codfilial'] = (int) $codfilial;
        }

        $sql .= " ORDER BY n.lancamento DESC";

        $results = DB::select($sql, $bindings);

        return response()->json(array_map(function ($row) {
            return [
                'codnegocio' => (int) $row->codnegocio,
                'lancamento' => $row->lancamento,
                'codfilial' => (int) $row->codfilial,
                'filial' => $row->filial,
                'valortotal' => (float) $row->valortotal,
                'cliente' => $row->cliente,
            ];
        }, $results));
    }

    /**
     * POST /nota-fiscal/controle/gerar-nfce-faltantes
     * Gera NFC-e para negócios selecionados
     */
    public function gerarNfceFaltantes(GerarNfceFaltantesRequest $request)
    {
        $sucesso = [];
        $erro = [];

        foreach ($request->codnegocio_ids as $codnegocio) {
            try {
                $negocio = Negocio::findOrFail($codnegocio);
                $nf = NotaFiscalNegocioService::gerarNotaFiscalDoNegocio($negocio, 65);
                $sucesso[] = [
                    'codnegocio' => $codnegocio,
                    'codnotafiscal' => $nf->codnotafiscal,
                ];
            } catch (\Exception $e) {
                $erro[] = [
                    'codnegocio' => $codnegocio,
                    'mensagem' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'sucesso' => $sucesso,
            'erro' => $erro,
        ]);
    }

    /**
     * POST /nota-fiscal/controle/gerar-transferencias
     * Gera notas de transferência para uma filial
     */
    public function gerarTransferencias(Request $request)
    {
        $request->validate([
            'codfilial' => 'required|integer',
        ]);

        $resultado = NotaFiscalTransferenciaService::geraTransferencias($request->codfilial);

        return response()->json($resultado);
    }
}
