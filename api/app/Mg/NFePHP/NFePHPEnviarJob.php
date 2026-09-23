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
     * que precisa ser MAIOR que isto.
     *
     * Pior caso do enviarSincrono: 3 tentativas de envio a 80s (soaptimeout 60 + 20 do
     * SoapCurl) = ~240s, mais o laço de recuperação por consulta (17,5s de espera + uma
     * consulta que estoura em 40s e encerra o laço) = ~300s. As consultas de recuperação
     * são de tentativa única de propósito: com o retry de 3x delas, este número passava
     * de 20 min e estourava lock, timeout e o teto do front (TASK-159).
     */
    public $timeout = 900;

    public function __construct(
        public int $codnotafiscal
    ) {
    }

    public function handle(): void
    {
        NFePHPEnvioService::executar($this->codnotafiscal);
    }

    /**
     * Rede de segurança para o que não passa pelo catch do executar(): job marcado como
     * failed antes do handle() (o retry_after devolvendo o job com $tries = 1), erro de
     * desserialização, timeout imposto. Sem isto o progresso ficava 'processando' e a
     * nota travada até o TTL (TASK-147).
     */
    public function failed(\Throwable $e): void
    {
        NFePHPEnvioService::registrarFalha($this->codnotafiscal, $e);
    }
}
