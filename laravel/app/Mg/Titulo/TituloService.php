<?php

namespace Mg\Titulo;

class TituloService
{
    const TIPO_VALE = 3;
    const TIPO_RH = 952;

    public static function estornar(Titulo $titulo)
    {
        if (!empty($titulo->estornado)) {
            throw new \Exception("Titulo já está estornado!", 1);
        }
        $mov = new MovimentoTitulo([
            'codtitulo' => $titulo->codtitulo,
            'codtipomovimentotitulo' => MovimentoTituloService::TIPO_ESTORNO_IMPLANTACAO,
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
