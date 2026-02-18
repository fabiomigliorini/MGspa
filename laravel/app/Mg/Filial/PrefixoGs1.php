<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 10:50:31
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codprefixogs1' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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