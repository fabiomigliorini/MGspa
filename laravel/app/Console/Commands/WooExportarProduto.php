<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Mg\Produto\Produto;
// use Mg\Mercos\MercosProduto;
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
            $this->produto($cod);
        } else {
            $this->alterados();
        }
        return true;
    }

    private function alterados()
    {
        $this->produtos();
        $this->variacoes();
        $this->embalagens();
        $this->imagensNovas();
        $this->imagensExcluidas();
        $this->estoque();
    }

    private function produtos() 
    {
        Log::info("Exportando para Woo Produtos alterados!");
        $sql = '
            select 
                p.codproduto
                --, p.alteracao, wp.exportacao 
            from tblproduto p
            inner join tblwooproduto wp on (wp.codproduto = p.codproduto and wp.codprodutovariacao is null)
            where p.alteracao >= wp.exportacao 
            order by 1
        ';
        $cods = DB::select($sql);
        foreach ($cods as $cod) {
            $this->produto($cod->codproduto);
        }
    }

    private function variacoes() 
    {
        Log::info("Exportando para Woo Variações alteradas!");
        $sql = '
            select distinct
                pv.codproduto
                --, p.alteracao, wp.exportacao 
            from tblprodutovariacao pv
            inner join tblwooproduto wp on (wp.codproduto = pv.codproduto)
            where pv.alteracao >= wp.exportacao 
            order by 1
        ';
        $cods = DB::select($sql);
        foreach ($cods as $cod) {
            $this->produto($cod->codproduto);
        }
    }    

    private function embalagens() 
    {
        Log::info("Exportando para Woo Embalagens alteradas!");
        $sql = '
            select distinct
                pe.codproduto
                --, p.alteracao, wp.exportacao 
            from tblprodutoembalagem pe
            inner join tblwooproduto wp on (wp.codproduto = pe.codproduto and wp.codprodutovariacao is null)
            where pe.alteracao >= wp.exportacao 
            order by 1
        ';
        $cods = DB::select($sql);
        foreach ($cods as $cod) {
            $this->produto($cod->codproduto);
        }
    }    

    private function imagensNovas() 
    {
        Log::info("Exportando para Woo Imagens novas!");
        $sql = '
            select distinct
                pi.codproduto
                --, p.alteracao, wp.exportacao 
            from tblprodutoimagem pi
            inner join tblimagem i on (i.codimagem = pi.codimagem)
            inner join tblwooproduto wp on (wp.codproduto = pi.codproduto and wp.codprodutovariacao is null)
            where i.alteracao >= wp.exportacao 
            order by 1
        ';
        $cods = DB::select($sql);
        foreach ($cods as $cod) {
            $this->produto($cod->codproduto);
        }
    }

    private function imagensExcluidas() 
    {
        Log::info("Exportando para Woo Imagens excluidas!");
        $sql = '
            select distinct 
                wp.codproduto
            from tblwooproduto wp
            inner join tblwooprodutoimagem wpi on (wpi.codwooproduto = wp.codwooproduto)
            where wpi.codprodutoimagem is null
            order by 1
        ';
        $cods = DB::select($sql);
        foreach ($cods as $cod) {
            $this->produto($cod->codproduto);
        }
    }
    
    private function estoque() 
    {
                $locais = env('WOO_API_CODESTOQUELOCAL_DISPONIVEL');

        Log::info("Exportando para Woo Estoques desatualizados!");
        $sql = '
            select distinct 
                wp.codproduto 
            from tblestoquelocalprodutovariacao elpv
            inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
            inner join tblwooproduto wp on (wp.codproduto = pv.codproduto)
            inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
            where elpv.codestoquelocal in (' . $locais . ')
            and es.alteracao >= wp.alteracao 
            order by 1
        ';
        $cods = DB::select($sql);
        foreach ($cods as $cod) {
            $this->produto($cod->codproduto);
        }
    }



    private function produto($codproduto)
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
