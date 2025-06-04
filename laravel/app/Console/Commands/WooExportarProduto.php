<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mg\Produto\Produto;
use Mg\Woo\WooProdutoService;

/*
use App\Mg\Portador\ExtratoBbService;
use Mg\Portador\Portador;
use Exception;
*/
class WooExportarProduto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woo:exportar-produto {--codproduto=} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exportar produto para WooCommerce';

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


        $codproduto = $this->option('codproduto')??null;
        $prod = Produto::findOrFail($codproduto);

        $wps = new WooProdutoService($prod);
        $wps->exportar();
    

        die($codproduto);

        die('aqi');

        return true;
    }
}
