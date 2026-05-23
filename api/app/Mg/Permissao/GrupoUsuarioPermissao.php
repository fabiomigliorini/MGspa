<?php

namespace Mg\Permissao;

use App\Models\Usuario;
use Mg\MgModel;
use Mg\Usuario\GrupoUsuario;

class GrupoUsuarioPermissao extends MgModel
{
    protected $table = 'tblgrupousuariopermissao';
    protected $primaryKey = 'codgrupousuariopermissao';

    protected $fillable = [
        'codgrupousuario',
        'codpermissao',
    ];

    protected $casts = [
        'codgrupousuario' => 'integer',
        'codgrupousuariopermissao' => 'integer',
        'codpermissao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
    ];

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
