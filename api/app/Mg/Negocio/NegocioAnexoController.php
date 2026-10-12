<?php

namespace Mg\Negocio;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Pdv\PdvService;
use Mg\Usuario\Autorizador;

/**
 * Anexos do negocio (confissao, imagem, pdf) e leitura da confissao. Uma
 * rota so' para o PDV e o contas (M9 doc-3): com `pdv` autoriza pelo
 * dispositivo; sem ele, pelo usuario (quem confere a filial do negocio).
 */
class NegocioAnexoController
{
    private function autorizarNegocio(Request $request, int $codnegocio): void
    {
        if (!empty($request->pdv)) {
            PdvService::autoriza($request->pdv);
            return;
        }
        $negocio = Negocio::findOrFail($codnegocio);
        ConferenciaAutorizador::autorizar($negocio->codfilial);
    }

    // leitura e busca sem negocio definido: PDV ou quem confere
    private function autorizar(Request $request): void
    {
        if (!empty($request->pdv)) {
            PdvService::autoriza($request->pdv);
            return;
        }
        Autorizador::autoriza(['Financeiro', 'Gerente']);
    }

    public function upload(Request $request, int $codnegocio)
    {
        $request->validate([
            'pasta' => 'required|in:confissao,imagem,pdf',
            'anexoBase64' => 'required|string',
        ]);
        $this->autorizarNegocio($request, $codnegocio);
        NegocioAnexoService::upload($codnegocio, $request->pasta, $request->ratio ?? '', $request->anexoBase64);
        return NegocioAnexoService::listagem($codnegocio);
    }

    public function listagem(Request $request, int $codnegocio)
    {
        $this->autorizarNegocio($request, $codnegocio);
        return NegocioAnexoService::listagem($codnegocio);
    }

    public function excluir(Request $request, int $codnegocio, string $pasta, string $anexo)
    {
        $this->autorizarNegocio($request, $codnegocio);
        NegocioAnexoService::excluir($codnegocio, $pasta, $anexo);
        return NegocioAnexoService::listagem($codnegocio);
    }

    public function show(int $codnegocio, string $pasta, string $anexo)
    {
        $path = NegocioAnexoService::diretorio($codnegocio) . "{$pasta}/{$anexo}";
        if (!Storage::disk('negocio-anexo')->exists($path)) {
            abort(404, 'Anexo Inexistente!');
        }
        return Storage::disk('negocio-anexo')->response($path);
    }

    public function sugerir(Request $request)
    {
        $request->validate(['anexoBase64' => 'required|string']);
        $this->autorizar($request);
        return NegocioAnexoService::sugerir($request->anexoBase64);
    }

    public function procurar(Request $request)
    {
        $this->autorizar($request);
        $encontrados = NegocioAnexoService::procurar($request->codnegocio, $request->valor);
        return [
            'codnegocio' => $request->codnegocio,
            'valor' => $request->valor,
            'encontrados' => $encontrados,
        ];
    }

    public function faltando(Request $request, $ano, $mes)
    {
        $this->autorizar($request);
        return NegocioAnexoService::faltando($ano, $mes);
    }

    public function ignorarConfissao(Request $request, int $codnegocio)
    {
        $this->autorizarNegocio($request, $codnegocio);
        NegocioAnexoService::ignorarConfissao($codnegocio);
        $neg = Negocio::findOrFail($codnegocio);
        return NegocioAnexoService::faltando($neg->lancamento->year, $neg->lancamento->month);
    }
}
