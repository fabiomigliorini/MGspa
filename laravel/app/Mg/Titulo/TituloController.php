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
                t.saldo,
                case 
                    when vencimento < (now() - '2 day'::interval) then 'vencido' 
                    else 'vencer' 
                end	as vencido,
                case 
                    when vencimento < (now() - '1 year'::interval) then 'vencido_antigo'
                    when vencimento between (now() - '1 year'::interval) and (now() - '6 month'::interval) then 'vencido_ano'
                    when vencimento between (now() - '6 month'::interval) and (now() - '1 month'::interval) then 'vencido_semestre'
                    when vencimento between (now() - '1 month'::interval) and (now() - '2 day'::interval) then 'vencido_mes'
                    when vencimento between (now() - '2 day'::interval) and (now() + '30 day'::interval) then 'vencendo_mes'
                    when vencimento between (now() + '30 day'::interval) and (now() + '60 day'::interval) then 'vencendo_bimestre'
                    when vencimento between (now() + '60 day'::interval) and (now() + '1 year'::interval) then 'vencendo_ano'
                    else 'vencendo_posterior'
                end as idade,
                t.vencimento
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
