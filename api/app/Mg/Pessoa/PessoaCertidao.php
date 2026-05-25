<?php

namespace Mg\Pessoa;

use Mg\MgModel;

/**
 * Stub minimal do PessoaCertidao — só pra hasMany() do CertidaoEmissor
 * resolver autoload. Substituir quando PessoaCertidaoController for migrada.
 */
class PessoaCertidao extends MgModel
{
    protected $table = 'tblpessoacertidao';
    protected $primaryKey = 'codpessoacertidao';

    public $timestamps = false;
}
