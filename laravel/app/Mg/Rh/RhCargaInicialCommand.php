<?php

namespace Mg\Rh;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Mg\Colaborador\Colaborador;

class RhCargaInicialCommand extends Command
{
    protected $signature = 'rh:carga-inicial {codperiodo}';
    protected $description = 'Carga inicial de colaboradores para um período';

    public function handle()
    {
        $codperiodo = (int) $this->argument('codperiodo');
        $periodo = Periodo::findOrFail($codperiodo);

        $this->info("Período: {$periodo->periodoinicial} a {$periodo->periodofinal}");

        DB::beginTransaction();
        try {
            $colaboradores = Colaborador::whereNull('rescisao')
                ->with('Pessoa')
                ->get();

            $this->info("Colaboradores ativos: {$colaboradores->count()}");

            $criados = 0;
            $existentes = 0;

            foreach ($colaboradores as $col) {
                $existe = PeriodoColaborador::where('codperiodo', $codperiodo)
                    ->where('codcolaborador', $col->codcolaborador)
                    ->exists();

                if ($existe) {
                    $existentes++;
                    $this->line("  Já existe: {$col->Pessoa->fantasia}");
                    continue;
                }

                PeriodoColaborador::create([
                    'codperiodo' => $codperiodo,
                    'codcolaborador' => $col->codcolaborador,
                    'status' => PeriodoService::STATUS_COLABORADOR_ABERTO,
                    'valortotal' => 0,
                ]);

                $criados++;
                $this->info("  Criado: {$col->Pessoa->fantasia} (codcolaborador={$col->codcolaborador})");
            }

            DB::commit();

            $this->newLine();
            $this->info("Criados: {$criados}");
            $this->info("Já existiam: {$existentes}");

            $total = PeriodoColaborador::where('codperiodo', $codperiodo)->count();
            $comSetor = PeriodoColaborador::where('codperiodo', $codperiodo)
                ->whereHas('PeriodoColaboradorSetorS')
                ->count();
            $semSetor = $total - $comSetor;

            $this->info("Total no período: {$total}");
            $this->info("Com setor: {$comSetor}");
            if ($semSetor > 0) {
                $this->warn("Sem setor: {$semSetor} — vincular manualmente!");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Erro: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
