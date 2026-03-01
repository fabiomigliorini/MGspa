<?php

namespace Mg\Rh;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class AcertoController extends Controller
{
    public function index(int $codperiodo, Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $dias = (int) $request->input('dias', 5);

        $acertos = AcertoService::listarAcertos($codperiodo, $dias);

        return AcertoListResource::collection($acertos);
    }

    public function titulos(int $codperiodo, int $codperiodocolaborador, Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $dias = (int) $request->input('dias', 5);

        $data = AcertoService::buscarTitulos($codperiodocolaborador, $dias);

        return new AcertoTitulosResource($data);
    }

    public function efetivar(int $codperiodo, int $codperiodocolaborador, EfetivarAcertoRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $resultado = AcertoService::efetivar(
                $codperiodocolaborador,
                $request->input('titulos', []),
                $request->input('observacao')
            );
            DB::commit();
            return new AcertoEfetivadoResource($resultado);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function estornar(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $qtd = AcertoService::estornar($codperiodocolaborador);
            DB::commit();
            return response()->json([
                'data' => [
                    'status'                 => 'pendente',
                    'liquidacoes_estornadas' => $qtd,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function recibos(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $pdf = AcertoReciboPdf::gerar($codperiodo);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recibos.pdf"',
        ]);
    }

    public function recibosColaborador(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $pdf = AcertoReciboPdf::gerar($codperiodo, [$codperiodocolaborador]);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recibos.pdf"',
        ]);
    }

    public function relatorioFolha(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $pdf = AcertoRelatorioFolhaPdf::gerar($codperiodo);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="relatorio-folha.pdf"',
        ]);
    }
}
