<?php

namespace Mg\Dominio;


use DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use Mg\Dominio\Arquivo\ArquivoEstoque;
use Mg\Dominio\Arquivo\ArquivoProduto;
use Mg\Filial\Filial;

// use Mg\Portador\Portador;
// use Mg\Sequence\SequenceService;
// use Mg\Titulo\Titulo;

class DominioService
{
    public static function estoque(int $codfilial, Carbon $mes)
    {
        $filial = Filial::findOrFail($codfilial);
        $arquivo = new ArquivoEstoque($mes, $filial);
        $arquivo->processa();
        $arquivo->grava();
        return [
            'arquivo' => $arquivo->arquivo,
            'registros' => count($arquivo->registros),
        ];
    }

    public static function produto(int $codfilial, Carbon $mes)
    {
        $filial = Filial::findOrFail($codfilial);
        $arquivo = new ArquivoProduto($mes, $filial);
        $arquivo->processa();
        $arquivo->grava();
        return [
            'arquivo' => $arquivo->arquivo,
            'registros' => count($arquivo->registros),
        ];
    }

}
