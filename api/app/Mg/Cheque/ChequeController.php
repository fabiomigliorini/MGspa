<?php

namespace Mg\Cheque;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class ChequeController extends MgController
{
    private const GRUPOS = ['Administrador', 'Financeiro'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        [$filter, $sort, $fields] = $this->filtros($request);

        $qry = ChequeService::pesquisar($filter, $sort, $fields);
        $res = $qry->paginate()->appends($request->all());

        return response()->json($res, 200);
    }

    public function show(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $cheque = Cheque::with([
            'Banco',
            'Pessoa',
            'ChequeEmitenteS',
            'Titulo',
            'ChequeRepasseChequeS.ChequeRepasse.Portador',
            'UsuarioCriacao',
            'UsuarioAlteracao',
        ])->findOrFail($id);
        return response()->json($cheque, 200);
    }

    public function store(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);

        $this->validar($request);

        DB::beginTransaction();
        try {
            $cheque = ChequeService::criar($request->all());
            DB::commit();
            return response()->json($cheque, 201);
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, $id)
    {
        Autorizador::autoriza(self::GRUPOS);

        $cheque = Cheque::findOrFail($id);
        $this->validar($request, $cheque->codcheque);

        DB::beginTransaction();
        try {
            $cheque = ChequeService::atualizar($cheque, $request->all());
            DB::commit();
            return response()->json($cheque, 200);
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function consultaCmc7(Request $request, string $cmc7)
    {
        Autorizador::autoriza(self::GRUPOS);
        return response()->json(ChequeService::consultaCmc7($cmc7), 200);
    }

    public function consultaEmitente(Request $request, string $cnpj)
    {
        Autorizador::autoriza(self::GRUPOS);
        return response()->json(ChequeService::consultaEmitente($cnpj), 200);
    }

    private function validar(Request $request, ?int $codcheque = null): void
    {
        $unique = Rule::unique('tblcheque', 'cmc7');
        if ($codcheque) {
            $unique->ignore($codcheque, 'codcheque');
        }

        $request->validate([
            'cmc7' => ['required', $unique],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'emissao' => ['required', 'date'],
            'vencimento' => ['required', 'date', 'after_or_equal:emissao'],
            'codpessoa' => ['required', 'integer'],
            'emitentes' => ['required', 'array', 'min:1'],
            'emitentes.*.cnpj' => ['required'],
            'emitentes.*.emitente' => ['required'],
        ], [
            'cmc7.required' => 'O campo "CMC7" deve ser preenchido!',
            'cmc7.unique' => 'Já existe um CMC7 cadastrado com esse número!',
            'valor.required' => 'O campo "Valor" deve ser preenchido!',
            'valor.numeric' => 'O campo "Valor" deve ser um número!',
            'valor.min' => 'O campo "Valor" deve ser maior que zero!',
            'emissao.required' => 'O campo "Emissão" deve ser preenchido!',
            'vencimento.required' => 'O campo "Vencimento" deve ser preenchido!',
            'vencimento.after_or_equal' => 'O vencimento não pode ser anterior à emissão!',
            'codpessoa.required' => 'Selecione o Cliente!',
            'emitentes.required' => 'Informe ao menos um emitente!',
            'emitentes.min' => 'Informe ao menos um emitente!',
            'emitentes.*.cnpj.required' => 'CNPJ/CPF do emitente é obrigatório!',
            'emitentes.*.emitente.required' => 'Nome do emitente é obrigatório!',
        ]);
    }
}
