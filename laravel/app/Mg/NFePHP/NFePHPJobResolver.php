<?php

namespace Mg\NFePHP;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Log;

use Mg\NotaFiscal\NotaFiscal;

class NFePHPJobResolver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $nf = NotaFiscal::findOrFail($this->codnotafiscal);
        $res = NFePHPRepositoryRobo::resolver($nf);
        $res = $this->eliminarXML($res);
        if (!isset($res->resolvido) || ($res->resolvido == false)) {
            Log::error("NFePHPJobResolver: Erro ao resolver #{$this->codnotafiscal}", (array) $res);
        } else {
            Log::notice("NFePHPJobResolver: Resolvido #{$this->codnotafiscal}");
        }
    }
}
