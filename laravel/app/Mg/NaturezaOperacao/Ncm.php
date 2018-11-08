<?php

namespace Mg\NaturezaOperacao;

use Mg\MgModel;

class Ncm extends MGModel
{

    protected $table = 'tblncm';
    protected $primaryKey = 'codncm';
    protected $fillable = [
        'ncm',
        'descricao',
        'codncmpai',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
    ];

    public function NcmPai()
    {
        return $this->belongsTo(Ncm::class, 'codncmpai', 'codncm');
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
        return $this->hasMany(Produto::class, 'codncm', 'codncm');
    }

    public function NcmS()
    {
        return $this->hasMany(Ncm::class, 'codncmpai', 'codncm');
    }

}
