<?php

namespace Mg\Pdv;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mg\Negocio\Negocio;

/**
 * Imprime na termica do caixa, num trabalho so', todos os vales vendidos e
 * contra vales com saldo do negocio que acabou de fechar. Romaneio e nota
 * continuam por conta do front.
 */
class ImprimirValesNegocioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $codnegocio;
    public string $impressora;

    public function __construct(int $codnegocio, string $impressora)
    {
        $this->codnegocio = $codnegocio;
        $this->impressora = $impressora;
    }

    public function handle(): void
    {
        $negocio = Negocio::findOrFail($this->codnegocio);
        if (empty(ValeService::comprovantes($negocio))) {
            return;
        }
        ValeService::imprimir($this->codnegocio, $this->impressora);
    }
}
