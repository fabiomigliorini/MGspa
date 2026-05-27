<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:38:36
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Filial\AuditoriaTransmissao;
use Mg\Usuario\Usuario;

class BaseRemota extends MgModel
{
    protected $table = 'tblbaseremota';
    protected $primaryKey = 'codbaseremota';


    protected $fillable = [
        'baseremota',
        'conexao',
        'inicioreplicacao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codbaseremota' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inicioreplicacao' => 'datetime'
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
    public function AuditoriaTransmissaoS()
    {
        return $this->hasMany(AuditoriaTransmissao::class, 'codbaseremota', 'codbaseremota');
    }

}
