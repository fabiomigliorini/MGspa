<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 11:35:01
 */

namespace Mg\Permissao;

use Mg\MgModel;
use Mg\Permissao\GrupoUsuarioPermissao;
use Mg\Usuario\Usuario;

class Permissao extends MgModel
{
    protected $table = 'tblpermissao';
    protected $primaryKey = 'codpermissao';


    protected $fillable = [
        'observacoes',
        'permissao'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codpermissao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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
    public function GrupoUsuarioPermissaoS()
    {
        return $this->hasMany(GrupoUsuarioPermissao::class, 'codpermissao', 'codpermissao');
    }

}