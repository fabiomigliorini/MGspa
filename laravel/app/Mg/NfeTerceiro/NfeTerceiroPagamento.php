<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jul/2021 17:47:57
 */

namespace Mg\NfeTerceiro;

use Mg\MgModel;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\Usuario\Usuario;

class NfeTerceiroPagamento extends MgModel
{
    protected $table = 'tblnfeterceiropagamento';
    protected $primaryKey = 'codnfeterceiropagamento';


    protected $fillable = [
        'caut',
        'cnpj',
        'codnfeterceiro',
        'indpag',
        'tband',
        'tpag',
        'vpag'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'cnpj' => 'float',
        'codnfeterceiro' => 'integer',
        'codnfeterceiropagamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'indpag' => 'integer',
        'tband' => 'integer',
        'tpag' => 'integer',
        'vpag' => 'float'
    ];


    // Chaves Estrangeiras
    public function NfeTerceiro()
    {
        return $this->belongsTo(NfeTerceiro::class, 'codnfeterceiro', 'codnfeterceiro');
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