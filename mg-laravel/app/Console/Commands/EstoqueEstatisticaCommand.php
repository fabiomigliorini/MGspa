<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;

use App\Repositories\EstoqueEstatisticaRepository;


class EstoqueEstatisticaCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estoque:estatisticas {--codprodutovariacao=} {--codestoquelocal=} {--codproduto=} {--codmarca=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Estatísticas de Venda';

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
        $this->info("Building!");
        //$ret = EstoqueEstatisticaRepository::buscaEstatisticaProduto(1, 12, 1, 101001);
        //$ret = EstoqueEstatisticaRepository::buscaEstatisticaProduto(33, 73);
        $ret = EstoqueEstatisticaRepository::buscaEstatisticaProduto(3782, 73);
        print_r($ret);

        //$this->table(['serie'], $ret);
        $this->info("fim");

        /*
        $codprodutovariacao = $this->option('codprodutovariacao');
        $codestoquelocal = $this->option('codestoquelocal');
        $codproduto = $this->option('codproduto');
        $codmarca = $this->option('codmarca');

        EstoqueLocalProdutoVariacaoRepository::calculaVenda($codestoquelocal, $codprodutovariacao, $codproduto, $codmarca);
        // MarcaRepository::calculaVenda();
        */

    }

}
