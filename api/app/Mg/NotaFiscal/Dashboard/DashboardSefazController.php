<?php

namespace Mg\NotaFiscal\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Mg\Filial\Filial;
use Mg\NFePHP\NFePHPService;

class DashboardSefazController extends Controller
{
    /**
     * GET /nota-fiscal/dashboard/sefaz/status/{codfilial}
     * Consulta status do SEFAZ para a filial
     *
     * Retorno esperado:
     * {
     *   "tpAmb": 1,
     *   "verAplic": "SP_NFE_PL_008i2",
     *   "cStat": "107",
     *   "xMotivo": "Servico em Operacao",
     *   "cUF": "35",
     *   "dhRecbto": "2017-05-24T10:12:47-03:00",
     *   "tMed": "1"
     * }
     */
    public function status(int $codfilial)
    {
        $filial = Filial::findOrFail($codfilial);

        try {
            $result = NFePHPService::sefazStatus($filial);
        } catch (\Throwable $e) {
            Log::warning("Status SEFAZ indisponível (filial {$codfilial}): {$e->getMessage()}");
            return response()->json([
                'status' => 'offline',
                'mensagem' => $e->getMessage(),
            ]);
        }

        return response()->json($result);
    }
}
