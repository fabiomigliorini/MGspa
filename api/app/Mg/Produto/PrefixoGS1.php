<?php

namespace Mg\Produto;

use Mg\MgModel;

class PrefixoGS1 extends MGModel
{
    protected $table = 'tblprefixogs1';
    protected $primaryKey = 'codprefixogs1';
    protected $fillable = [
        'unidademedida',
        'inicial',
        'final',
        'especial',
        'descricao',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];

    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    // Tabelas Filhas

}
