<?php

namespace Mg\Colaborador;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CargoService
{


    public static function index($cargo, $de, $ate)
    {

        $sql = '
        select * from tblcargo ca 
        ';

        $params = null;

        if (!empty($cargo)) {
            $sql .= '
        where ca.cargo ilike :cargo
        ';
            $params['cargo'] = "%{$cargo}%";
        }

        if (!empty($de)) {
            $sql .= '
            and ca.salario >= :de 
            ';

            $params['de'] = $de;
        }

        if(!empty($ate)) {
            $sql .= '
            and ca.salario <= :ate
            ';

            $params['ate'] = $ate;
        }

        $sql .= '
        order by ca.cargo asc
        ';

        if($params) {
        $result = DB::select($sql, $params);
        }else{
        $result = DB::select($sql);
        }
        return $result;
    }

    public static function create($data)
    {
        $cargo = new Cargo($data);
        $cargo->save();
        return $cargo->refresh();
    }

    public static function update($cargo, $data)
    {
        $cargo->fill($data);
        $cargo->save();
        return $cargo;
    }


    public static function delete($cargo)
    {
        return $cargo->delete();
    }

    public static function ativar($cargo)
    {
        $cargo->inativo = null;
        $cargo->update();
        return $cargo;
    }

    public static function inativar($cargo, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $cargo->inativo = $date;
        $cargo->update();
        return $cargo;
    }

    public static function pessoasDoCargo($codcargo)
    {

        $sql = '
        select f.codfilial, f.filial, p.codpessoa, p.fantasia, cc.codcolaboradorcargo, cc.inicio
        from tblcolaboradorcargo cc
        left join tblcolaborador c on (c.codcolaborador = cc.codcolaborador)
        left join tblpessoa p on (p.codpessoa = c.codpessoa)
        left join tblfilial f on (f.codfilial = cc.codfilial)
        where cc.codcargo = :codcargo
        and cc.fim is null
        and c.rescisao is null
        ';

        $params['codcargo'] = $codcargo;

        $result = DB::select($sql, $params);

        return $result;
    }
}
