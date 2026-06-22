<?php

namespace Mg\Moeda;

use Mg\MgModel;

/**
 * Moeda. PK = codmoeda (bigint/sequence). Cadastro compartilhado; CRUD no app
 * contas. Campos: moeda (nome), sigla (simbolo), iso (ISO 4217, unico).
 * tblcontratofixacao.moeda guarda o iso e referencia tblmoeda(iso).
 */
class Moeda extends MgModel
{
    protected $table = 'tblmoeda';
    protected $primaryKey = 'codmoeda';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];

    protected $fillable = [
        'moeda',
        'sigla',
        'iso',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codmoeda' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];
}
