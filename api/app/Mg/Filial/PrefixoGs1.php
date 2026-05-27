<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:40:05
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Usuario\Usuario;

class PrefixoGs1 extends MgModel
{
    protected $table = 'tblprefixogs1';
    protected $primaryKey = 'codprefixogs1';


    protected $fillable = [
        'descricao',
        'especial',
        'final',
        'inicial'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codprefixogs1' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'especial' => 'boolean'
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

}
