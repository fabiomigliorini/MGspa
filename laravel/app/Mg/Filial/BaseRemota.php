<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Oct/2024 12:28:32
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

    protected $dates = [
        'alteracao',
        'criacao',
        'inicioreplicacao'
    ];

    protected $casts = [
        'codbaseremota' => 'integer',
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
    public function AuditoriaTransmissaoS()
    {
        return $this->hasMany(AuditoriaTransmissao::class, 'codbaseremota', 'codbaseremota');
    }

}