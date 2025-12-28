<?php

namespace Mg\Tributacao;

use App\Http\Controllers\Controller;
use Mg\Tributacao\Requests\TributoRequest;
use Mg\Tributacao\Resources\TributoResource;

class TributoController extends Controller
{
    public function index()
    {
        return TributoResource::collection(
            Tributo::orderBy('descricao')->get()
        );
    }

    public function show(int $id)
    {
        return new TributoResource(
            Tributo::findOrFail($id)
        );
    }

    public function store(TributoRequest $request)
    {
        $tributo = Tributo::create($request->validated());
        return new TributoResource($tributo);
    }

    public function update(TributoRequest $request, int $id)
    {
        $tributo = Tributo::findOrFail($id);
        $tributo->update($request->validated());
        return new TributoResource($tributo);
    }

    public function destroy(int $id)
    {
        Tributo::findOrFail($id)->delete();
        return response()->noContent();
    }
}
