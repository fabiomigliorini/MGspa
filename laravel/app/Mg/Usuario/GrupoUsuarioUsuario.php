<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 11:35:14
 */

namespace Mg\Usuario;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Usuario\GrupoUsuario;
use Mg\Usuario\Usuario;

class GrupoUsuarioUsuario extends MgModel
{
    protected $table = 'tblgrupousuariousuario';
    protected $primaryKey = 'codgrupousuariousuario';


    protected $fillable = [
        'codfilial',
        'codgrupousuario',
        'codusuario'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codgrupousuario' => 'integer',
        'codgrupousuariousuario' => 'integer',
        'codusuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function GrupoUsuario()
    {
        return $this->belongsTo(GrupoUsuario::class, 'codgrupousuario', 'codgrupousuario');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuario', 'codusuario');
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