<?php

namespace Mg\Moeda;

use Mg\MgModel;

/**
 * Moeda (ISO 4217). PK = codigo de 3 letras (BRL, USD, ...). Cadastro
 * compartilhado; CRUD no app contas. Referenciada por tblcontratofixacao.moeda.
 */
class Moeda extends MgModel
{
    protected $table = 'tblmoeda';
    protected $primaryKey = 'moeda';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'moeda',
        'descricao',
        'simbolo',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];
}
