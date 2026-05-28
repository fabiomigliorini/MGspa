<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:39:21
 */

namespace Mg\Estoque;

use Mg\MgModel;
use Mg\Estoque\EstoqueMovimento;
use Mg\Estoque\EstoqueSaldo;
use Mg\Usuario\Usuario;

class EstoqueSaldoConferencia extends MgModel
{
    protected $table = 'tblestoquesaldoconferencia';
    protected $primaryKey = 'codestoquesaldoconferencia';


    protected $fillable = [
        'codestoquesaldo',
        'customedioinformado',
        'customediosistema',
        'data',
        'inativo',
        'observacoes',
        'quantidadeinformada',
        'quantidadesistema'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codestoquesaldo' => 'integer',
        'codestoquesaldoconferencia' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'customedioinformado' => 'float',
        'customediosistema' => 'float',
        'data' => 'datetime',
        'inativo' => 'datetime',
        'quantidadeinformada' => 'float',
        'quantidadesistema' => 'float'
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
        return $this->hasMany(EstoqueMovimento::class, 'codestoquesaldoconferencia', 'codestoquesaldoconferencia');
    }

}
