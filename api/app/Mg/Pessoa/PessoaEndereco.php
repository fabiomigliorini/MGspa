<?php

namespace Mg\Pessoa;

use Mg\MgModel;

/**
 * Stub minimal — preenche somente o necessário para checagens
 * de uso (FK codcidade) em Cidade::destroy(). Substituir
 * quando o domínio PessoaEndereco for migrado integralmente.
 */
class PessoaEndereco extends MgModel
{
    protected $table = 'tblpessoaendereco';
    protected $primaryKey = 'codpessoaendereco';

    public $timestamps = false;

    protected $casts = [
        'codpessoaendereco' => 'integer',
        'codpessoa' => 'integer',
        'codcidade' => 'integer',
    ];
}
