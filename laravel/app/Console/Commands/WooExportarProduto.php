<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Mg\Produto\Produto;
use Mg\Mercos\MercosProduto;
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
        if ($cod = $this->option('codproduto') ?? null) {
            $this->exportar($cod);
        } else {
            $cods = MercosProduto::whereNull('inativo')->select('codproduto')->distinct()->orderBy('codproduto')->get();
            foreach ($cods as $cod) {
                $this->exportar($cod->codproduto);
            }
            dd($cods);
            $offset = 0;
            $limit = 50;
            while ($ps = Produto::where('site', true)->whereNull('inativo')->orderBy('codproduto')->offset($offset)->limit($limit)->get()) {
                foreach ($ps as $prod) {
                }
                $offset += $limit;
            }
        }

        return true;
    }

    private function exportar($codproduto)
    {
        $prod = Produto::findOrFail($codproduto);
        Log::info("Exportando para Woo produto {$prod->codproduto} - '{$prod->produto}'!");
        try {
            $wps = new WooProdutoService($prod);
            $wps->exportar();
        } catch (\Throwable $th) {
            Log::error("Falha ao exportar para Woo produto {$prod->codproduto} - '{$prod->produto}'!");
            $msg = $th->getMessage();
            Log::error("{$msg}");
        }
    }
}
