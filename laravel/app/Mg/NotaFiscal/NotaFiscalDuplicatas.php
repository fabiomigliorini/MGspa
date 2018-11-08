<?php

namespace Mg\NotaFiscal;

use Mg\MgModel;

class NotaFiscalDuplicatas extends MGModel
{
    protected $table = 'tblnotafiscalduplicatas';
    protected $primaryKey = 'codnotafiscalduplicatas';
    protected $fillable = [
        'codnotafiscal',
        'fatura',
        'vencimento',
        'valor',
    ];
    protected $dates = [
        'vencimento',
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
