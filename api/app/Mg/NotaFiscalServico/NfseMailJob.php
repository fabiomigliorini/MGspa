<?php

namespace Mg\NotaFiscalServico;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Envia o e-mail da NFS-e autorizada ao tomador (TASK-279 #7), como o NFePHPMailJob da NF-e.
 *
 * Despachado de um lugar so — NfseService::autorizar(), o unico ponto onde a nota passa a
 * autorizada — para que todo caminho que autoriza mande o e-mail: Enviar e Consultar.
 * Vai para os e-mails "Envio de NFe" do tomador; nota sem tomador (ou tomador sem esses
 * e-mails) nao manda nada. Fila propria (low), como a da NF-e.
 */
class NfseMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Repetir e' barato: o DANFSe e' gerado no envio do e-mail
    public $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public int $codnotafiscalservico)
    {
    }

    public function handle(): void
    {
        NfseService::mail(NotaFiscalServico::findOrFail($this->codnotafiscalservico));
    }
}
