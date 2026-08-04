<?php

namespace Mg\Rh;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class RubricaController extends Controller
{
    public function index(Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $query = Rubrica::query();

        if ($request->filled('busca')) {
            $query->where('descricao', 'ilike', '%' . $request->input('busca') . '%');
        }

        $regs = $query->orderBy('descricao')->get();

        return RubricaResource::collection($regs);
    }

    public function store(RubricaStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::create($request->validated());

        return (new RubricaResource($reg))
            ->response()
            ->setStatusCode(201);
    }

    public function update(int $codrubrica, RubricaUpdateRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::findOrFail($codrubrica);
        $descricaoAnterior = $reg->descricao;

        DB::transaction(function () use ($reg, $request, $descricaoAnterior, $codrubrica) {
            $reg->update($request->validated());

            // tblcolaboradorrubrica.descricao e denormalizada (NOT NULL, unica fonte
            // para as avulsas, usada em PDFs e como chave ao duplicar periodo). Sem
            // isso o card do colaborador segue exibindo o nome antigo pra sempre.
            // Propaga em TODOS os vinculos, inclusive de periodos ja encerrados.
            // So a descricao propaga: tipovalor/valores sao snapshot ajustado por colaborador.
            if ($reg->descricao !== $descricaoAnterior) {
                ColaboradorRubrica::where('codrubrica', $codrubrica)->update([
                    'descricao' => $reg->descricao,
                    'alteracao' => Carbon::now(),
                    'codusuarioalteracao' => Auth::id(),
                ]);
            }
        });

        return new RubricaResource($reg->refresh());
    }

    public function inativar(int $codrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::findOrFail($codrubrica);
        $reg->inativo = Carbon::now();
        $reg->update();

        return new RubricaResource($reg->refresh());
    }

    public function ativar(int $codrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::findOrFail($codrubrica);
        $reg->inativo = null;
        $reg->update();

        return new RubricaResource($reg->refresh());
    }

    public function destroy(int $codrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $reg = Rubrica::findOrFail($codrubrica);

        $possuiVinculo = ColaboradorRubrica::where('codrubrica', $codrubrica)->exists();
        if ($possuiVinculo) {
            abort(422, 'Rubrica vinculada a colaborador não pode ser excluída.');
        }

        $reg->delete();

        return response()->json(null, 204);
    }
}
