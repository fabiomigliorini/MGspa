<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:39:40
 */

namespace Mg\Permissao;

use Mg\MgModel;
use Mg\Usuario\GrupoUsuario;
use Mg\Permissao\Permissao;
use Mg\Usuario\Usuario;

class GrupoUsuarioPermissao extends MgModel
{
    protected $table = 'tblgrupousuariopermissao';
    protected $primaryKey = 'codgrupousuariopermissao';


    protected $fillable = [
        'codgrupousuario',
        'codpermissao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codgrupousuario' => 'integer',
        'codgrupousuariopermissao' => 'integer',
        'codpermissao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function GrupoUsuario()
    {
        return $this->belongsTo(GrupoUsuario::class, 'codgrupousuario', 'codgrupousuario');
    }

    public function Permissao()
    {
        return $this->belongsTo(Permissao::class, 'codpermissao', 'codpermissao');
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
