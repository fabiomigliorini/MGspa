<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;

use App\Repositories\EstoqueLocalProdutoVariacaoRepository;
use App\Repositories\MarcaRepository;

class EstoqueCalculaEstatisticasCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estoque:calcula-estatisticas {--codprodutovariacao=} {--codestoquelocal=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula estatística de Venda da EstoqueLocalProdutoVariacao';

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
        $codprodutovariacao = $this->option('codprodutovariacao');
        $codestoquelocal = $this->option('codestoquelocal');

        EstoqueLocalProdutoVariacaoRepository::calculaVenda($codestoquelocal, $codprodutovariacao);
        // MarcaRepository::calculaVenda();

    }

}
