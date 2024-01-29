<?php

namespace Mg\Negocio;

use DB;

class NegocioProdutoBarraService
{
    public static function quantidadeDevolvida(NegocioProdutoBarra $npb)
    {
        $sql = '
            SELECT sum(npb.quantidade) as quantidade
            FROM tblnegocio n
            INNER JOIN tblnegocioprodutobarra npb ON (npb.codnegocio = n.codnegocio)
            WHERE n.codnegociostatus = :codnegociostatus
            AND npb.codnegocioprodutobarradevolucao = :codprodutobarra
        ';
        $ret = DB::select($sql, [
            'codnegociostatus' => NegocioService::STATUS_FECHADO,
            'codprodutobarra' => $npb->codnegocioprodutobarra
        ]);
        return $ret[0]->quantidade;
    }
}
