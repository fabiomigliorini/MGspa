<?php

namespace Mg\NaturezaOperacao;

use Mg\MgModel;

class Operacao extends MgModel
{

    const ENTRADA = 1;
    const SAIDA = 2;

    protected $table = 'tbloperacao';
    protected $primaryKey = 'codoperacao';
    protected $fillable = [
        'operacao',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    // Tabelas Filhas
    public function NotaFiscalS()
    {
        return $this->hasMany(NotasFiscal::class, 'codoperacao', 'codoperacao');
    }

}
