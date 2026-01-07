<?php

namespace Mg\NotaFiscal\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardGraficosController extends Controller
{
    /**
     * GET /nota-fiscal/dashboard/graficos/volume-mensal
     * Volume de notas por mes (ultimos 12 meses)
     */
    public function volumeMensal()
    {
        $sql = "
            SELECT
                TO_CHAR(date_trunc('month', saida), 'YYYY-MM') AS mes,
                SUM((modelo = 55)::int) AS nfe,
                SUM((modelo = 65)::int) AS nfce
            FROM tblnotafiscal
            WHERE status IN ('AUT','ERR','DEN','CAN','INU','DIG')
              AND saida >= date_trunc('month', CURRENT_DATE) - INTERVAL '11 months'
            GROUP BY date_trunc('month', saida)
            ORDER BY date_trunc('month', saida)
        ";

        $results = DB::select($sql);

        return response()->json(array_map(function ($row) {
            return [
                'mes' => $row->mes,
                'nfe' => (int) $row->nfe,
                'nfce' => (int) $row->nfce,
            ];
        }, $results));
    }

    /**
     * GET /nota-fiscal/dashboard/graficos/erro-por-filial
     * Percentual de erro por filial
     */
    public function erroPorFilial(Request $request)
    {
        $period = (int) $request->input('period', 30);
        $modelo = $request->input('modelo', 'all');

        $where = "f.status IN ('AUT','ERR','DEN','CAN','INU','DIG')
              AND f.saida >= CURRENT_DATE - INTERVAL '{$period} days'";
        $bindings = [];

        if ($modelo !== 'all') {
            $where .= ' AND f.modelo = :modelo';
            $bindings['modelo'] = (int) $modelo;
        }

        $sql = "
            SELECT
                f.codfilial,
                fi.filial,
                ROUND(
                    100.0 * SUM((f.status IN ('ERR','DEN'))::int) / NULLIF(COUNT(*), 0),
                    2
                ) AS percent_erro
            FROM tblnotafiscal f
            JOIN tblfilial fi
              ON fi.codfilial = f.codfilial
            WHERE {$where}
            GROUP BY f.codfilial, fi.filial
            ORDER BY percent_erro DESC
        ";

        $results = DB::select($sql, $bindings);

        return response()->json(array_map(function ($row) {
            return [
                'codfilial' => (int) $row->codfilial,
                'filial' => $row->filial,
                'percent_erro' => (float) ($row->percent_erro ?? 0),
            ];
        }, $results));
    }
}
