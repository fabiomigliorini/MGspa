<?php

namespace Mg\Colaborador;

use Illuminate\Support\Facades\DB;

class FeriasService
{

    public static function create($data)
    {
        $ferias = new Ferias($data);
        $ferias->save();
        return $ferias->refresh();
    }

    public static function update($ferias, $data)
    {
        $ferias->fill($data);
        $ferias->save();
        return $ferias;
    }


    public static function delete($ferias)
    {
        return $ferias->delete();
    }

    public static function programacaoFerias($ano)
    {

        $sql = '
            select 
                c.codcolaborador, 
                c.contratacao,
                p.codpessoa, 
                p.fantasia, 
                f.codfilial, 
                f.filial, 
                cc.codcolaboradorcargo, 
                ca.codcargo, 
                ca.cargo,
                cc.inicio
            from tblcolaborador c
            left join tblcolaboradorcargo cc on (cc.codcolaboradorcargo in (
                select iq.codcolaboradorcargo 
                from tblcolaboradorcargo iq 
                where iq.codcolaborador = c.codcolaborador
                order by iq.inicio desc 
                limit 1
            ))
            left join tblcargo ca on (ca.codcargo = cc.codcargo)
            left join tblfilial f on (f.codfilial = cc.codfilial)
            left join tblpessoa p on (p.codpessoa = c.codpessoa)
            where extract(year from c.contratacao) <= :ano
            and (extract(year from c.rescisao) <= :ano or c.rescisao is null)
            order by f.codfilial, ca.cargo nulls last, p.fantasia
        ';

        $colaboradores = DB::select($sql, ['ano' => $ano]);

        $sql = '
            select 
                f.*,
                date_part(\'doy\', f.gozoinicio) as diagozoinicio,
                date_part(\'doy\', f.gozofim) as diagozofim
            from tblferias f
            where f.codcolaborador = :codcolaborador
            and (extract(year from f.gozoinicio) = :ano or extract(year from f.gozofim) = :ano)        
        ';

        foreach ($colaboradores as $colaborador) {
            $colaborador->ferias = DB::select($sql, [
                'ano' => $ano,
                'codcolaborador' => $colaborador->codcolaborador
            ]);
        }

        return $colaboradores;
    }

    public static function programacaoFeriasOld($gozodate)
    {

        $sql = 'select co.codpessoa, p.pessoa, p.fantasia, car.cargo,
        fe.aquisitivoinicio , fe.aquisitivofim , fe.gozoinicio , fe.gozofim , 
        fe.diasgozo , fe.diasabono  from tblcolaborador co
        left join tblferias fe on (fe.codcolaborador = co.codcolaborador)
        left join tblpessoa p on (p.codpessoa = co.codpessoa)
        inner join tblcolaboradorcargo ca on (ca.codcolaborador = fe.codcolaborador)
        inner join tblcargo car on(car.codcargo = ca.codcargo) where fe.gozoinicio >= :gozodate';


        $params['gozodate'] = $gozodate;

        $result = DB::select($sql, $params);    

        return $result;
    }
}
