<?php

namespace Mg\NotaFiscal;

use Mg\MgModel;

class NotaFiscalReferenciada extends MGModel
{
    protected $table = 'tblnotafiscalreferenciada';
    protected $primaryKey = 'codnotafiscalreferenciada';
    protected $fillable = [
        'codnotafiscal',
        'nfechave',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];

    // Chaves Estrangeiras
    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
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

}
