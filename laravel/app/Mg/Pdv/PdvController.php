<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Carbon\Carbon;

use Mg\MgController;

class PdvController
{

    public function produtoCount (Request $request)
    {
        return PdvService::produtoCount();
    }

    public function produto (Request $request)
    {
        $codprodutobarra = $request->codprodutobarra??0;
        $limite = $request->limite??10000;
        return PdvService::produto($codprodutobarra, $limite);
    }

    public function pessoaCount (Request $request)
    {
        return PdvService::pessoaCount();
    }

    public function pessoa (Request $request)
    {
        $codpessoa = $request->codpessoa??0;
        $limite = $request->limite??10000;
        return PdvService::pessoa($codpessoa, $limite);
    }

    public function naturezaOperacao (Request $request)
    {
        return PdvService::naturezaOperacao();
    }

    public function estoqueLocal (Request $request)
    {
        return PdvService::estoqueLocal();
    }
    
    public function formaPagamento (Request $request)
    {
        return PdvService::formaPagamento();
    }
    
    public function impressora (Request $request)
    {
        return PdvService::impressora();
    }

}
