<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

use App\Mg\Portador\ExtratoBbService;
use Mg\Portador\ExtratoBancario;
use Mg\Portador\Portador;
use Exception;

class ExtratoBbConsultarApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extrato-bb:consultar-api {--codportador=} {--inicio=} {--fim=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta via API o extrato no BB';

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
        $homologacao = false;

        // portador selecionado ou todos que se encaixam
        $codportador = $this->option('codportador');
        if ($codportador) { 
            $portadores = Portador::where(['codportador' => $codportador])->get();
        } else {
            $portadores = Portador::where([
                'codbanco' => 1,
            ])->whereNull('inativo')
            ->whereNotNull('bbclientid')
            ->whereNotNull('bbclientsecret')
            ->whereNotNull('bbdevappkey')
            ->get();
        }
        // percorre portadores
        $rets = [];
        foreach ($portadores as $portador) {

            // inicio padrao 16 dias
            $inicio = $this->option('inicio');
            if ($inicio) {
                $inicio = Carbon::parse($inicio);
            } else {
                $ultimoExtrato = ExtratoBancario::where(['codportador' => $portador->codportador])
                    ->orderBy('dia', 'desc')
                    ->first();

                if($ultimoExtrato) {
                    $inicio = $ultimoExtrato->dia;
                }else{
                    $inicio = Carbon::now()->subDays(16);
                }
            }

            // fim padrao hoje
            $fim = $this->option('fim');
            if ($fim) {
                $fim = Carbon::parse($fim);
            } else {
                $fim = Carbon::now()->subDays(0);
            }

            if ($inicio->gt($fim)) {
                throw new Exception("Data de Início maior que o Fim!");
            }

            $this->info("Consultando Portador: {$portador->codportador} ({$portador->portador}) de {$inicio} ate {$fim}!");

            // dev
            if($homologacao) {
                $portador->bbdevappkey = 'd1fa18b4902e4deab7107b2450e21995';
                $portador->bbclientid = 'eyJpZCI6ImY5MzRhZTktNDdhZi0iLCJjb2RpZ29QdWJsaWNhZG9yIjowLCJjb2RpZ29Tb2Z0d2FyZSI6MTM0MDM5LCJzZXF1ZW5jaWFsSW5zdGFsYWNhbyI6MX0';
                $portador->bbclientsecret = 'eyJpZCI6IiIsImNvZGlnb1B1YmxpY2Fkb3IiOjAsImNvZGlnb1NvZnR3YXJlIjoxMzQwMzksInNlcXVlbmNpYWxJbnN0YWxhY2FvIjoxLCJzZXF1ZW5jaWFsQ3JlZGVuY2lhbCI6MSwiYW1iaWVudGUiOiJob21vbG9nYWNhbyIsImlhdCI6MTc0NTY3NDY0MzE0OH0';
                $portador->agencia = '1505';
                $portador->conta = '1348';
            }
    
            $ret = ExtratoBbService::consultarExtrato($portador, $inicio, $fim);
            $this->info("Registros processados: {$ret['registros']} ({$ret['falhas']} falhas)");

            $rets[] = $ret;
        }

        return true;
    }
}
