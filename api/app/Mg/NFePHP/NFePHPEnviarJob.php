<?php

namespace Mg\NFePHP;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NFePHPEnviarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * NUNCA reenviar à SEFAZ automaticamente.
     *
     * Esta é a proteção REAL contra envio duplicado, e não o $timeout: a extensão pcntl
     * não está instalada no container, então Worker::supportsAsyncSignals() é false e o
     * $timeout do job nunca é imposto por sinal — ele é declarativo.
     *
     * Com $tries = 1, se o retry_after da fila devolver este job enquanto ele ainda roda,
     * o segundo worker o marca como failed ANTES do handle() (attempts > maxTries), em vez
     * de executar de novo. Ou seja: no pior caso sobra um registro espúrio em
     * tbljobsfailedspa, nunca uma NFe enviada duas vezes.
     */
    public $tries = 1;

    /**
     * Declarativo (ver acima). Serve de base para o cálculo do REDIS_QUEUE_RETRY_AFTER,
     * que precisa ser MAIOR que isto. Cobre o enviarSincrono (~243s no pior caso) com folga.
     */
    public $timeout = 420;

    public function __construct(
        public int $codnotafiscal
    ) {
    }

    public function handle(): void
    {
        NFePHPEnvioService::executar($this->codnotafiscal);
    }
}
