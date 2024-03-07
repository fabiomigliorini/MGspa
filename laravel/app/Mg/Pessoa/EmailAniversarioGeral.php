<?php

namespace Mg\Pessoa;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailAniversarioGeral extends Mailable
{
    use Queueable, SerializesModels;

    protected $anivs;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($anivs)
    {
        $this->anivs = $anivs;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        
        return $this
          ->subject("Lista de Aniversarios")
          ->view('aniversarios-geral-mail.geralemail')
          ->with(['anivs' => $this->anivs]);
    }
}
