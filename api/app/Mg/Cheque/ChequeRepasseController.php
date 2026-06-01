<?php

namespace Mg\Cheque;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class ChequeRepasseController extends MgController
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        [$filter, $sort, $fields] = $this->filtros($request);

        $qry = ChequeRepasseService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return response()->json($res, 200);
    }

    public function show(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);
        return response()->json(ChequeRepasseService::carregarComCheques($id), 200);
    }

    public function chequesParaRepassar(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $request->validate([
            'vencimento_de' => ['nullable', 'date'],
            'vencimento_ate' => ['nullable', 'date'],
        ]);

        if (empty($request->vencimento_de) && empty($request->vencimento_ate)) {
            return response()->json(['message' => 'Informe ao menos uma data de vencimento.'], 422);
        }

        $cheques = ChequeRepasseService::chequesParaRepassar($request->all());
        return response()->json($cheques, 200);
    }

    public function store(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $request->validate([
            'codportador' => ['required', 'integer'],
            'data' => ['required', 'date'],
            'observacoes' => ['nullable', 'max:200'],
            'codcheques' => ['required', 'array', 'min:1'],
            'codcheques.*' => ['integer'],
        ], [
            'codportador.required' => 'Selecione o Portador!',
            'data.required' => 'Informe a data do repasse!',
            'codcheques.required' => 'Selecione ao menos um cheque!',
            'codcheques.min' => 'Selecione ao menos um cheque!',
        ]);

        DB::beginTransaction();
        try {
            $repasse = ChequeRepasseService::criar($request->all());
            DB::commit();
            return response()->json($repasse, 201);
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function inativar(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $repasse = ChequeRepasse::with('ChequeRepasseChequeS.Cheque')->findOrFail($id);
            $repasse = ChequeRepasseService::inativarRepasse($repasse);
            DB::commit();
            return response()->json($repasse, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ativar(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        DB::beginTransaction();
        try {
            $repasse = ChequeRepasse::with(['Portador', 'ChequeRepasseChequeS.Cheque'])->findOrFail($id);
            $repasse = ChequeRepasseService::reativarRepasse($repasse);
            DB::commit();
            return response()->json($repasse, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function borderoPdf(Request $request, $codchequerepasse)
    {
        Autorizador::autoriza(self::GRUPOS);

        $pdf = ChequeRepasseRelatorioService::pdf((int) $codchequerepasse);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="repasse-' . $codchequerepasse . '.pdf"',
        ]);
    }

    public function devolverCheque(Request $request, $codchequerepasse, $codchequerepassecheque)
    {
        Autorizador::autoriza(self::GRUPOS);

        $request->validate([
            'codchequemotivodevolucao' => ['required', 'integer'],
            'data' => ['required', 'date'],
            'observacoes' => ['nullable', 'max:200'],
        ], [
            'codchequemotivodevolucao.required' => 'Selecione o motivo da devolução!',
            'data.required' => 'Informe a data da devolução!',
        ]);

        DB::beginTransaction();
        try {
            $pivot = ChequeRepasseCheque::with('Cheque')
                ->where('codchequerepasse', $codchequerepasse)
                ->findOrFail($codchequerepassecheque);
            $pivot = ChequeRepasseService::devolverCheque($pivot, $request->all());
            DB::commit();
            return response()->json($pivot, 200);
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
