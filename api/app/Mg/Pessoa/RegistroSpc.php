<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:29:05
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class RegistroSpc extends MgModel
{
    protected $table = 'tblregistrospc';
    protected $primaryKey = 'codregistrospc';


    protected $fillable = [
        'baixa',
        'codpessoa',
        'inclusao',
        'observacoes',
        'valor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'baixa' => 'date',
        'codpessoa' => 'integer',
        'codregistrospc' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inclusao' => 'date',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
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
