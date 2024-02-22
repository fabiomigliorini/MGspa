<?php

namespace Mg\Pessoa;

use Illuminate\Http\Request;
use Mg\MgController;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use DB;
use Illuminate\Support\Facades\Auth;
use Mg\Cidade\Cidade;
use Mg\FormaPagamento\FormaPagamento;
use Mg\Usuario\Autorizador;

class PessoaController extends MgController
{

    public function create(Request $request)
    {

        Autorizador::autoriza(['Publico']);

        $data = $request->all();

        if ($request->ieoutra) {
            $this->validate($request, [
                'ieoutra' => 'required|inscricao_estadual:' . $request->uf,
            ]);
        }

        $this->validate($request, [
            'cnpj' => 'required|cpf_cnpj',
            'fisica' => 'required|boolean',
            'fantasia' => 'required',
            'pessoa' => 'required',
            'consumidor' => 'required|boolean',
            'fornecedor' => 'required|boolean',
            'cliente' => 'required|boolean',      
        ]);

        $pessoa = PessoaService::createPelaSefazReceitaWs($data);
        return new PessoaResource($pessoa);
    }

    public function index(Request $request)
    {

        $codpessoa = $request->codpessoa ?? null;
        $pessoa = $request->pessoa ?? null;
        $cnpj = $request->cnpj ?? null;
        $email = $request->email ?? null;
        $fone = $request->fone ?? null;
        $codgrupoeconomico = $request->codgrupoeconomico ?? null;
        $codcidade = $request->codcidade ?? null;
        $inativo = $request->inativo ?? null;
        $codformapagamento = $request->codformapagamento ?? null;
        $codgrupocliente = $request->codgrupocliente ?? null;

        $limit = $request->per_page ?? 108;
        $offset = (($request->page ?? 1) - 1) * $limit;

        $pessoas = PessoaService::index(
            $limit,
            $offset,
            $codpessoa,
            $pessoa,
            $cnpj,
            $email,
            $fone,
            $codgrupoeconomico,
            $codcidade,
            $inativo,
            $codformapagamento,
            $codgrupocliente,
        );

        return PessoaResource::collection($pessoas);
    }

    public function formapagamento(Request $request)
    {
        $codformapagamento = $request->codformapagamento;

        if (!$request->codformapagamento) {
            $consultaformapagamento =  DB::table('tblformapagamento')->select('codformapagamento', 'formapagamento')->get();

            return response()->json($consultaformapagamento, 200);
        }

        if (!is_numeric($codformapagamento)) {
            return response()->json('Forma de pagamento nao encontrada', 200);
        }
        $formapagamento = FormaPagamento::where('codformapagamento', $codformapagamento)->first();

        if ($formapagamento) {
            return response()->json($formapagamento['formapagamento'], 200);
        } else {
            return response()->json('Nada encontrado', 200);
        }
    }

    public function show(Request $request, $codpessoa)
    {

        $pessoa = Pessoa::findOrFail($codpessoa);
        return new PessoaResource($pessoa);
    }

    public function update(Request $request, $codpessoa)
    {
        Autorizador::autoriza(array('Publico'));

        $data = $request->all();

        // if ($request->ie) {
        //     $buscaSigla = PessoaService::buscaSigla($request->codcidade);
        //     $uf = $buscaSigla[0]->sigla;
        //     $ie = str_pad($request->ie, 11, 0, STR_PAD_LEFT);
        //     $request['ie'] = $ie;

        //     $this->validate($request, [
        //         'cnpj' => 'required|cpf_cnpj',
        //         'ie' => 'required|inscricao_estadual:' . $uf,
        //     ]);
        // }

        // $this->validate($request, [
        //     'cnpj' => 'required|cpf_cnpj'
        // ]);


        $pessoa = Pessoa::findOrFail($codpessoa);
        $pessoa = PessoaService::update($pessoa, $data);
        return new PessoaResource($pessoa);
    }

    public function delete(Request $request, $codpessoa)
    {
        Autorizador::autoriza(array('Publico'));


        $pessoa = Pessoa::findOrFail($codpessoa);
        $res = PessoaService::delete($pessoa);
        return response()->json([
            'result' => $res
        ], 200);
    }

    public function ativar(Request $request, $codpessoa)
    {

        Autorizador::autoriza(array('Publico'));

        $pessoa = Pessoa::findOrFail($codpessoa);
        $pessoa = PessoaService::ativar($pessoa);
        return new PessoaResource($pessoa);
    }

    public function inativar(Request $request, $codpessoa)
    {

        Autorizador::autoriza(array('Publico'));

        $pessoa = Pessoa::findOrFail($codpessoa);
        $pessoa = PessoaService::inativar($pessoa);
        return new PessoaResource($pessoa);
    }

    public function importar(Request $request)
    {
        $request->validate([
            // 'cnpj' => 'required'
        ]);
        $cnpj = $request->cnpj ?? '';
        $codfilial = $request->codfilial;
        $cpf = $request->cpf ?? '';
        $ie = $request->ie ?? '';
        $uf = $request->uf ?? '';
        $pessoas = PessoaService::importar($codfilial, $uf, $cnpj, $cpf, $ie);
        return PessoaResource::collection($pessoas);
    }


    public function atualizaCampos($pessoa)
    {
        // $pessoa = PessoaService::atualizaCamposLegado($codpessoatelefone, $codpessoaemail, $codpessoaendereco);
    }

    public function comandaVendedor(Request $request, $codpessoa)
    {
        $pessoa = Pessoa::findOrFail($codpessoa);
        if (!$pessoa->vendedor) {
            throw new \Exception("\"{$pessoa->fantasia}\" não é vendedor!", 1);
        }
        $pdf = PessoaComandaVendedorService::pdf($pessoa);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comanda' . $codpessoa . '.pdf"'
        ]);
    }

    public function comandaVendedorImprimir(Request $request, $codpessoa)
    {
        $request->validate([
            'impressora' => ['required', 'string'],
            'copias' => ['required', 'integer']
        ]);
        $pessoa = Pessoa::findOrFail($codpessoa);
        if (!$pessoa->vendedor) {
            throw new \Exception("\"{$pessoa->fantasia}\" não é vendedor!", 1);
        }
        $pdf = PessoaComandaVendedorService::imprimir($pessoa, $request->impressora, $request->copias);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Comanda' . $codpessoa . '.pdf"'
        ]);
    }

    public function autocomplete(Request $request)
    {
        $qry = PessoaService::autocomplete($request->all());
        return response()->json($qry, 200);
    }


    public function verificaIeSefaz(Request $request)
    {
        $cnpj = $request->cnpj ?? '';
        $codfilial = $request->codfilial;
        $cpf = $request->cpf ?? '';
        $ie = $request->ie ?? '';
        $uf = $request->uf ?? '';

        $pessoas = PessoaService::verificaIeSefaz($codfilial, $uf, $cnpj, $cpf, $ie);

        return response()->json($pessoas, 200);
    }
}
