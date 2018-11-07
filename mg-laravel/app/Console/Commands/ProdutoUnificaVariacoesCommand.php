<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;

use App\Repositories\ProdutoRepository;


class ProdutoUnificaVariacoesCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'produto:unifica-variacoes {codprodutovariacaoorigem} {codprodutovariacaodestino}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unifica Variações do Produto';

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

        $codprodutovariacaoorigem = $this->argument('codprodutovariacaoorigem');
        $codprodutovariacaodestino = $this->argument('codprodutovariacaodestino');

        $ret = ProdutoRepository::unificaVariacoes($codprodutovariacaoorigem, $codprodutovariacaodestino);

        return $ret;

    }

}
