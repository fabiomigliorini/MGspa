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
    protected $signature = 'gerador {--tabela=}';

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
        $this->info('teste:');

        // Pega opcao do estoquelocal
        $nome_tabela = $this->option('tabela');

        if (empty($nome_tabela)) {
            $this->line('');
            $this->error('Informe a Tabela! --tabela=');
            $this->line('');
            return;
        }

        GeradorCodigoRepository::model($nome_tabela);

    }

}
