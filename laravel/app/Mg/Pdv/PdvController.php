<?php

namespace Mg\Pdv;

use Illuminate\Http\Request;
use Carbon\Carbon;

use Mg\MgController;
use Mg\Negocio\NegocioResource;
use Mg\Negocio\Negocio;

class PdvController
{

    public function dispositivo (Request $request)
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
        PdvService::autoriza($request->pdv);
        return PdvService::produtoCount();
    }

    public function produto (PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $codprodutobarra = $request->codprodutobarra??0;
        $limite = $request->limite??10000;
        return PdvService::produto($codprodutobarra, $limite);
    }

    public function pessoaCount (PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::pessoaCount();
    }

    public function pessoa (PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $codpessoa = $request->codpessoa??0;
        $limite = $request->limite??10000;
        return PdvService::pessoa($codpessoa, $limite);
    }

    public function naturezaOperacao (PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::naturezaOperacao();
    }

    public function estoqueLocal (PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::estoqueLocal();
    }
    
    public function formaPagamento (PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::formaPagamento();
    }
    
    public function impressora (PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvService::impressora();
    }

    public function putNegocio (PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = PdvNegocioService::negocio($request->negocio, $pdv);
        return new NegocioResource($negocio);
    }

    public function getNegocio (PdvRequest $request, $id)
    {
        PdvService::autoriza($request->pdv);
        if (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $id)) {
            $negocio = Negocio::where(['uuid'=>$id])->firstOrFail();
        } else {
            $negocio = Negocio::findOrFail($id);
        }
        return new NegocioResource($negocio);
    }

    public function fecharNegocio (PdvRequest $request, $id)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $negocio = Negocio::findOrFail($id);
        $negocio = PdvNegocioService::fechar($negocio, $pdv);
        return new NegocioResource($negocio);
    }
}
