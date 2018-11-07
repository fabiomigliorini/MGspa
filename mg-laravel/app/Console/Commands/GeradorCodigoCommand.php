<?php

namespace App\Console\Commands;

use App\Repositories\GeradorCodigoRepository;

use Illuminate\Console\Command;

class GeradorCodigoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gerador {--tabela=} {--model=} {--url=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera codigo baseado na tabela';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // Pega opcao do estoquelocal
        $tabela = $this->option('tabela');
        $model = $this->option('model');
        $url = $this->option('url');

        if (empty($tabela)) {
            $this->line('');
            $this->error('Informe a Tabela! --tabela=');
            $this->line('');
            return;
        }

        if (empty($model)) {
            $this->line('');
            $this->error('Informe o Nome do Model! --model=');
            $this->line('');
            return;
        }

        if (empty($url)) {
            $this->line('');
            $this->error('Informe a URL! --url=');
            $this->line('');
            return;
        }


        $this->line('');
        if ($this->confirm('Gerar Model?')) {
            GeradorCodigoRepository::salvaModel($tabela, $model);
            $this->info('Model Gerado!');
        }

        $this->line('');
        if ($this->confirm('Gerar Repository?')) {
            GeradorCodigoRepository::salvaRepository($tabela, $model);
            $this->info('Repository Gerado!');
        }

        $this->line('');
        if ($this->confirm('Gerar Controller?')) {
            GeradorCodigoRepository::salvaController($model);
            $this->info('Controller Gerado!');
        }

        $this->line('');
        if ($this->confirm('Registrar Rota?')) {
            GeradorCodigoRepository::registraRota($model, $url);
            $this->info('Rota Registrada!');
        }

        $arquivosOld = GeradorCodigoRepository::arquivosOld();
        if (!empty($arquivosOld)) {
            $this->line('');
            $this->info('Não esqueça de excluir os arquivos:');
            foreach ($arquivosOld as $arquivo) {
                $this->line($arquivo);
            }
        }

        $this->line('');
    }

}
