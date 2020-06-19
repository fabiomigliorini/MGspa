<?php

namespace Mg\NFePHP\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NFePHP\NFePHPPathService;
use Mg\NFePHP\NFePHPService;

class NFeAutorizadaMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $nf;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(NotaFiscal $nf)
    {
        $this->nf = $nf;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $pathNFeAutorizada = NFePHPPathService::pathNFeAutorizada($this->nf);
        if (!file_exists($pathNFeAutorizada)) {
            throw new \Exception("Arquivo XML não localizado ($pathNFeAutorizada)!");
        }

        $pathDanfe = NFePHPPathService::pathDanfe($this->nf);
        if (!file_exists($pathDanfe)) {
            NFePHPService::danfe($this->nf);
            if (!file_exists($pathDanfe)) {
                throw new \Exception("Erro ao gerar arquivo PDF ($pathDanfe)!");
            }
        }

        return $this
          ->subject("Nota Fiscal {$this->nf->numero} {$this->nf->Filial->Pessoa->fantasia}")
          ->attach($pathDanfe)
          ->attach($pathNFeAutorizada)
          ->view('nfe-mail.autorizada')
          ->with(['nf' => $this->nf]);
    }
}
