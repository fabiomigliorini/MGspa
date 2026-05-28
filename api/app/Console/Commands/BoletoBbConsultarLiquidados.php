<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use Mg\Titulo\BoletoBb\BoletoBbService;

class BoletoBbConsultarLiquidados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'boleto-bb:consultar-liquidados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta via API os boletos liquidados no BB';

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
        BoletoBbService::consultarLiquidados();
    }
}
