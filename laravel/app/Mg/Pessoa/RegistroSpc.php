<?php
/**
 * Created by php artisan gerador:model.
 * Date: 21/Jul/2020 11:58:14
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

    protected $dates = [
        'alteracao',
        'baixa',
        'criacao',
        'inclusao'
    ];

    protected $casts = [
        'codpessoa' => 'integer',
        'codregistrospc' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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