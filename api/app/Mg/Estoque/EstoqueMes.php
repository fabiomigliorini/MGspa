<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:39:08
 */

namespace Mg\Estoque;

use Mg\MgModel;
use Mg\Estoque\EstoqueMovimento;
use Mg\Estoque\EstoqueSaldo;
use Mg\Usuario\Usuario;

class EstoqueMes extends MgModel
{
    protected $table = 'tblestoquemes';
    protected $primaryKey = 'codestoquemes';


    protected $fillable = [
        'codestoquesaldo',
        'customedio',
        'entradaquantidade',
        'entradavalor',
        'inicialquantidade',
        'inicialvalor',
        'mes',
        'saidaquantidade',
        'saidavalor',
        'saldoquantidade',
        'saldovalor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codestoquemes' => 'integer',
        'codestoquesaldo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'customedio' => 'float',
        'entradaquantidade' => 'float',
        'entradavalor' => 'float',
        'inicialquantidade' => 'float',
        'inicialvalor' => 'float',
        'mes' => 'date',
        'saidaquantidade' => 'float',
        'saidavalor' => 'float',
        'saldoquantidade' => 'float',
        'saldovalor' => 'float'
    ];


    // Chaves Estrangeiras
    public function EstoqueSaldo()
    {
        return $this->belongsTo(EstoqueSaldo::class, 'codestoquesaldo', 'codestoquesaldo');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function EstoqueMovimentoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codestoquemes', 'codestoquemes');
    }

}
