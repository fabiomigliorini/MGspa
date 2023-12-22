<?php

namespace Mg\GrupoEconomico;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Exception;
use Illuminate\Validation\Rule;
use Mg\Pessoa\Pessoa;
use Mg\Pessoa\PessoaResource;

class GrupoEconomicoController extends MgController
{

    public function index (Request $request)
    {
        $pesquisa = "%$request->nome%"??null;

        $grupos = GrupoEconomicoService::index($pesquisa);
        return GrupoEconomicoResource::collection($grupos);
    }

    public function create (Request $request)
    {
        $data = $request->all();
        $criargrupo = GrupoEconomicoService::create($data);
        
         return new GrupoEconomicoResource($criargrupo);
    }

    public function show (Request $request, $codgrupoeconomico)
    {   
        $pessoasGrupo = GrupoEconomico::findOrFail($codgrupoeconomico);
        return new GrupoEconomicoResource($pessoasGrupo);
    }

    public function update (Request $request, $codgrupoeconomico)
    {
        $data = $request->all();
        $grupo = GrupoEconomico::findOrFail($codgrupoeconomico);
        $grupo = GrupoEconomicoService::update($grupo, $data);
        
        return new GrupoEconomicoResource($grupo);
    }

    public function delete (Request $request, $codgrupoeconomico)
    {
       
        $grupo = GrupoEconomico::findOrFail($codgrupoeconomico);
        $grupo = GrupoEconomicoService::delete($grupo);
        
        return response()->json([
            'result' => true
        ], 200);
    }

    public function pesquisaGrupoEconomico(Request $request)
    {
        if ($request->grupoeconomico) {
            $search = GrupoEconomico::where('grupoeconomico', 'ilike', "%$request->grupoeconomico%")->get();
            return response()->json($search);
        }

        if($request->codgrupoeconomico) {
            $search = GrupoEconomico::where('codgrupoeconomico', $request->codgrupoeconomico)->get();
            return response()->json($search);
        }
       
        return response()->json('Nenhum resultado encontrado');
    }


    public function deletaPessoadoGrupo(Request $request, $codpessoa, $codgrupoeconomico) 
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        $pessoa = GrupoEconomicoService::removerDoGrupo($pessoa);

        return ['message' => true];
    }

    public function inativar($codgrupoeconomico)
    {

        // Autorizador::autoriza(array('Pessoa'));
        $pes = GrupoEconomico::findOrFail($codgrupoeconomico);
        $pes = GrupoEconomicoService::inativar($pes);

        return new GrupoEconomicoResource($pes);
    }


    public function ativar($codgrupoeconomico)
    {
        // Autorizador::autoriza(array('Pessoa'));

        $pes = GrupoEconomico::findOrFail($codgrupoeconomico);
        $pes = GrupoEconomicoService::ativar($pes);

        return new GrupoEconomicoResource($pes);
    }


}
