<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mg\Colaborador\Colaborador;
use Mg\Colaborador\ColaboradorObserver;

class CalendarioInicializarCommand extends Command
{
    protected $signature = 'calendario:inicializar';

    protected $description = 'Inicializa o Google Calendar com todos os eventos de colaboradores, férias e dependentes existentes no banco';

    public function handle()
    {
        $colaboradores = Colaborador::all();
        $total = $colaboradores->count();

        $this->info("Inicializando calendário para {$total} colaboradores...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $observer = new ColaboradorObserver();
        $criados = 0;
        $erros = 0;

        foreach ($colaboradores as $colaborador) {
            try {
                $observer->created($colaborador);
                $criados++;
            } catch (\Throwable $e) {
                $erros++;
                $this->newLine();
                $this->error("Erro no colaborador {$colaborador->codcolaborador}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Concluído! Processados: {$criados}, Erros: {$erros}");
        $this->info("Os jobs de sincronização com o Google Calendar foram enfileirados.");

        return 0;
    }
}
