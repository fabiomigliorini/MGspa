<?php

namespace Mg\Rh;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class BeeRecargaController extends Controller
{
    /**
     * Histórico do período inteiro — inclusive os lotes inativos, que a tela
     * mostra esmaecidos. São poucos por período (um por empresa), então não vale
     * um endpoint por empresa: o front separa por tab com o codempresa.
     */
    public function index(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $recargas = BeeRecarga::with(['Filial.Empresa', 'Titulo.Portador'])
            ->where('codperiodo', $codperiodo)
            ->orderBy('codbeerecarga', 'desc')
            ->get();

        // Uma consulta para os itens de todos os lotes, em vez de uma por lote
        // dentro do Resource. Com recarga avulsa o número de lotes por período
        // deixa de ser "um por empresa".
        $itens = BeeRecargaService::linhasDeLotes($recargas->pluck('codbeerecarga')->all());
        foreach ($recargas as $recarga) {
            $recarga->colaboradores = $itens[$recarga->codbeerecarga] ?? [];
        }

        return BeeRecargaResource::collection($recargas);
    }

    // Empresas com colaborador no período — uma tab por linha.
    public function empresas(int $codperiodo)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        return response()->json(BeeRecargaService::empresas($codperiodo));
    }

    // Extrato / recarga / saldo de cada colaborador da empresa.
    public function previa(int $codperiodo, Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $codempresa = (int) $request->input('codempresa');
        abort_unless($codempresa > 0, 422, 'Empresa inválida');

        return response()->json(BeeRecargaService::previa($codperiodo, $codempresa));
    }

    /**
     * Sem `itens` gera o lote do mês (todo mundo com saldo, pelo saldo cheio);
     * com `itens` gera o lote avulso (só os informados, com o valor informado).
     * Os dois passam pelo mesmo service, e portanto pelo mesmo FOR UPDATE.
     */
    public function gerar(int $codperiodo, GerarRecargaRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $recarga = BeeRecargaService::gerar(
                $codperiodo,
                (int) $request->input('codempresa'),
                $request->input('itens'),
                $request->input('dia'),
                $request->input('observacao'),
                $request->input('codportador')
            );
            DB::commit();
            $recarga->load(['Filial.Empresa', 'Titulo.Portador']);
            return new BeeRecargaResource($recarga);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    // Re-download da planilha de um lote já gerado (inclusive inativo).
    public function planilha(int $codperiodo, int $codbeerecarga)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $recarga = BeeRecarga::where('codperiodo', $codperiodo)->findOrFail($codbeerecarga);
        $xlsx = AcertoPlanilhaCartaoXlsx::gerar($recarga->codbeerecarga);

        return response($xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"recarga-bee-{$codbeerecarga}.xlsx\"",
        ]);
    }

    public function confirmar(int $codperiodo, int $codbeerecarga)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $recarga = BeeRecargaService::confirmar($codperiodo, $codbeerecarga);
            DB::commit();
            $recarga->load(['Filial.Empresa', 'Titulo.Portador']);
            return new BeeRecargaResource($recarga);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function inativar(int $codperiodo, int $codbeerecarga)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $recarga = BeeRecargaService::inativar($codperiodo, $codbeerecarga);
            DB::commit();
            $recarga->load(['Filial.Empresa', 'Titulo.Portador']);
            return new BeeRecargaResource($recarga);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
