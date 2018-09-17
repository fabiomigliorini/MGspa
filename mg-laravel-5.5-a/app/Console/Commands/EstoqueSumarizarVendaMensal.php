<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Mg\Produto\ProdutoVariacao;
use Mg\Estoque\MinimoMaximo\VendasRepository;

class EstoqueSumarizarVendaMensal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estoque:sumarizar-venda-mensal {codprodutovariacao}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza historico de vendas de uma variacao';

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
        $codprodutovariacao = $this->argument('codprodutovariacao');
        $pv = ProdutoVariacao::findOrFail($codprodutovariacao);
        return VendasRepository::atualizarUltimaCompra($pv);
        return VendasRepository::atualizarPrimeiraVenda($pv);
        return VendasRepository::sumarizarVendaMensal($pv);
    }
}
