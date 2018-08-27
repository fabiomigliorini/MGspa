<?php

namespace Mg\CaixaMercadoria;

use Mg\MgModel;

class CaixaMercadoriaModelo extends MGModel
{
    protected $table = 'tblcaixamercadoriamodelo';
    protected $primaryKey = 'codcaixamercadoriamodelo';
    protected $fillable = [
        'obeservacoes',
        'inativo'
    ];
    protected $dates = [
        'inativo',
        'alteracao',
        'criacao'
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
    public function CaixaMercadoriaS()
    {
        return $this->hasMany(CaixaMercadoria::class, 'codcaixamercadoriamodelo', 'codcaixamercadoriamodelo');
    }


}
