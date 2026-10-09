<?php

namespace Mg\Ocorrencia;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaAutorizador;

/**
 * Tela Ocorrencias do contas (TASK-205): o livro do que o caixa cancelou,
 * removeu, diminuiu, descontou ou esqueceu aberto, para o gerente conferir.
 */
class OcorrenciaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'codfilial' => 'nullable|integer',
            'codpdv' => 'nullable|integer',
            'tipo' => 'nullable|integer',
            'codusuario' => 'nullable|integer',
            'codnegocio' => 'nullable|integer',
            'data_de' => 'nullable|date',
            'data_ate' => 'nullable|date',
            'situacao' => 'nullable|in:pendente,conferida,todas',
            'ordem' => 'nullable|in:recentes,valor',
            'page' => 'nullable|integer|min:1',
        ]);
        return OcorrenciaService::listagem($filtros, (int) ($filtros['page'] ?? 1));
    }

    private function ocorrencia(int $id): Ocorrencia
    {
        $oc = Ocorrencia::findOrFail($id);
        ConferenciaAutorizador::autorizar($oc->codfilial);
        return $oc;
    }

    public function conferir(Request $request, int $id)
    {
        $dados = $request->validate(['observacao' => 'nullable|string|max:500']);
        $oc = $this->ocorrencia($id);
        DB::transaction(fn () => OcorrenciaService::conferir($oc, $dados['observacao'] ?? null));
        return ['data' => OcorrenciaService::carregar($oc->codocorrencia)];
    }

    public function reabrir(int $id)
    {
        $oc = $this->ocorrencia($id);
        DB::transaction(fn () => OcorrenciaService::reabrir($oc));
        return ['data' => OcorrenciaService::carregar($oc->codocorrencia)];
    }
}
