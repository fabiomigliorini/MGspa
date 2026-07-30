<?php

namespace Mg\Rh;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class ColaboradorRubricaController extends Controller
{
    // Rubricas só podem ser editadas com o colaborador ABERTO. Encerrar trava tudo.
    private function assertAberto(int $codperiodocolaborador): void
    {
        $status = PeriodoColaborador::where('codperiodocolaborador', $codperiodocolaborador)->value('status');
        if ($status === PeriodoService::STATUS_COLABORADOR_ENCERRADO) {
            abort(422, 'Colaborador encerrado — reabra para editar as rubricas.');
        }
    }

    /**
     * Aplicação em massa (presenteísmo): cria a rubrica do catálogo informado
     * para TODOS os colaboradores abertos (status A) do período, copiando os
     * valores-padrão do catálogo. Pula quem já possui a rubrica.
     */
    public function aplicarMassa(int $codperiodo, Request $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        $codrubrica = (int) $request->input('codrubrica');
        $rubrica = Rubrica::findOrFail($codrubrica);
        $periodo = Periodo::findOrFail($codperiodo);

        DB::beginTransaction();
        try {
            $pcs = PeriodoColaborador::where('codperiodo', $codperiodo)
                ->where('status', PeriodoService::STATUS_COLABORADOR_ABERTO)
                ->get();

            $aplicados = 0;
            foreach ($pcs as $pc) {
                $jaTem = ColaboradorRubrica::where('codperiodocolaborador', $pc->codperiodocolaborador)
                    ->where('codrubrica', $codrubrica)
                    ->exists();
                if ($jaTem) {
                    continue;
                }

                $nova = new ColaboradorRubrica([
                    'codrubrica' => $rubrica->codrubrica,
                    'descricao' => $rubrica->descricao,
                    'tipovalor' => $rubrica->tipovalor,
                    'tipocondicao' => $rubrica->tipocondicao,
                    'recorrente' => $rubrica->recorrente,
                    'concedido' => true,
                ]);
                $nova->codperiodocolaborador = $pc->codperiodocolaborador;
                $nova->valorcalculado = 0;

                if ($rubrica->tipovalor === 'F') {
                    $nova->valorfixo = $rubrica->valorpadrao;
                } elseif ($rubrica->tipovalor === 'Q') {
                    $nova->valorunitario = $rubrica->valorunitariopadrao;
                    $nova->quantidade = $periodo->diasuteis;
                }

                $nova->save();

                CalculoRubricaService::calcularColaborador($pc->codperiodocolaborador);
                $aplicados++;
            }

            DB::commit();
            return response()->json(['aplicados' => $aplicados]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function store(int $codperiodocolaborador, ColaboradorRubricaStoreRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);
        $this->assertAberto($codperiodocolaborador);

        DB::beginTransaction();
        try {
            $rubrica = ColaboradorRubrica::create(array_merge(
                $request->validated(),
                ['codperiodocolaborador' => $codperiodocolaborador, 'valorcalculado' => 0]
            ));

            CalculoRubricaService::calcularColaborador($codperiodocolaborador);

            DB::commit();
            return new ColaboradorRubricaResource($rubrica->refresh());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function update(int $codcolaboradorrubrica, ColaboradorRubricaUpdateRequest $request)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $rubrica = ColaboradorRubrica::findOrFail($codcolaboradorrubrica);
            $this->assertAberto($rubrica->codperiodocolaborador);
            $rubrica->fill($request->validated());
            $rubrica->save();

            CalculoRubricaService::calcularColaborador($rubrica->codperiodocolaborador);

            DB::commit();
            return new ColaboradorRubricaResource($rubrica->refresh());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $codcolaboradorrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $rubrica = ColaboradorRubrica::findOrFail($codcolaboradorrubrica);
            $codperiodocolaborador = $rubrica->codperiodocolaborador;
            $this->assertAberto($codperiodocolaborador);
            $rubrica->delete();

            CalculoRubricaService::calcularColaborador($codperiodocolaborador);

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }

    public function toggleConcedido(int $codcolaboradorrubrica)
    {
        Autorizador::autoriza(['Recursos Humanos']);

        DB::beginTransaction();
        try {
            $rubrica = ColaboradorRubrica::findOrFail($codcolaboradorrubrica);
            $this->assertAberto($rubrica->codperiodocolaborador);
            $rubrica->concedido = !$rubrica->concedido;
            $rubrica->save();

            CalculoRubricaService::calcularColaborador($rubrica->codperiodocolaborador);

            DB::commit();
            return new ColaboradorRubricaResource($rubrica->refresh());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['erro' => $e->getMessage()], 422);
        }
    }
}
