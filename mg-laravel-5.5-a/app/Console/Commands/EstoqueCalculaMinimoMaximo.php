<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

class EstoqueCalculaMinimoMaximo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estoque:calcula-minimo-maximo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza historico de vendas e calcula Estoque Minimo e Maximo dos Produtos';

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
        $sql = 'select fnatualizaestoqueminimomaximo() as atualizados';
        $ret = DB::select($sql);
        $this->info("{$ret[0]->atualizados} registros atualizados");
        return true;
    }
}
