<?php

namespace Mg\Pessoa;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificacao extends Mailable
{
    use Queueable, SerializesModels;

    protected $email;
    protected $random;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $random)
    {
        $this->email = $email;
        $this->random = $random;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
          ->subject("Verificação de Email")
          ->view('pessoa-mail.verificacaoemail')
          ->with(['random' => $this->random, 'email' => $this->email]);
    }
}
