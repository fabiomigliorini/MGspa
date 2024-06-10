<?php

namespace Mg\Titulo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;


class TituloController extends Controller
{
    public static function looker(Request $request)
    {
        // busca Cidades
        $sql = "
            select 
                tt.pagar,
                tt.receber,
                tt.tipotitulo,
                gc.grupocliente,
                por.portador,
                ge.grupoeconomico, 
                pes.fantasia,
                coalesce(ge.grupoeconomico, pes.fantasia) as grupoeconomicofantasia,
                tt.tipotitulo,
                t.numero,
                t.saldo
            from tbltitulo t
            inner join tbltipotitulo tt on (tt.codtipotitulo = t.codtipotitulo)
            left join tblportador por on (por.codportador = t.codportador)
            left join tblpessoa pes on (pes.codpessoa = t.codpessoa)
            left join tblgrupocliente gc on (gc.codgrupocliente = pes.codgrupocliente)
            left join tblgrupoeconomico ge on (ge.codgrupoeconomico = pes.codgrupoeconomico)
            where t.saldo != 0
        ";
        $ret = DB::select($sql);

        // retorna
        return $ret;
    }
}
