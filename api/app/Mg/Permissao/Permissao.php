<?php

namespace Mg\Permissao;

use App\Models\Usuario;
use Mg\MgModel;

class Permissao extends MgModel
{
    protected $table = 'tblpermissao';
    protected $primaryKey = 'codpermissao';

    protected $fillable = [
        'observacoes',
        'permissao',
    ];

    protected $casts = [
        'codpermissao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function GrupoUsuarioPermissaoS()
    {
        return $this->hasMany(GrupoUsuarioPermissao::class, 'codpermissao', 'codpermissao');
    }
}
