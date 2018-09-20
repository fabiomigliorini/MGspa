<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

use Mg\Estoque\MinimoMaximo\FaltandoMail;

class EstoqueFaltandoMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estoque:faltando-mail {--codmarca=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar email com produtos abaixo do estoque minimo';

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
        // $codmarca = $this->option('codmarca');
        //
        // if (!empty($codmarca)) {
        //   $marca = \Mg\Marca\Marca::findOrFail($codmarca);
        //   $ret = FaltandoMailRepository::enviar($marca);
        // }

        $destinatario = env('MAIL_ADDRES_ESTOQUE_FALTANDO', null);

        Mail::to($destinatario)->queue(new FaltandoMail());

        //$ret = FaltandoMailRepository::enviar();

        dd($ret);
        return $ret;

        // return VendasRepository::atualizarUltimaCompra($pv);
        // return VendasRepository::atualizarPrimeiraVenda($pv);
        // return VendasRepository::sumarizarVendaMensal($pv);
    }
}
