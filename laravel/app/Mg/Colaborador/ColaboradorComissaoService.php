<?php

namespace Mg\Colaborador;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ColaboradorComissaoService
{

    public static function comissaoCaixas($inicio, $fim)
    {
        $sql = '
            with cx as (
                select 
                    u.codusuario, 
                    p.codpessoa, 
                    p.pessoa, 
                    c.codcargo, 
                    c.cargo, 
                    c.comissaocaixa, 
                    f.codfilial, 
                    f.filial 
                from tblcargo c
                inner join tblcolaboradorcargo cc on (cc.codcargo = c.codcargo)
                inner join tblcolaborador col on (col.codcolaborador = cc.codcolaborador)
                inner join tblpessoa p on (p.codpessoa = col.codpessoa)
                inner join tblusuario u on (u.codpessoa = col.codpessoa)
                inner join tblfilial f on (f.codfilial = cc.codfilial)
                where c.comissaocaixa is not null
                and coalesce(cc.fim, :fim) <= :fim
                and coalesce(col.rescisao, :fim) <= :fim
                and coalesce(col.renovacaoexperiencia, :fim) < :fim
                order by p.pessoa
            )
            select 
                cx.codfilial, 
                cx.filial,
                cx.codpessoa, 
                cx.pessoa, 
                cx.codcargo, 
                cx.cargo, 
                cx.comissaocaixa,
                count(n.codnegocio) negocios, 
                sum(n.valortotal) valor,
                sum(n.valortotal * (cx.comissaocaixa /100)) as comissao
            from tblnegocio n 
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
            inner join cx on (cx.codusuario = n.codusuario)
            where n.lancamento between :inicio and :fim
            and n.codnegociostatus = 2
            and nat.venda = true 
            group by 
                cx.codusuario, 
                cx.codpessoa, 
                cx.pessoa, 
                cx.codcargo, 
                cx.cargo, 
                cx.comissaocaixa, 
                cx.codfilial, 
                cx.filial 
            order by 
                cx.filial,
                cx.pessoa
        ';

        $ret = DB::select($sql, [
            'inicio' => $inicio,
            'fim' => $fim,
        ]);
        return $ret;
    }
}
