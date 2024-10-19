<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Storage;

class PdvAnexoController
{
    public function upload($codnegocio, PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        PdvAnexoService::upload($codnegocio, $request->pasta, $request->anexoBase64);
        return PdvAnexoService::listagem($codnegocio);
    }

    public function listagem($codnegocio, PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvAnexoService::listagem($codnegocio);
    }

    public function excluir(int $codnegocio, string $pasta, string $anexo, PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        PdvAnexoService::excluir($codnegocio, $pasta, $anexo);
        return PdvAnexoService::listagem($codnegocio);
    }

    public function show($codnegocio, string $pasta, string $anexo)
    {
        $path = PdvAnexoService::diretorio($codnegocio) . "{$pasta}/{$anexo}";
        if (!Storage::disk('negocio-anexo')->exists($path)) {
            abort(404, 'Anexo Inexistente!');
        }
        return Storage::disk('negocio-anexo')->response($path);
    }

    public function sugerir(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        return PdvAnexoService::sugerir($request->anexoBase64);
    }

    public function procurar(PdvRequest $request)
    {
        PdvService::autoriza($request->pdv);
        $encontrados = PdvAnexoService::procurar($request->codnegocio, $request->valor);
        return [
            'codnegocio' => $request->codnegocio,
            'valor' => $request->valor,
            'encontrados' => $encontrados
        ];
    }

    public function faltando (PdvRequest $request, $ano, $mes)
    {
        PdvService::autoriza($request->pdv);
        return PdvAnexoService::faltando($ano, $mes);
    }
}
