<?php

namespace Mg\Pessoa;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailAniversarioIndividual extends Mailable
{
    use Queueable, SerializesModels;

    protected $aniv;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($aniv)
    {
        $this->aniv = $aniv;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = ($this->aniv->tipo == 'Empresa')?'Feliz Aniversário de Empresa!':'Feliz Aniversário!';
        return $this
            ->subject($subject)
            ->view('aniversarios-individual-mail.individualemail')
            ->with(['aniv' => $this->aniv]);
    }
}
