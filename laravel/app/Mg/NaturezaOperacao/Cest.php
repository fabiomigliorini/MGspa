<?php

namespace Mg\NaturezaOperacao;

use Mg\MgModel;

class Cest extends MGModel
{

    protected $table = 'tblcest';
    protected $primaryKey = 'codcest';
    protected $fillable = [
        'cest',
        'ncm',
        'descricao',
        'codncm',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];

    public function Ncm()
    {
        return $this->belongsTo(Ncm::class, 'codncm', 'codncm');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    // Tabelas Filhas
    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codcest', 'codcest');
    }

}
