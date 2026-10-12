<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:22:50
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\TipoTitulo;
use Mg\Usuario\Usuario;

class TipoMovimentoTitulo extends MgModel
{
    protected $table = 'tbltipomovimentotitulo';
    protected $primaryKey = 'codtipomovimentotitulo';


    protected $fillable = [
        'inativo',
        'observacao',
        'tipomovimentotitulo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codtipomovimentotitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
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
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
    }

}
