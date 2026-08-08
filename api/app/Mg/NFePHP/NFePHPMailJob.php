<?php

namespace Mg\NFePHP;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Mg\NotaFiscal\NotaFiscal;

/**
 * Envia o e-mail da NFe autorizada.
 *
 * Despachado de um lugar so — NFePHPService::vincularProtocoloAutorizacao(), o unico ponto
 * onde a nota passa a autorizada — para que TODO caminho que autoriza mande o e-mail: botao
 * Enviar, botao Consultar, robo de pendentes (contingencia off-line) e as rotas legadas.
 * Antes cada um desses caminhos precisava lembrar de chamar o NFePHPMailService na mao, e a
 * maioria nao chamava.
 *
 * Fica em fila propria para nao disputar worker com o envio a SEFAZ, que e `urgent`.
 */
class NFePHPMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Ao contrario do NFePHPEnviarJob, aqui repetir e barato e desejavel: o job pode pegar a
     * nota antes de o DANFE existir em disco, e o NFeAutorizadaMail estoura nesse caso.
     */
    public $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public int $codnotafiscal)
    {
    }

    public function handle(): void
    {
        NFePHPMailService::mail(NotaFiscal::findOrFail($this->codnotafiscal));
    }
}
