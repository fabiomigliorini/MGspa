<?php

namespace Mg\Stone\Connect;

use Illuminate\Http\Request;

use Mg\Stone\StonePreTransacaoService;
use Mg\Stone\StonePreTransacao;
use Mg\Stone\StoneFilial;

use Mg\MgController;

class PreTranscaoController extends MgController
{

    public function store(Request $request)
    {
        $request->validate([
            'codstonefilial' => ['required', 'integer'],
            'codstonepos' => ['integer', 'nullable'],
            'valor' => ['required', 'numeric'],
            'titulo' => ['string'],
            'codnegocio' => ['integer'],
            'tipo' => ['integer'],
            'parcelas' => ['integer'],
            'tipoparcelamento' => ['integer'],
        ]);
        $sf = StoneFilial::findOrFail($request->codstonefilial);
        $spt = StonePreTransacaoService::create(
            $sf,
            $request->valor,
            $request->titulo??null,
            $request->codstonepos??null,
            $request->codnegocio??null,
            $request->tipo??null,
            $request->parcelas??null,
            $request->tipoparcelamento??null
        );
        return $spt;
    }

    public function show(Request $request, $codstonepretransacao)
    {
        $spt = StonePreTransacao::findOrFail($codstonepretransacao);
        $spt = StonePreTransacaoService::consulta($spt);
        return $spt;
    }

}
