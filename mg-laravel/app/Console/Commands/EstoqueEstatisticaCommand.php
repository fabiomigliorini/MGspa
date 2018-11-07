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
    protected $signature = 'estoque:estatisticas {--codproduto=} {--meses=} {--codprodutovariacao=} {--codestoquelocal=}';

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

        $codproduto = $this->option('codproduto');
        $meses = $this->option('meses');
        $codprodutovariacao = $this->option('codprodutovariacao');
        $codestoquelocal = $this->option('codestoquelocal');
        $ret = EstoqueEstatisticaRepository::buscaEstatisticaProduto($codproduto, $meses, $codprodutovariacao, $codestoquelocal);
        print_r($ret);
        $this->info("fim");

        //$this->info("Building!");
        //$ret = EstoqueEstatisticaRepository::buscaEstatisticaProduto(1, 12, 1, 101001);
        //$ret = EstoqueEstatisticaRepository::buscaEstatisticaProduto(33, 73);
        //$ret = EstoqueEstatisticaRepository::buscaEstatisticaProduto(3782, 73);

        //$this->table(['serie'], $ret);

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
