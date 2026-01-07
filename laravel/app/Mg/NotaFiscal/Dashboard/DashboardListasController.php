<?php

namespace Mg\NotaFiscal\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardListasController extends Controller
{
    /**
     * GET /nota-fiscal/dashboard/listas/erro
     * Lista notas com erro (ERR ou DEN)
     */
    public function erro()
    {
        $sql = "
            SELECT
                f.codnotafiscal,
                f.numero,
                f.serie,
                f.modelo,
                f.valortotal,
                fi.filial,
                f.status,
                f.saida AS data
            FROM tblnotafiscal f
            JOIN tblfilial fi
              ON fi.codfilial = f.codfilial
            WHERE f.status IN ('ERR','DEN')
            ORDER BY f.criacao DESC
            LIMIT 20
        ";

        $results = DB::select($sql);

        return response()->json($this->formatList($results));
    }

    /**
     * GET /nota-fiscal/dashboard/listas/canceladas-inutilizadas
     * Lista notas canceladas e inutilizadas
     */
    public function canceladasInutilizadas()
    {
        $sql = "
            SELECT
                f.codnotafiscal,
                f.numero,
                f.serie,
                f.modelo,
                f.valortotal,
                fi.filial,
                f.status,
                f.saida AS data
            FROM tblnotafiscal f
            JOIN tblfilial fi
              ON fi.codfilial = f.codfilial
            WHERE f.status IN ('CAN','INU')
            ORDER BY f.saida DESC
            LIMIT 20
        ";

        $results = DB::select($sql);

        return response()->json($this->formatList($results));
    }

    /**
     * GET /nota-fiscal/dashboard/listas/digitacao
     * Lista notas em digitacao
     */
    public function digitacao()
    {
        $sql = "
            SELECT
                f.codnotafiscal,
                f.numero,
                f.serie,
                f.modelo,
                f.valortotal,
                fi.filial,
                f.status,
                f.saida AS data
            FROM tblnotafiscal f
            JOIN tblfilial fi
              ON fi.codfilial = f.codfilial
            WHERE f.status = 'DIG'
            ORDER BY f.criacao DESC
            LIMIT 20
        ";

        $results = DB::select($sql);

        return response()->json($this->formatList($results));
    }

    /**
     * Formata lista de notas para resposta JSON
     */
    private function formatList(array $results): array
    {
        return array_map(function ($row) {
            return [
                'codnotafiscal' => (int) $row->codnotafiscal,
                'numero' => $row->numero ? (int) $row->numero : null,
                'serie' => (int) $row->serie,
                'modelo' => (int) $row->modelo,
                'valortotal' => (float) $row->valortotal,
                'filial' => $row->filial,
                'status' => $row->status,
                'data' => $row->data,
            ];
        }, $results);
    }
}
