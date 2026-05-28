<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;

use Mg\Produto\ProdutoBarraService;


class ProdutoUnificaBarrasCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'produto:unifica-barras {codprodutobarraorigem} {codprodutobarradestino}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unifica Codigos de Barras';

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

        $codprodutobarraorigem = $this->argument('codprodutobarraorigem');
        $codprodutobarradestino = $this->argument('codprodutobarradestino');

        $ret = ProdutoBarraService::unificaBarras($codprodutobarraorigem, $codprodutobarradestino);

        return $ret;

    }

}
