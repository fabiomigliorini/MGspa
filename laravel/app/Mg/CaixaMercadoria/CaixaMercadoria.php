<?php

namespace Mg\CaixaMercadoria;

use Mg\MgModel;

class CaixaMercadoria extends MGModel
{
    protected $table = 'tblcaixamercadoria';
    protected $primaryKey = 'codcaixamercadoria';
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
    public function CaixaMercadoriaModelo()
    {
        return $this->belongsTo(CaixaMercadoriaModelo::class, 'codcaixamercadoriamodelo', 'codcaixamercadoriamodelo');
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
    public function NegocioCaixaMercadoriaS()
    {
        return $this->hasMany(NegocioCaixaMercadoria::class, 'codcaixamercadoria', 'codcaixamercadoria');
    }


}
