<?php

namespace Mg\NFePHP;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Log;

use Mg\NotaFiscal\NotaFiscal;

class NFePHPResolverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O robo NUNCA reenvia sozinho. Sem isso o job herdava o --tries=3 do worker, ou seja
     * o robo podia reenviar a mesma NFe 3x — bug pre-existente.
     */
    public $tries = 1;

    /**
     * Declarativo (pcntl nao esta instalado). resolver() pode encadear
     * enviarSincrono + criar + enviarSincrono + consultar.
     */
    public $timeout = 900;

    protected $codnotafiscal;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($codnotafiscal)
    {
        $this->codnotafiscal = $codnotafiscal;
    }

    public function eliminarXML($res)
    {
        $arr = (array) $res;
        foreach ($arr as $key => $value) {
            if (is_object($res->$key)) {
                $res->$key = $this->eliminarXML($res->$key);
            } elseif (is_string($value) && (substr($value, 0, 5) == '<?xml')) {
                unset($res->$key);
            }
        }
        return $res;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Cede a vez ao usuario. Sem isso o robo disputa o lock da nota com quem esta no
        // balcao e o usuario ve "Outra operacao ja esta em andamento". A nota volta na
        // proxima varredura, em 10 min.
        //
        // Duas condicoes: a primeira cobre operacao ja com o lock na mao; a segunda cobre
        // a janela em que o envio do usuario foi enfileirado mas ainda nao pegou o lock.
        if (NFePHPService::operacaoEmAndamento($this->codnotafiscal)
            || NFePHPEnvioService::emAndamento($this->codnotafiscal)) {
            Log::notice("NFePHPResolverJob: #{$this->codnotafiscal} com operacao em andamento — cede a vez");
            return;
        }

        $nf = NotaFiscal::findOrFail($this->codnotafiscal);
        $res = NFePHPRoboService::resolver($nf);
        $res = $this->eliminarXML($res);
        if (!isset($res->resolvido) || ($res->resolvido == false)) {
            Log::error("NFePHPResolverJob: Erro ao resolver #{$this->codnotafiscal}", (array) $res);
        } else {
            Log::notice("NFePHPResolverJob: Resolvido #{$this->codnotafiscal}");
        }
    }
}
