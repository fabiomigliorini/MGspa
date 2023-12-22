<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Carbon\Carbon;

use Mg\MgController;
use Mg\Negocio\NegocioResource;

class PdvController
{

    public function dispositivo (PdvRequest $request)
    {
        $request->validate([
            'uuid' => 'required|uuid',
            'desktop' => 'required|boolean',
            'navegador' => 'required',
            'versaonavegador' => 'required',
            'plataforma' => 'required',
        ]);
        $pdv = PdvService::dispositivo(
            $request->uuid,
            $request->ip(),
            $request->latitude,
            $request->longitude,
            $request->precisao,
            $request->desktop,
            $request->navegador,
            $request->versaonavegador,
            $request->plataforma
        );
        return new PdvResource($pdv);
    }

    public function produtoCount (PdvRequest $request)
    {
        PdvService::autoriza($request->uuid);
        return PdvService::produtoCount();
    }

    public function produto (PdvRequest $request)
    {
        PdvService::autoriza($request->uuid);
        $codprodutobarra = $request->codprodutobarra??0;
        $limite = $request->limite??10000;
        return PdvService::produto($codprodutobarra, $limite);
    }

    public function pessoaCount (PdvRequest $request)
    {
        PdvService::autoriza($request->uuid);
        return PdvService::pessoaCount();
    }

    public function pessoa (PdvRequest $request)
    {
        PdvService::autoriza($request->uuid);
        $codpessoa = $request->codpessoa??0;
        $limite = $request->limite??10000;
        return PdvService::pessoa($codpessoa, $limite);
    }

    public function naturezaOperacao (PdvRequest $request)
    {
        PdvService::autoriza($request->uuid);
        return PdvService::naturezaOperacao();
    }

    public function estoqueLocal (PdvRequest $request)
    {
        PdvService::autoriza($request->uuid);
        return PdvService::estoqueLocal();
    }
    
    public function formaPagamento (PdvRequest $request)
    {
        PdvService::autoriza($request->uuid);
        return PdvService::formaPagamento();
    }
    
    public function impressora (PdvRequest $request)
    {
        PdvService::autoriza($request->uuid);
        return PdvService::impressora();
    }

    public function negocio (PdvRequest $request)
    {
        PdvService::autoriza($request->uuid);
        $negocio = PdvNegocioService::negocio($request->negocio);
        return new NegocioResource($negocio);
    }

}
