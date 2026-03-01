<?php

namespace Mg\Titulo;

class MovimentoTituloService
{
    const TIPO_IMPLANTACAO = 100;
    const TIPO_AJUSTE = 200;
    const TIPO_AMORTIZACAO = 300;
    const TIPO_JUROS = 400;
    const TIPO_MULTA = 401;
    const TIPO_DESCONTO = 500;
    const TIPO_LIQUIDACAO = 600;
    const TIPO_RH = 601;
    const TIPO_LIQUIDACAO_COBRANCA = 610;
    const TIPO_ESTORNO_IMPLANTACAO = 900;
    const TIPO_AGRUPAMENTO = 901;
    const TIPO_ESTORNO_LIQUIDACAO_COBRANCA = 910;
    const TIPO_ESTORNO_AJUSTE = 920;
    const TIPO_ESTORNO_LIQUIDACAO = 930;
    const TIPO_ESTORNO_AMORTIZACAO = 933;
    const TIPO_ESTORNO_JUROS = 940;
    const TIPO_ESTORNO_MULTA = 941;
    const TIPO_ESTORNO_DESCONTO = 950;
    const TIPO_ESTORNO_AGRUPAMENTO = 991;
    const TIPO_TRANSFERENCIA = 992;

    public static function estornar(MovimentoTitulo $movimento): MovimentoTitulo
    {
        $estorno = new MovimentoTitulo([
            'codtitulo'              => $movimento->codtitulo,
            'codtipomovimentotitulo' => static::TIPO_ESTORNO_LIQUIDACAO,
            'debito'                 => $movimento->credito,
            'credito'                => $movimento->debito,
            'codportador'            => $movimento->codportador,
            'codliquidacaotitulo'    => $movimento->codliquidacaotitulo,
            'historico'              => null,
            'transacao'              => date('Y-m-d'),
            'sistema'                => date('Y-m-d H:i:s'),
        ]);
        $estorno->save();
        return $estorno;
    }
}
