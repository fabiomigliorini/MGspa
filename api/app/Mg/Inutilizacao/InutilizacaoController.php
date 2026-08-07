<?php

namespace Mg\Inutilizacao;

use Illuminate\Http\Request;

use Mg\MgController;
use Mg\Filial\Filial;

class InutilizacaoController extends MgController
{
    public function index(Request $request)
    {
        $regs = InutilizacaoService::listar($request->only([
            'codfilial',
            'modelo',
            'serie',
            'numero',
            'limite',
        ]));

        return response()->json(['data' => $regs]);
    }

    public function show(int $codinutilizacao)
    {
        $reg = Inutilizacao::with('Filial')->findOrFail($codinutilizacao);
        return new InutilizacaoResource($reg);
    }

    public function store(InutilizacaoStoreRequest $request)
    {
        $dados = $request->validated();
        $filial = Filial::findOrFail($dados['codfilial']);

        $reg = InutilizacaoService::inutilizar(
            $filial,
            (int) $dados['modelo'],
            (int) $dados['serie'],
            (int) $dados['numeroinicial'],
            (int) $dados['numerofinal'],
            $dados['justificativa']
        );

        return (new InutilizacaoResource($reg->load('Filial')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Download do procInutNFe. Ate este PR esse XML simplesmente nao era guardado.
     */
    public function xml(int $codinutilizacao)
    {
        $reg = Inutilizacao::findOrFail($codinutilizacao);

        if (empty($reg->arquivo)) {
            abort(404, 'Esta inutilização não possui XML arquivado.');
        }

        $path = rtrim(config('mg.paths.nfe_php'), '/') . '/' . $reg->arquivo;
        if (!file_exists($path)) {
            abort(404, 'Arquivo XML não localizado.');
        }

        $nome = "{$reg->modelo}-{$reg->serie}-{$reg->numeroinicial}-{$reg->numerofinal}-inut.xml";

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$nome}\"",
        ]);
    }
}
