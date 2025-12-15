<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Mg\Woo\WooPedidoService;

class WooBuscarPedidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woo:buscar-pedidos {--id=} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buscar pedidos do WooCommerce';

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
        $wps = new WooPedidoService(); 
        if ($id = $this->option('id') ?? null) {
            $wps->buscarPedido($id);
        } else {
            $wps->buscarNovosPedidos();
        }        
        return true;
    }

}
