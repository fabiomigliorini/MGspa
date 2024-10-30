<?php

namespace Mg\Dominio;

use Mg\NaturezaOperacao\DominioAcumulador;

class DominioAcumuladorService
{
    public static function salvar($dados)
    {
        $acum = DominioAcumulador::firstOrNew([
            'codfilial' => $dados['codfilial'],
            'codcfop' => $dados['codcfop'],
            'icmscst' => $dados['icmscst'],
        ]);
        $acum->fill($dados);
        $acum->save();
        return $acum;
    }

    public static function excluir($coddominioacumulador)
    {
        return DominioAcumulador::where([
            'coddominioacumulador' => $coddominioacumulador,
        ])->delete();
    }
}
