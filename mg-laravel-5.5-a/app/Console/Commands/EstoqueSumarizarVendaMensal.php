<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Mg\Produto\ProdutoVariacao;
use Mg\Estoque\MinimoMaximo\VendasRepository;
use Mg\Estoque\MinimoMaximo\ComprasRepository;

class EstoqueSumarizarVendaMensal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estoque:sumarizar-venda-mensal {--codprodutovariacao=}  {--codmarca=}';

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
        $codprodutovariacao = $this->option('codprodutovariacao');
        $codmarca = $this->option('codmarca');

        $ret = [];
        if (!empty($codprodutovariacao)) {
            $var = ProdutoVariacao::findOrFail($codprodutovariacao);
            $ret[$var->codprodutovariacao] = VendasRepository::atualizar($var);
        } else if (!empty($codmarca)) {
          $marca = \Mg\Marca\Marca::findOrFail($codmarca);
          foreach ($marca->ProdutoS as $prod) {
            foreach ($prod->ProdutoVariacaoS as $var) {
              $ret[$var->codprodutovariacao] = VendasRepository::atualizar($var);
            }
          }
          ComprasRepository::gerarPlanilhaPedido($marca);
        } else {
          $vars = \Mg\Produto\ProdutoVariacao::all();
          foreach ($vars as $var) {
            $ret[$var->codprodutovariacao] = VendasRepository::atualizar($var);
          }
        }

        dd($ret);
        return $ret;

        // return VendasRepository::atualizarUltimaCompra($pv);
        // return VendasRepository::atualizarPrimeiraVenda($pv);
        // return VendasRepository::sumarizarVendaMensal($pv);
    }
}
