<?php

namespace Mg\Negocio;

use Mg\MgModel;

class NegocioCaixaMercadoria extends MGModel
{
    protected $table = 'tblnegociocaixamercadoria';
    protected $primaryKey = 'codnegociocaixamercadoria';
    protected $fillable = [
        'codnegocio',
        'codcaixamercadoria'
    ];
    protected $dates = [
        'recebimeto',
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

    public function UsuarioRecebimento()
    {
        return $this->belongsTo(Usuario::class, 'codusuariorecebimento', 'codusuario');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function CaixaMercadoria()
    {
        return $this->belongsTo(CaixaMercadoria::class, 'codcaixamercadoria', 'codcaixamercadoria');
    }

    // Tabelas Filhas
}
