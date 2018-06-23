<?php

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\Usuario\Usuario;

class NotaFiscalCartaCorrecao extends MGModel
{
    protected $table = 'tblnotafiscalcartacorrecao';
    protected $primaryKey = 'codnotafiscalcartacorrecao';
    protected $fillable = [
        'codnotafiscal',
        'lote',
        'data',
        'sequencia',
        'texto',
        'protocolo',
        'protocolodata',
    ];
    protected $dates = [
        'data',
        'protocolodata',
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
