<?php

namespace Mg\Grao;

use App\Http\Requests\Mg\Grao\MovimentoGraoManualRequest;
use Illuminate\Http\Request;
use Mg\MgController;

class MovimentoGraoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = MovimentoGraoService::pesquisar($filter, $sort, $fields)
            ->paginate()->appends($request->all());
        return MovimentoGraoResource::collection($res);
    }

    /** Estoque depositado por unidade armazenadora (silo proprio + terceiro). */
    public function saldosUnidades(Request $request)
    {
        $codsafra = $request->codsafra ? (int) $request->codsafra : null;
        return response()->json(MovimentoGraoService::saldosUnidades($codsafra), 200);
    }

    /** Lanca um ajuste manual (comercial) direto no extrato. */
    public function store(MovimentoGraoManualRequest $request)
    {
        $mov = MovimentoGraoService::lancarManual($request->validated());
        return new MovimentoGraoResource($mov);
    }

    /** Estorna (inativa) um lancamento MANUAL. Auto nao pode ser inativado aqui. */
    public function inativar(Request $request, $id)
    {
        $mov = MovimentoGrao::where('manual', true)->findOrFail($id);
        $mov = MovimentoGraoService::inativar($mov);
        return new MovimentoGraoResource($mov->fresh(MovimentoGraoService::WITH));
    }

    public function ativar(Request $request, $id)
    {
        $mov = MovimentoGrao::where('manual', true)->findOrFail($id);
        $mov = MovimentoGraoService::ativar($mov);
        return new MovimentoGraoResource($mov->fresh(MovimentoGraoService::WITH));
    }
}
