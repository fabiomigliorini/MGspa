<?php

namespace Mg\Grao;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;

/**
 * Unidade armazenadora de grao: silo proprio, armazem de terceiro ou silo bag.
 * NAO tem filial (silo pode ser de terceiro) — por isso nao reusa tblestoquelocal.
 * Saldo da unidade = SUM(tblmovimentograo.liquido) onde contatipo = UNIDADE.
 */
class UnidadeArmazenadora extends MgModel
{
    protected $table = 'tblunidadearmazenadora';
    protected $primaryKey = 'codunidadearmazenadora';

    protected $fillable = [
        'unidadearmazenadora',
        'tipo',
        'codpessoa',
        'capacidadesacas',
        'observacao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'capacidadesacas' => 'float',
        'codpessoa' => 'integer',
        'codunidadearmazenadora' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    // Chaves Estrangeiras
    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }
}
