<?php

namespace Mg\Feriado;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class FeriadoController extends Controller
{
    public function index()
    {
        $regs = Feriado::orderBy('data', 'desc')->get();
        return FeriadoResource::collection($regs);
    }

    public function store(FeriadoStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Feriado::create($request->validated());

        return (new FeriadoResource($reg))
            ->response()
            ->setStatusCode(201);
    }

    public function update(FeriadoUpdateRequest $request, $codferiado)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Feriado::findOrFail($codferiado);
        $reg->update($request->validated());

        return new FeriadoResource($reg);
    }

    public function destroy($codferiado)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Feriado::findOrFail($codferiado);
        $reg->delete();

        return response()->json(null, 204);
    }

    public function inativar($codferiado)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Feriado::findOrFail($codferiado);
        $reg->inativo = Carbon::now();
        $reg->update();

        return new FeriadoResource($reg);
    }

    public function ativar($codferiado)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Feriado::findOrFail($codferiado);
        $reg->inativo = null;
        $reg->update();

        return new FeriadoResource($reg);
    }

    public function gerarAno(FeriadoGerarAnoRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $resultado = FeriadoService::gerarFeriados($request->validated()['ano']);
            DB::commit();
            return new FeriadoGerarAnoResource($resultado);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
