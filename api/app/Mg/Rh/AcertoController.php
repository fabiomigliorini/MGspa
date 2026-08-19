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
                $request->input('forma'),
                $request->input('observacao'),
                $request->input('data')
            );
            DB::commit();
            return new PeriodoColaboradorAcertoResource($resultado['acerto']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    // Inativa TODOS os eventos ativos do colaborador (botão "Estornar Acerto").
    public function estornar(int $codperiodo, int $codperiodocolaborador)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $qtd = AcertoService::estornarTodos($codperiodocolaborador);
            DB::commit();
            return response()->json([
                'data' => [
                    'status'             => 'pendente',
                    'eventos_inativados' => $qtd,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    // Inativa UM acerto (desfaz as baixas; registro permanece).
    public function inativarAcerto(int $codperiodo, int $codperiodocolaboradoracerto)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $acerto = PeriodoColaboradorAcerto::findOrFail($codperiodocolaboradoracerto);
            AcertoService::inativarAcerto($codperiodocolaboradoracerto);
            DB::commit();

            // O dinheiro que já foi para o cartão não volta com o estorno. Sem
            // este aviso o colaborador fica com saldo negativo na tela de
            // recarga e ninguém sabe de onde veio.
            [$vivo, $confirmado] = AcertoService::recargaJaEnviada($acerto->codperiodocolaborador);

            return response()->json(['data' => [
                'status' => 'inativado',
                'recarga_enviada' => $vivo,
                'recarga_confirmada' => $confirmado,
            ]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    // Reativa UM acerto (reaplica as baixas).
    public function reativarAcerto(int $codperiodo, int $codperiodocolaboradoracerto)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            AcertoService::reativarAcerto($codperiodocolaboradoracerto);
            DB::commit();
            return response()->json(['data' => ['status' => 'ativo']]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function recibos(int $codperiodo, Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $codunidadenegocio = $request->input('codunidadenegocio');
        $codunidadenegocio = $codunidadenegocio ? (int) $codunidadenegocio : null;

        $pdf = AcertoReciboPdf::gerar($codperiodo, [], $codunidadenegocio);

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

    public function reciboAcerto(int $codperiodo, int $codperiodocolaboradoracerto, Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $tipo = $request->input('tipo');
        $tipo = in_array($tipo, ['pagamento', 'recebimento'], true) ? $tipo : null;

        $pdf = AcertoReciboPdf::gerarPorAcerto($codperiodocolaboradoracerto, $tipo);

        if ($pdf === '') {
            abort(404, 'Acerto inativo ou nao encontrado.');
        }

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recibo.pdf"',
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
