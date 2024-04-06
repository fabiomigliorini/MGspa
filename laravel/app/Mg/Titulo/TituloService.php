<?php

namespace Mg\Titulo;

class TituloService
{

    const TIPO_IMPLANTACAO = 100;
    const TIPO_ESTORNO_IMPLANTACAO = 900;

    const TIPO_AJUSTE = 200;
    const TIPO_ESTORNO_AJUSTE = 920;

    const TIPO_AMORTIZACAO = 300;
    const TIPO_ESTORNO_AMORTIZACAO = 933;

    const TIPO_JUROS = 400;
    const TIPO_ESTORNO_JUROS = 940;

    const TIPO_DESCONTO = 500;
    const TIPO_ESTORNO_DESCONTO = 950;

    const TIPO_LIQUIDACAO = 600;
    const TIPO_ESTORNO_LIQUIDACAO = 930;

    const TIPO_AGRUPAMENTO = 901;
    const TIPO_ESTORNO_AGRUPAMENTO = 991;

    const TIPO_LIQUIDACAO_COBRANCA = 610;
    const TIPO_ESTORNO_LIQUIDACAO_COBRANCA = 910;

    const TIPO_MULTA = 401;
    const TIPO_ESTORNO_MULTA = 941;

    const TIPO_VALE = 3;

    public static function estornar(Titulo $titulo)
    {
        if (!empty($titulo->estornado)) {
            throw new Exception("Titulo já está estornado!", 1);
        }
        $mov = new MovimentoTitulo([
            'codtitulo' => $titulo->codtitulo,
            'codtipomovimentotitulo' => static::TIPO_ESTORNO_IMPLANTACAO,
            'debito' => $titulo->credito,
            'credito' => $titulo->debito,
            'transacao' => date('Y-m-d'),
            'codtituloagrupamento' => $titulo->codtituloagrupamento,
            'codliquidacaotitulo' => $titulo->codliquidacaotitulo,
            'codboletoretorno' => $titulo->codboletoretorno,
            'codcobranca' => $titulo->codcobranca,
            'codportador' => $titulo->codportador,
            'codtitulorelacionado' => $titulo->codtitulorelacionado,
            'sistema' => date('Y-m-d H:i:s'),
        ]);
        $mov->save();
        $titulo = $titulo->fresh();
        $titulo->estornado = date('Y-m-d H:i:s');
        $titulo->save();
        return $titulo;
    }
}
