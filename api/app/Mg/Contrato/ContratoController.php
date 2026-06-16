<?php

namespace Mg\Contrato;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mg\MgController;

class ContratoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = ContratoService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return response()->json($res, 200);
    }

    public function show(Request $request, $id)
    {
        $contrato = ContratoService::detalhe((int) $id);
        $data = $contrato->toArray();
        $data['calculo'] = ContratoCalculoService::calcularDoContrato($contrato);

        // Enriquece cada fixação com o preço líquido (deduções sobre o precoreal,
        // na data da fixação) — o agro é dono do cálculo.
        $funruralvenda = $contrato->codfilial && $contrato->Filial ? (bool) $contrato->Filial->funruralvenda : false;
        $data['ContratoFixacaoS'] = collect($data['ContratoFixacaoS'] ?? [])->map(function ($f) use ($contrato, $funruralvenda) {
            $calc = ContratoCalculoService::calcular([
                'codcultura' => (int) $contrato->codcultura,
                'bruto' => (float) $f['precoreal'],
                'data' => $f['data'] ?? null,
                'isentofethab' => (bool) $contrato->isentofethab,
                'funruralvenda' => $funruralvenda,
            ]);
            $f['precoliquido'] = $calc['liquido'];
            return $f;
        })->all();

        return response()->json($data, 200);
    }

    /**
     * Preview do preço líquido (deduções FETHAB/IAGRO/Senar/Funrural) p/ a tela
     * de contrato — sem persistir. O agro é o dono do cálculo.
     */
    public function calculo(Request $request)
    {
        $request->validate([
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'bruto' => ['required', 'numeric'],
            'data' => ['nullable', 'date'],
            'isentofethab' => ['nullable', 'boolean'],
            'funruralvenda' => ['nullable', 'boolean'],
        ]);

        return response()->json(ContratoCalculoService::calcular([
            'codcultura' => (int) $request->codcultura,
            'bruto' => (float) $request->bruto,
            'data' => $request->data,
            'isentofethab' => $request->boolean('isentofethab'),
            'funruralvenda' => $request->boolean('funruralvenda'),
        ]), 200);
    }

    public function store(Request $request)
    {
        $request->validate($this->regras($request));

        $model = ContratoService::salvar($request->all());

        return response()->json($model->fresh(ContratoService::WITH), 201);
    }

    public function update(Request $request, $id)
    {
        $model = Contrato::findOrFail($id);
        $request->validate($this->regras($request));

        $model = ContratoService::salvar($request->all(), $model);

        return response()->json($model->fresh(ContratoService::WITH), 200);
    }

    public function destroy($id)
    {
        Contrato::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        return response()->json(ContratoService::inativar(Contrato::findOrFail($id)), 200);
    }

    public function ativar(Request $request, $id)
    {
        return response()->json(ContratoService::ativar(Contrato::findOrFail($id)), 200);
    }

    protected function regras(Request $request): array
    {
        // FIXO trava o preço no próprio contrato (vira fixação-espelho), então
        // o preço é obrigatório e > 0; FIXAR/BARTER fixam à mão depois (preço
        // aqui é só referência, pode faltar).
        $preco = $request->tipo === 'FIXO'
            ? ['required', 'numeric', 'gt:0']
            : ['nullable', 'numeric', 'gte:0'];

        return [
            'contrato' => ['required', 'min:1'],
            'codpessoa' => ['required', 'exists:tblpessoa,codpessoa'],
            'codcultura' => ['required', 'exists:tblcultura,codcultura'],
            'codsafra' => ['nullable', 'exists:tblsafra,codsafra'],
            'tipo' => ['required', Rule::in(['FIXO', 'FIXAR', 'BARTER'])],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'preco' => $preco,
            'moeda' => ['nullable', Rule::in(['BRL', 'USD'])],
            'codnaturezaoperacao' => ['nullable', 'exists:tblnaturezaoperacao,codnaturezaoperacao'],
            'codpessoanf' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'isentofethab' => ['nullable', 'boolean'],
            'codfilial' => ['nullable', 'exists:tblfilial,codfilial'],
            'datacontrato' => ['nullable', 'date'],
            'embarqueinicio' => ['nullable', 'date'],
            'embarquefim' => ['nullable', 'date'],
            'codportador' => ['nullable', 'exists:tblportador,codportador'],
            'codpessoacorretora' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'comissaotipo' => ['nullable', Rule::in(['PERCENTUAL', 'SACA', 'TOTAL'])],
            'comissaovalor' => ['nullable', 'numeric', 'gte:0'],
            'comissaototal' => ['nullable', 'numeric', 'gte:0'],
            'viacooperativa' => ['nullable', 'boolean'],
            'codpessoacooperativa' => ['nullable', 'exists:tblpessoa,codpessoa'],
            'numerocomprador' => ['nullable', 'max:30'],
            'numerocorretora' => ['nullable', 'max:30'],
            'numerocooperativa' => ['nullable', 'max:30'],
        ];
    }
}
