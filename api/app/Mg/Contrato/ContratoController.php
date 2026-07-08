<?php

namespace Mg\Contrato;

use App\Http\Requests\Mg\Contrato\ContratoCalculoRequest;
use App\Http\Requests\Mg\Contrato\ContratoStoreRequest;
use App\Http\Requests\Mg\Contrato\ContratoUpdateRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Mg\MgController;

class ContratoController extends MgController
{
    public function index(Request $request)
    {
        [$filter, $sort, $fields] = $this->filtros($request);
        $res = ContratoService::pesquisar($filter, $sort, $fields)->paginate()->appends($request->all());
        return ContratoResource::collection($res);
    }

    public function show(Request $request, $id)
    {
        return new ContratoResource(ContratoService::detalhe((int) $id));
    }

    /**
     * Preview do preço líquido (deduções FETHAB/IAGRO/Senar/Funrural) p/ a tela
     * de contrato — sem persistir. O agro é o dono do cálculo.
     */
    public function calculo(ContratoCalculoRequest $request)
    {
        return response()->json(ContratoCalculoService::calcular([
            'codcultura' => (int) $request->codcultura,
            'bruto' => (float) $request->bruto,
            'data' => $request->data,
            'isentofethab' => $request->boolean('isentofethab'),
            'funruralvenda' => $request->boolean('funruralvenda'),
        ]), 200);
    }

    /**
     * Plano de emissão de NF do contrato para uma carga (operação triangular):
     * sequência de notas, partes, kg/sacas rateados e valor bruto/líquido com
     * tributação. Preview — não persiste nem transmite. Ver NotaFiscalContratoService.
     */
    public function emissao(Request $request, $codcontrato, $codcarga)
    {
        return response()->json(
            \Mg\NotaFiscal\NotaFiscalContratoService::planoEmissao((int) $codcontrato, (int) $codcarga),
            200,
        );
    }

    /**
     * Próximo Nº Nosso sugerido para a safra (convenção CULTURA-AA/AA-NNNN).
     * Editável no form; a numeração definitiva é garantida no salvar.
     */
    public function proximoNumero($codsafra)
    {
        return response()->json(['numero' => ContratoService::proximoNumero((int) $codsafra)]);
    }

    public function store(ContratoStoreRequest $request)
    {
        $model = ContratoService::salvar($request->validated());
        return new ContratoResource(ContratoService::detalhe((int) $model->codcontrato));
    }

    public function update(ContratoUpdateRequest $request, $id)
    {
        $model = ContratoService::salvar($request->validated(), Contrato::findOrFail($id));
        return new ContratoResource(ContratoService::detalhe((int) $model->codcontrato));
    }

    public function destroy($id)
    {
        $contrato = Contrato::findOrFail($id);
        try {
            $contrato->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) !== '23503') {
                throw $e;
            }
            $msg = $e->getMessage();
            abort(409, match (true) {
                str_contains($msg, 'tblcargaponto') => 'Existem entregas vinculadas a este Contrato! Impossível excluir!',
                str_contains($msg, 'tblmovimentograo') => 'Existe movimentação de grão vinculada a este Contrato! Impossível excluir!',
                default => 'Existem registros vinculados a este Contrato! Impossível excluir!',
            });
        }
        return response()->noContent();
    }

    public function inativar(Request $request, $id)
    {
        ContratoService::inativar(Contrato::findOrFail($id));
        return new ContratoResource(ContratoService::detalhe((int) $id));
    }

    public function ativar(Request $request, $id)
    {
        ContratoService::ativar(Contrato::findOrFail($id));
        return new ContratoResource(ContratoService::detalhe((int) $id));
    }

    /**
     * Liga (POST) / desliga (DELETE) o flag barter — settlement em insumos, sem
     * exigir fixação/parcelas. Espelha o toggle de inativo: muda 1 campo sem PUT.
     */
    public function marcarBarter(Request $request, $id)
    {
        ContratoService::barter(Contrato::findOrFail($id), true);
        return new ContratoResource(ContratoService::detalhe((int) $id));
    }

    public function desmarcarBarter(Request $request, $id)
    {
        ContratoService::barter(Contrato::findOrFail($id), false);
        return new ContratoResource(ContratoService::detalhe((int) $id));
    }
}
