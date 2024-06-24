<?php

namespace Mg\Titulo;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NFePHP\NFePHPService;
use Mg\NFePHP\NFePHPPathService;
use Mg\Pdv\RomaneioService;
use Mg\Titulo\BoletoBb\BoletoBbService;

class TituloAgrupamentoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ta;
    public $nfs;
    public $negs;
    public $bols;
    public $baixas;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(TituloAgrupamento $ta, $nfs, $negs, $bols, $baixas)
    {
        $this->ta = $ta;
        $this->nfs = $nfs;
        $this->negs = $negs;
        $this->bols = $bols;
        $this->baixas = $baixas;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        // Formata Titulo
        $cod = formataCodigo($this->ta->codtituloagrupamento);
        $this->subject("Agrupamento {$cod}");
        $this->replyTo('cobranca@mgpapelaria.com.br', 'MG Papelaria - Cobrança');


        // Renderiza View
        $this->view('titulo-agrupamento.mail')->with([
            'ta' => $this->ta,
            'baixas' => $this->baixas
        ]);


        // Anexa Boleto
        foreach ($this->bols as $bol) {
            $pdf = BoletoBbService::pdf($bol);
            $this->attachData($pdf, "Boleto{$bol->codtituloboleto}.pdf", [
                'mime' => 'application/pdf',
            ]);
        }

        // Anexa Notas Fiscais
        foreach ($this->nfs as $nf) {
            $path = NFePHPPathService::pathNFeAutorizada($nf);
            if (file_exists($path)) {
                $this->attach($path);
            }
            $path = NFePHPPathService::pathDanfe($nf);
            if (file_exists($path)) {
                $this->attach($path);
            }
        }

        // Anexa Romaneios
        foreach ($this->negs as $neg) {
            $pdf = RomaneioService::pdf($neg);
            $this->attachData($pdf, "Negocio{$neg->codnegocio}.pdf", [
                'mime' => 'application/pdf',
            ]);
        }

        return $this;
    }
}
