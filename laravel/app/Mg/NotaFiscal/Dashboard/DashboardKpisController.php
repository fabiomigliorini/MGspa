<?php

namespace Mg\NotaFiscal\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardKpisController extends Controller
{
    /**
     * Converte string de modelo para código numérico
     */
    private function parseModelo($modelo)
    {
        if ($modelo === 'nfe' || $modelo === '55') {
            return 55;
        }
        if ($modelo === 'nfce' || $modelo === '65') {
            return 65;
        }
        return null; // 'ambos' ou 'all'
    }

    /**
     * GET /nota-fiscal/dashboard/kpis/gerais
     * KPIs gerais (cards do topo)
     */
    public function gerais(Request $request)
    {
        $period = (int) $request->input('period', 30);
        $modeloParam = $request->input('modelo', 'ambos');
        $modelo = $this->parseModelo($modeloParam);
        $filial = $request->input('filial', 'all');

        $where = "status IN ('AUT','ERR','DEN','CAN','INU','DIG')
              AND saida >= CURRENT_DATE - INTERVAL '{$period} days'";
        $bindings = [];

        if ($modelo !== null) {
            $where .= ' AND modelo = :modelo';
            $bindings['modelo'] = $modelo;
        }

        if ($filial !== 'all') {
            $where .= ' AND codfilial = :filial';
            $bindings['filial'] = (int) $filial;
        }

        $sql = "
            SELECT
                COUNT(*) AS total_notas,
                SUM((status = 'AUT')::int) AS qtd_autorizadas,
                SUM((status IN ('ERR','DEN'))::int) AS qtd_erro,
                SUM((status IN ('CAN', 'INU'))::int) AS qtd_canceladas
            FROM tblnotafiscal
            WHERE {$where}
        ";

        $result = DB::selectOne($sql, $bindings);

        $total = (int) ($result->total_notas ?? 0);
        $qtdAutorizadas = (int) ($result->qtd_autorizadas ?? 0);
        $qtdErro = (int) ($result->qtd_erro ?? 0);
        $qtdCanceladas = (int) ($result->qtd_canceladas ?? 0);

        return response()->json([
            'total_notas' => $total,
            'autorizadas' => [
                'quantidade' => $qtdAutorizadas,
                'percentual' => $total > 0 ? round(100.0 * $qtdAutorizadas / $total, 2) : 0,
            ],
            'erro' => [
                'quantidade' => $qtdErro,
                'percentual' => $total > 0 ? round(100.0 * $qtdErro / $total, 2) : 0,
            ],
            'canceladas' => [
                'quantidade' => $qtdCanceladas,
                'percentual' => $total > 0 ? round(100.0 * $qtdCanceladas / $total, 2) : 0,
            ],
        ]);
    }

    /**
     * GET /nota-fiscal/dashboard/kpis/por-natureza
     * KPIs agrupados por natureza de operacao (valor)
     */
    public function porNatureza(Request $request)
    {
        $period = (int) $request->input('period', 30);
        $modeloParam = $request->input('modelo', 'ambos');
        $modelo = $this->parseModelo($modeloParam);
        $filial = $request->input('filial', 'all');

        $where = "f.status = 'AUT'
              AND f.saida >= CURRENT_DATE - INTERVAL '{$period} days'";
        $bindings = [];

        if ($modelo !== null) {
            $where .= ' AND f.modelo = :modelo';
            $bindings['modelo'] = $modelo;
        }

        if ($filial !== 'all') {
            $where .= ' AND f.codfilial = :filial';
            $bindings['filial'] = (int) $filial;
        }

        $sql = "
            SELECT
                n.naturezaoperacao AS natureza,
                SUM(f.valortotal) AS valor_total
            FROM tblnotafiscal f
            JOIN tblnaturezaoperacao n
              ON n.codnaturezaoperacao = f.codnaturezaoperacao
            WHERE {$where}
            GROUP BY n.naturezaoperacao
            ORDER BY valor_total DESC
        ";

        $results = DB::select($sql, $bindings);

        return response()->json(array_map(function ($row) {
            return [
                'natureza' => $row->natureza,
                'valor_total' => (float) $row->valor_total,
            ];
        }, $results));
    }

    /**
     * GET /nota-fiscal/dashboard/kpis/por-filial
     * KPIs agrupados por filial (tabela)
     */
    public function porFilial(Request $request)
    {
        $period = (int) $request->input('period', 30);
        $modeloParam = $request->input('modelo', 'ambos');
        $modelo = $this->parseModelo($modeloParam);

        $where = "f.status IN ('AUT','ERR','DEN','CAN','INU','DIG')
              AND f.saida >= CURRENT_DATE - INTERVAL '{$period} days'";
        $bindings = [];

        if ($modelo !== null) {
            $where .= ' AND f.modelo = :modelo';
            $bindings['modelo'] = $modelo;
        }

        $sql = "
            SELECT
                f.codfilial,
                fi.filial,
                COUNT(*) AS total_notas,
                ROUND(100.0 * SUM((f.status = 'AUT')::int) / NULLIF(COUNT(*), 0), 2) AS percent_autorizadas,
                ROUND(100.0 * SUM((f.status IN ('ERR','DEN'))::int) / NULLIF(COUNT(*), 0), 2) AS percent_erro,
                ROUND(100.0 * SUM((f.status = 'CAN')::int) / NULLIF(COUNT(*), 0), 2) AS percent_canceladas,
                MAX(f.saida) FILTER (WHERE f.status = 'AUT') AS ultima_nota_emitida,
                MAX(f.saida) FILTER (WHERE f.status IN ('ERR','DEN')) AS ultima_nota_com_erro
            FROM tblnotafiscal f
            JOIN tblfilial fi
              ON fi.codfilial = f.codfilial
            WHERE {$where}
            GROUP BY f.codfilial, fi.filial
            ORDER BY fi.filial
        ";

        $results = DB::select($sql, $bindings);

        return response()->json(array_map(function ($row) {
            return [
                'codfilial' => (int) $row->codfilial,
                'filial' => $row->filial,
                'total_notas' => (int) $row->total_notas,
                'percent_autorizadas' => (float) ($row->percent_autorizadas ?? 0),
                'percent_erro' => (float) ($row->percent_erro ?? 0),
                'percent_canceladas' => (float) ($row->percent_canceladas ?? 0),
                'ultima_nota_emitida' => $row->ultima_nota_emitida,
                'ultima_nota_com_erro' => $row->ultima_nota_com_erro,
            ];
        }, $results));
    }
}
