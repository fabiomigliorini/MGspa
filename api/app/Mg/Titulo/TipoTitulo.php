<?php

namespace Mg\Titulo;

use App\Models\Usuario;
use Mg\MgModel;

class TipoTitulo extends MgModel
{
    protected $table = 'tbltipotitulo';
    protected $primaryKey = 'codtipotitulo';

    protected $fillable = [
        'codtipomovimentotitulo', 'credito', 'debito', 'inativo',
        'observacoes', 'pagar', 'receber', 'tipotitulo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codtipomovimentotitulo' => 'integer',
        'codtipotitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'credito' => 'boolean',
        'debito' => 'boolean',
        'pagar' => 'boolean',
        'receber' => 'boolean',
    ];

    public function TipoMovimentoTitulo()
    {
        return $this->belongsTo(TipoMovimentoTitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }
}
